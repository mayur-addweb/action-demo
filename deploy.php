<?php
namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

require 'recipe/common.php';

// Project name and repository
set('application', 'vscpa');
set('repository', 'git@gitlab.utdev.com:vscpa/drupal8.git');

// Shared files/dirs between deploys
set('shared_dirs', [
  'private',
  'web/sites/default/files',
  'config/sso/cert',
]);
set('shared_files', [
  'web/sites/default/settings.local.php',
  'web/robots.txt',
]);

// Drupal 8 Writable dirs
set('writable_dirs', [
  'private',
  'web/sites/default/files',
]);

// Other important paths
set('bin/drush', '{{bin/php}} ../vendor/bin/drush');
set('bin/bower', './node_modules/.bin/bower');
set('bin/gulp', './node_modules/.bin/gulp');
set('backups', '{{deploy_path}}/shared/backups');
set('web_root', '{{release_path}}/web');
set('current_web_root', '{{deploy_path}}/current/web');

// Don't phone home
set('allow_anonymous_stats', false);

// Hosts
host('10.50.20.12')
  ->stage('staging')
  ->roles('app')
  ->user('utadmin')
  ->port(52222)
  ->set('deploy_path', '/srv/vscpa.utstaging.com');

host('10.50.30.127', '10.50.30.128')
  ->stage('production')
  ->roles('app')
  ->user('utadmin')
  ->port(52222)
  ->set('deploy_path', '/srv/vscpa.com');

// Options
option('override-config', null, InputOption::VALUE_NONE, 'Override any configuration changes which might be present');

// Tasks
task('deploy', [
  'deploy:info',             // Notify the user of what's about to happen
  'deploy:config:flag',      // Determine whether an automated config import should be done
  'deploy:prepare',          // Prepare server for deployment
  'deploy:lock',             // Lock deployment so that nobody else can deploy right now
  'deploy:release',          // Create new release folder
  'deploy:update_code',      // Clone project into new release
  'deploy:shared',           // Symlink shared files/dirs into new release
  'deploy:hash',             // Write the release hash into release.txt
  'deploy:vendors',          // Install Composer dependencies
  'deploy:config:check',     // Check for config changes and abort if needed
  'database:backup:create',  // Create a database backup
  'deploy:vendors:yarn',     // Install Yarn dependencies
  'deploy:vendors:bower',    // Install bower dependencies
  'deploy:assets:build',     // Run `gulp build` tasks
  'deploy:cache:clear',      // Clear Drupal cache
  'database:update',         // Run database updates
  'deploy:config:import',    // Import configuration
  'deploy:symlink',          // Switch the `current` symlink, making this release live
  'deploy:unlock',           // Unlock deployments
  'cleanup',                 // Clean up old releases
  'database:backup:clean'    // Remove old database backups
]);

task('deploy:hash', function() {
    run('cd {{release_path}} && echo $(git rev-parse --short=8 HEAD) > release.txt');
})->desc('Write the release hash into release.txt')->setPrivate();

task('deploy:config:flag', function() {
  $stage = input()->getArgument('stage');
  if ($stage == 'production' && !input()->hasOption('override-config')) {
    set('skip_configuration_import', true);
    output()->writeln('Skipping automatic configuration import. Please manually run "deploy:config:import" later.');
  }
})->setPrivate()->once();

task('deploy:vendors:yarn', function() {
  run('cd {{release_path}} && yarn install');
})->desc('Install Yarn dependencies');

task('deploy:vendors:bower', function() {
  run('cd {{release_path}} && {{bin/bower}} install');
})->desc('Install Bower dependencies');

task('deploy:vendors:libs', function() {
  run('cd {{web_root}} && {{bin/drush}} webform:libraries:download');
})->desc('Download webform libraries locally to avoid CDN');

task('deploy:assets:build', function() {
  run('cd {{release_path}} && {{bin/gulp}} build');
})->desc('Run gulp build tasks');

task('deploy:cache:clear', function () {
  within(get('web_root'), function() {
    run('{{bin/drush}} cr');
  });
})->desc('Clear cache')->once();

