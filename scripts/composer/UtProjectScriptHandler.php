<?php

/**
 * @file
 * Contains \UtDrupalProject\composer\ScriptHandler.
 */

namespace UtDrupalProject\composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class UtProjectScriptHandler {

  const DRUPAL_VM_CONFIG_FILE = 'config/config.yml';
  const COMPOSER_JSON_FILE = 'composer.json';

  /**
   * @param \Composer\Script\Event $event
   */
  public static function postCreateProject(Event $event)
  {
    /** @var \Composer\IO\IOInterface $io */
    $io = $event->getIO();

    // Require interaction.
    if (!$io->isInteractive()) {
      $io->writeError('<error>The UT project setup script requires user interaction.</error>');
      return;
    }

    // Introduce this step to the user.
    $io->write('');
    $io->write('<info>=================================</info>');
    $io->write('<info>= INITIAL PROJECT CONFIGURATION =</info>');
    $io->write('<info>=================================</info>');
    $io->write('');


    // Check if setting up the project to update the starter project.
    $updatingComposerProject = $io->askConfirmation('Are you setting up this project so that you may update the starter project? [<comment>no</comment>]? ', false);

    //TODO: Check host requirements.

    if (!$updatingComposerProject) {
      $projectName = self::askForProjectName($io);
      $friendlyProjectName = self::askForFriendlyProjectName($io);
    }
    else {
      $config = Yaml::parse(file_get_contents(self::DRUPAL_VM_CONFIG_FILE));
      $projectName = $config['vagrant_machine_name'];
      $friendlyProjectName = 'Drupal Eight Project';
    }

    // Get the project repo git URL
    if (!$updatingComposerProject) {
      $io->write('<info>Create an empty git project in Gitlab.</info>');
      $repURL = self::askForRepoURL($io);
    }
    else {
      $repURL = 'git@gitlab.utdev.com:unleashedtech/drupal-eight-project.git';
    }

    // Update the readme file
    if (!$updatingComposerProject) {
      self::changeReadme($projectName,$friendlyProjectName, $repURL);
    }

    // Tweak the Drupal VM configuration.
    if (!$updatingComposerProject) {
      $stagingSiteURL = self::askForStagingSiteURL($io);
      self::changeDrupalVMConfig($projectName, $stagingSiteURL);
    }

    // Create Foundation Subtheme.
    if (!$updatingComposerProject) {
      $themeName = self::askForThemeName($io);
      self::createFoundationSubtheme($themeName, $projectName . '.dev');
    }

    // Tweak the composer.json file.
    if (!$updatingComposerProject) {
      $packageName = $event->getComposer()->getPackage()->getName();
      $repoProjectName = self::askForRepoProjectName($io, $packageName);
      self::changeComposerJson($repoProjectName);
      if ($repoProjectName != $packageName) {
        $io->write('Update composer.lock file with new package name...');
        system('composer update nothing');
      }
    }

    // Bring up vagrant machine
    $vagrantStatus = self::vagrantUp($io);
    if (!$vagrantStatus) {
      return;
    }

    // Install Drupal
    self::createLocalDrupalSettings($io);
    self::installDrupal($io , '/var/www/' . $projectName . '/web');

    // TODO: Automate the gitlabci scripts configuration

    if (!$updatingComposerProject) {
      self::initGitRepo($io, $repURL);
    }

    // Reload vagrant to account for possibility of changing Vagrant ip during re-run.
    self::reloadVagrant($io);
    $io->write('<info>Complete!</info>');
    $io->write('');
    $io->write('<info>If all went well the site should be available at http://' . $projectName . '.dev </info>');
    $io->write('');

  }