task('deploy:config:check', function() {
  if (!test('[ -d {{current_web_root}} ]')) {
    output()->writeln('No current deployment exists');
    return;
  }

  within(get('current_web_root'), function() {
    if(test('[[ $(../vendor/bin/drush config:status --format=list | wc -l) == 0 ]]')) {
      output()->writeln('No configuration changes were detected');
      set('configuration_has_changed', false);
    } else {
      set('configuration_has_changed', true);

      output()->writeln('Uncommitted configuration changes have been detected:');
      output()->writeln(run('../vendor/bin/drush config:status --ansi'));

      if (input()->getOption('override-config')) {
        output()->writeln('<error>!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!</error>');
        output()->writeln('<error>!!! THESE CHANGES WILL BE OVERRIDDEN !!!</error>');
        output()->writeln('<error>!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!</error>');
        output()->writeln('');
        output()->writeln('You have 10 seconds to abort the deployment...');
        sleep(10);
        output()->writeln('Deployment is proceeding.');
      } else {
        throw new \Exception(parse('You should export (./vendor/bin/dep sync:config {{stage}}) and commit these back before retrying the deployment, or re-run the deployment with the --override-config option. This deployment will now abort.'));
      }
    }
  });
})->desc('Check whether configuration changes have been made')->once();

task('deploy:config:import', function() {
  if (get('skip_configuration_import', false)) {
    output()->writeln('Skipping configuration import...');
    return;
  }

  // Ensure that we've checked the current state
  if (!has('configuration_has_changed')) {
    invoke('deploy:config:check');
  }

  within(get('web_root'), function() {
    run('{{bin/drush}} cim -y');
  });
})->desc('Import the configuration, overriding any changes')->once();

task('database:update', function () {
  within(get('web_root'), function () {
    run('{{bin/drush}} updb -y', [
      'timeout' => 1800,
    ]);
  });
})->desc('Run database updates')->once();

task('database:backup:create', function() {
  // Ensure that the backup directory exists
  run('mkdir -p {{backups}}');

  // Create the backup file
  within(get('web_root'), function() {
    $date = date('Y-m-d--H-i-s');
    $filename = "{{application}}--$date.sql.gz";
    run("{{bin/drush}} sql-dump --gzip > {{backups}}/$filename");
  });
})->desc('Create a database backup')->once();

task('database:backup:clean', function() {
  // Clean up old DB dumps keep 7 (7*24*60=10080) days worth, leave at least 20
  run('find {{backups}} -type f -printf \'%T@ %p\n\' -mmin +10080 | sort -nr | tail -n +21 | awk \'{print $2}\' | xargs rm -f');
})->desc('Clean old database backups')->once();

task('database:backup:download', function() {
    $latestBackup = run('find {{backups}} -type f -printf \'%T@ %p\n\' | sort -n | tail -1 | cut -f2- -d" "');
    $stage = input()->getArgument('stage');
    download($latestBackup, "{{application}}-$stage--latest.sql.gz", ['--copy-links']);
})->desc('Download the latest backup')->once();


task('sync:config', function() {
    if (!askConfirmation(parse('This will replace all .yml files in your local config/sync directory with copies from {{stage}}. Would you like to proceed?'))) {
        return;
    }

    within(get('current_web_root'), function() {
        // Create a temp directory
        $tmpdir = trim(run('mktemp -d -t config-sync.XXXXXX'));
        if (empty($tmpdir)) {
            throw new \RuntimeException('Failed to create temp directory');
        }

        // Export configs there
        run("{{bin/drush}} config:export --destination=$tmpdir/sync");
        // Remove local configs
        runLocally('rm -rf config/sync/*.yml');
        // Sync them to your local machine
        download($tmpdir.'/sync', 'config/', ['--delete']);
        // Remove temp directory
        run("rm -rf $tmpdir");
    });
})->desc('Pull down configs from server and replace local ones')->once();

task('sync:files:default', function() {
    download('{{deploy_path}}/shared/web/sites/default/files', 'web/sites/default/', ['--delete', '--omit-dir-times', '--exclude /styles/*']);
})->desc('Sync files down from the sites/default/files directory')->once();

task('sync:files:private', function() {
    download('{{deploy_path}}/shared/private', '.', ['--delete', '--omit-dir-times', '--exclude /styles/*']);
})->desc('Sync private files down')->once();

task('sync:all', [
    'database:backup:download',
    'sync:files:default',
    'sync:files:private',
]);

// If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