  /**
   * Update the DrupalVM configuration for this project.
   *
   * @param string $projectName
   * @param string $stagingSiteURL
   */
  public static function changeDrupalVMConfig(string $projectName, string $stagingSiteURL = '') {
    $drupalVMConfig = file_get_contents(self::DRUPAL_VM_CONFIG_FILE);

    $replacements = [
      '/vagrant_machine_name:.*$/m' => 'vagrant_machine_name: ' . $projectName,
      '/vagrant_hostname:.*$/m' => 'vagrant_hostname: ' . $projectName . '.dev',
      '/vagrant_ip:.*$/m' => 'vagrant_ip: 192.168.88.'. rand(100, 200), // Try to avoid collisions between other vagrant machines.
      '/destination:.*$/m' => 'destination: /var/www/' . $projectName,
      '/drupal_composer_install_dir:.*$/m' => 'drupal_composer_install_dir: "/var/www/' . $projectName . '"',
    ];

    // Add staging file redirect if defined.
    $drupalNginxConfigExp = '/nginx_hosts:.*- server_name:.* server_name: "adminer.{{ vagrant_hostname }}"/sU';
    if ($stagingSiteURL) {
      $replacements[$drupalNginxConfigExp] = 'nginx_hosts:
  - server_name: "{{ vagrant_ip }} {{ drupal_domain }} www.{{ drupal_domain }}"
    root: "{{ drupal_core_path }}"
    is_php: true
    extra_parameters: |
        ## Try local files first, otherwise redirect to a development server.
        location ^~ /sites/default/files {
          try_files $uri @dev_redirect;
        }
        ## Development Server Redirect
        location @dev_redirect {
          rewrite ^ ' . $stagingSiteURL . '$request_uri? permanent;
        }

  - server_name: "adminer.{{ vagrant_hostname }}"';
    }
    else {
      $replacements[$drupalNginxConfigExp] = 'nginx_hosts:
  - server_name: "{{ vagrant_ip }} {{ drupal_domain }} www.{{ drupal_domain }}"
    root: "{{ drupal_core_path }}"
    is_php: true

  - server_name: "adminer.{{ vagrant_hostname }}"';
    }

    $drupalVMConfig = preg_replace(array_keys($replacements), $replacements, $drupalVMConfig);
    file_put_contents(self::DRUPAL_VM_CONFIG_FILE, $drupalVMConfig);
  }

  /**
   * Update composer.json file.
   *
   * @param string $repoProjectName
   */
  public static function changeComposerJson(string $repoProjectName) {
    $composerJSON = file_get_contents(self::COMPOSER_JSON_FILE);
    $replacements = [
      '/"name": .*$/m' => '"name": "' . $repoProjectName . '",',
    ];
    $composerJSON = preg_replace(array_keys($replacements), $replacements, $composerJSON, 1);
    file_put_contents(self::COMPOSER_JSON_FILE, $composerJSON);
  }

  /**
   * Update README.md file.
   *
   * @param string $projectName
   */
  public static function changeReadme(string $projectName, string $siteName, string $gitRepoUrl) {
    // Update values in project readme.
    $projectReadme = file_get_contents('PROJECT-README.md');
    $replacements = [
      'SITENAME' => $siteName,
      'PROJECTNAME' => $projectName,
      'GITREPOURL' => $gitRepoUrl,
    ];
    $projectReadme = str_replace(array_keys($replacements), $replacements, $projectReadme);
    file_put_contents('PROJECT-README.md', $projectReadme);

    // Remove create project readme and replace with project's readme.
    $fs = new Filesystem();
    $fs->rename('PROJECT-README.md', 'README.md', true);
  }


  /**
   * Generate a foundation subtheme.
   *
   * @param string $themeName
   */
  protected static function createFoundationSubtheme(string $themeName, string $devSiteUrl) {
    $placeholderThemeName = 'd8_foundation_placeholder';
    $fs = new Filesystem();

    // Replace themename in files.
    $filesToSearch = [
      '.bowerrc',
      'bower.json',
      'gulpfile.js'
    ];

    $drupalConfigFiles = self::getDrupalConfigFiles();
    $filesToSearch = array_merge($filesToSearch, $drupalConfigFiles, self::getSubthemeFiles());

    // Perform in content replacement.
    foreach ($filesToSearch as $fileName) {
      if (is_dir($fileName)) {
        continue;
      }
      $fileContents = file_get_contents($fileName);
      $fileContents = str_replace($placeholderThemeName, $themeName, $fileContents, $count);
      if ($count) {
        file_put_contents($fileName, $fileContents);
      }
    }

    // Replace sitename in gulpfile.js
    $fileContents = file_get_contents('gulpfile.js');
    $fileContents = str_replace('drupal-eight-project.dev', $devSiteUrl, $fileContents, $count);
    if ($count) {
      file_put_contents('gulpfile.js', $fileContents);
    }

    // Rename Drupal 8 config files.
    foreach ($drupalConfigFiles as $fileName) {
      $newName = str_replace($placeholderThemeName, $themeName, $fileName, $count);
      if ($count) {
        $fs->rename($fileName, $newName);
      }
    }

    // Rename theme folder
    $placeholderThemePath = 'web/themes/custom/' . $placeholderThemeName;
    $themePath = 'web/themes/custom/' . $themeName;

    $fs->rename($placeholderThemePath, $themePath);

    // Rename theme files
    $fs->rename($themePath . '/' . $placeholderThemeName . '.info.yml', $themePath . '/' . $themeName . '.info.yml');
    $fs->rename($themePath . '/' . $placeholderThemeName . '.libraries.yml', $themePath . '/' . $themeName . '.libraries.yml');
    $fs->rename($themePath . '/' . $placeholderThemeName . '.theme', $themePath . '/' . $themeName . '.theme');
    $fs->rename($themePath . '/css/' . $placeholderThemeName . '.css', $themePath . '/css/' . $themeName . '.css');
    $fs->rename($themePath . '/scss/' . $placeholderThemeName . '.scss', $themePath . '/scss/' . $themeName . '.scss');
    $fs->rename($themePath . '/js/' . $placeholderThemeName . '.js', $themePath . '/js/' . $themeName . '.js');

  }

  /**
   * Provides and array of all Drupal 8 config files
   *
   * @return array
   */
  protected static function getDrupalConfigFiles() : array {
    $configDirectory = 'config/sync';
    $drupalConfigFiles = array_diff(scandir($configDirectory), array('..', '.'));
    array_walk($drupalConfigFiles, function(&$filename) use ($configDirectory) { $filename = $configDirectory . '/' . $filename;});
    return $drupalConfigFiles;
  }

  /**
   * Provides and array of all subtheme files
   *
   * @return array
   */
  protected static function getSubthemeFiles() : array {
    $themeDirectory = 'web/themes/custom/d8_foundation_placeholder';
    $themeFiles = array_diff(scandir($themeDirectory), array('..', '.'));
    array_walk($themeFiles, function(&$filename) use ($themeDirectory) { $filename = $themeDirectory . '/' . $filename;});
    $themeFiles[] = $themeDirectory . '/js/d8_foundation_placeholder.js';
    $themeFiles[] = $themeDirectory . '/scss/d8_foundation_placeholder.scss';
    return $themeFiles;
  }
  /**
   * Get the theme name from the user.
   *
   * @param \Composer\IO\IOInterface $io
   */
  protected static function askForThemeName(IOInterface $io) {
    $themeNameValidator = function ($projectName) {
      $projectName = trim($projectName);

      if (!preg_match('/^[a-z0-9_]+$/', $projectName)) {
        throw new \Exception('Not a valid theme name.');
      }

      return $projectName;
    };

    $themeName = $io->askAndValidate('<info>What is the name of your zurb foundation theme </info>[<comment>my_foundation_theme</comment>]? ', $themeNameValidator, null, 'my_foundation_theme');

    return $themeName;
  }

  /**
   * Get the friendly project name from the user.
   *
   * @param \Composer\IO\IOInterface $io
   */
  protected static function askForFriendlyProjectName(IOInterface $io) {
    $default = 'UT Blog';

    $projectNameValidator = function ($projectName) {
      $projectName = trim($projectName);

      if (empty($projectName)) {
        throw new \Exception('Not a valid project name.');
      }

      return $projectName;
    };

    $projectName = $io->askAndValidate('<info>What is the friendly name of your project </info>[<comment>example: "UT Blog"</comment>]? ', $projectNameValidator, null, $default);

    return $projectName;
  }

  /**
   * Get the project name from the user.
   *
   * @param \Composer\IO\IOInterface $io
   */
  protected static function askForProjectName(IOInterface $io) {

    $config = Yaml::parse(file_get_contents(self::DRUPAL_VM_CONFIG_FILE));
    $default = $config['vagrant_machine_name'];

    $projectNameValidator = function ($projectName) {
      $projectName = trim($projectName);

      if (!preg_match('/^[a-z0-9_-]+$/', $projectName)) {
        throw new \Exception('Not a valid project name.');
      }

      return $projectName;
    };

    $projectName = $io->askAndValidate('<info>What is the name of your project </info>[<comment>' . $default . '</comment>]? ', $projectNameValidator, null, $default);

    return $projectName;
  }

  /**
   * Get the project repo url from the server.
   *
   * @param \Composer\IO\IOInterface $io
   */
  protected static function askForRepoURL(IOInterface $io) {

    $repURLValidator = function ($repoURL) {
      $repoURL = trim($repoURL);

      if (empty($repoURL)) {
        throw new \Exception('Please provide a valid git project url.');
      }

      return $repoURL;
    };

    $repoURL = $io->askAndValidate('<info>What is the git repo url of your project?</info>[<comment>example: git@gitlab.utdev.com:evapco/evapco-drupal8.git</comment>]? ', $repURLValidator);

    return $repoURL;
  }

  /**
   * Get the project's repo name from the user.
   *
   * @param \Composer\IO\IOInterface $io
   */
  protected static function askForRepoProjectName(IOInterface $io, $default = 'group/drupal-eight') {
    $repoProjectNameValidator = function ($projectName) {
      $projectName = trim($projectName);
      if (!preg_match('/^[a-z0-9_-]+\/[a-z0-9_-]+$/', $projectName)) {
        throw new \Exception('Not a valid project name. Make sure that both the group and project are provided. Example: group/project');
      }
      return $projectName;
    };

    $repoProjectName = $io->askAndValidate('<info>What is the name of your project (including group) in gitlab </info>[<comment>' . $default . '</comment>]? ', $repoProjectNameValidator, null, $default);

    return $repoProjectName;
  }

  /**
   * Get the staging site url from the user.
   *
   * @param \Composer\IO\IOInterface $io
   */
  protected static function askForStagingSiteURL(IOInterface $io) {
    $default = '';
    $urlValidator = function ($url) use ($default){
      $url = trim($url);

      if ($url === $default) {
        return $url;
      }

      if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        throw new \Exception('Not a valid url.');
      }

      return $url;
    };

    $url = $io->askAndValidate('<info>What is the URL of the staging site or the site that should be used to load the image assets from. Include http:// or https://. Leave blank to not load images from the staging site.</info> [<comment></comment>] ', $urlValidator, null, $default);

    return $url;
  }

  /**
   * Check if vagrant is installed.
   *
   * TODO: Check to see if this works on Windows.
   *
   * @param \Composer\IO\IOInterface $io
   * @return bool
   */
  protected static function isVagrantInstalled(IOInterface $io){
    $returnValue = null;
    $io->write('Checking if vagrant is installed...');
    system('vagrant --version', $returnValue);
    if ($returnValue == 0) {
      return true;
    }
    else {
      $io->writeError('<error>Vagrant is not installed.</error> Please install the latest version of Vagrant and run <comment>composer run-script post-create-project-cmd</comment> to continue.');
      return false;
    }
  }

  /**
   * Bring up Vagrant box.
   *
   * TODO: Check to see if this works on Windows.
   *
   * @param \Composer\IO\IOInterface $io
   * @return bool
   */
  protected static function vagrantUp(IOInterface $io){
    // Check to see if Vagrant is installed.
    if (!self::isVagrantInstalled($io)) {
      return false;
    }


    $returnValue = null;
    $io->write('Bringing up Vagrant box...');
    system('vagrant up', $returnValue);

    if ($returnValue == 0) {
      return true;
    }
    else {
      $io->writeError('<error>Unable to bring up Vagrant box.</error> Please review output then run <comment>composer run-script post-create-project-cmd</comment> to continue.');
      return false;
    }
  }

  /**
   * Commit project to git repo
   *
   * @param \Composer\IO\IOInterface $io
   * @return bool
   */
  protected static function initGitRepo(IOInterface $io, string $gitRepoUrl){
    $returnValue = null;

    $io->write('<info>Init and create git repo...</info>');
    system('git init', $returnValue);

    if ($returnValue == 0) {
      system('git remote add origin ' . $gitRepoUrl, $returnValue);
    }

    if ($returnValue == 0) {
      system('git add .', $returnValue);
    }


    if ($returnValue == 0) {
      system('git commit -m "Initial Project Creation (done using drupal-eight-project)"', $returnValue);
    }

    if ($returnValue == 0) {
      system('git push -u origin master', $returnValue);
    }

    if ($returnValue != 0){
      $io->writeError('<error>Unable to initialize your git repo. You may have to do this manually.</error>');
      return false;
    }

    return true;
  }

  /**
   * Reload the vagrant box.
   *
   * TODO: Check to see if this works on Windows.
   *
   * @param \Composer\IO\IOInterface $io
   * @return bool
   */
  protected static function reloadVagrant(IOInterface $io){
    // Check to see if Vagrant is installed.
    if (!self::isVagrantInstalled($io)) {
      return false;
    }

    $returnValue = null;
    $io->write('Reloading the Vagrant box...');
    system('vagrant reload', $returnValue);

    if ($returnValue == 0) {
      return true;
    }
    else {
      $io->writeError('<error>Unable to reload the Vagrant box.</error> Please review output then run <comment>composer run-script post-create-project-cmd</comment> to continue.');
      return false;
    }
  }

  /**
   * Install Drupal using drush inside of the vagrant machine.
   *
   * @param \Composer\IO\IOInterface $io
   * @return bool
   */
  protected static function installDrupal(IOInterface $io, string $drupalInstallPath){
    $returnValue = null;
    $io->write('<info>Installing Drupal...</info>');

    $fs = new Filesystem();
    $root = self::getDrupalRoot(getcwd());
    $settingsFile = $root . '/sites/default/settings.php';

    // Comment out redis configuration as it interferes with installation
    $settingsFileContents = file_get_contents($settingsFile);
    $replacements = [
      '// START - COMMENT OUT THIS SECTION TO DISABLE REDIS CACHING' => '/*// START - COMMENT OUT THIS SECTION TO DISABLE REDIS CACHING',
      '// END - COMMENT OUT THIS SECTION TO DISABLE REDIS CACHING' => '*/// END - COMMENT OUT THIS SECTION TO DISABLE REDIS CACHING',
    ];
    $editedSettingsFileContents = str_replace(array_keys($replacements), $replacements, $settingsFileContents);
    file_put_contents($settingsFile, $editedSettingsFileContents);

    system('vagrant ssh --command="cd ' . $drupalInstallPath . ' && drush site-install config_installer config_installer_sync_configure_form.sync_directory=../config/sync --yes --account-pass=admin"', $returnValue);

    // Undo the file permission change on the settings file and default directory.
    // These are preventing us from editing the files after the fact. Their
    // change causes the file changes to not be committed via git. We might
    // want to confirm these permissions.
    if ($fs->exists($settingsFile)){
      $fs->chmod($settingsFile, 0664);
    }
    if ($fs->exists($root . '/sites/default')){
      $fs->chmod($root . '/sites/default', 0775);
    }

    // Restore settings.php contents.
    file_put_contents($settingsFile, $settingsFileContents);

    // Install and configure npm and node requirements
    if ($returnValue == 0) {
      $io->write('<info>Installing node dependencies...</info>');
      system('vagrant ssh --command="cd ' . $drupalInstallPath . ' && cd .. && yarn install || npm install"', $returnValue);
    }

    if ($returnValue == 0) {
      $io->write('<info>Installing bower dependencies...</info>');
      system('vagrant ssh --command="cd ' . $drupalInstallPath . ' && cd .. && bower install"', $returnValue);
    }

    if ($returnValue == 0) {
      $io->write('<info>Building front end assets...</info>');
      system('vagrant ssh --command="cd ' . $drupalInstallPath . ' && cd .. && gulp build"', $returnValue);
    }

    if ($returnValue == 0) {
      return true;
    }
    else {
      $io->writeError('<error>Unable to install Druap.</error> Please review output then run <comment>composer run-script post-create-project-cmd</comment> to continue.');
      return false;
    }
  }

  /**
   * Create settings.local.php file based off of example-dev.settings.local.php
   *
   * @param \Composer\IO\IOInterface $io
   */
  protected static function createLocalDrupalSettings(IOInterface $io){
    $fs = new Filesystem();
    $root = self::getDrupalRoot(getcwd());
    // Prepare the settings.local.php  file for installation
    if (!$fs->exists($root . '/sites/default/settings.local.php') and $fs->exists($root . '/sites/default/example-dev.settings.local.php')) {
      $fs->copy($root . '/sites/default/example-dev.settings.local.php', $root . '/sites/default/settings.local.php');
      $fs->chmod($root . '/sites/default/settings.local.php', 0666);
      $io->write("Create a sites/default/settings.local.php file with chmod 0666");
    }
  }

  protected static function getDrupalRoot($project_root) {
    return $project_root . '/web';
  }

}
