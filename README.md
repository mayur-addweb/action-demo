# VSCPA Drupal 8 Site Built on Drupal 8

## Server Requirements

* PHP >= 7.0 (Review the [system requirement on drupal.org](https://www.drupal.org/docs/7/system-requirements/php))
* [Composer](https://getcomposer.org/)
* nodejs >= 6.x (and Yarn)

## Installation for local development

This project has been configured for local development using Vagrant, Ansible DrupalVM, and Composer

### Prerequisites

Ensure you have the following installed on your **local** development machine.

* [Vagrant >=  1.8.6](https://www.vagrantup.com/)
* [VirtualBox >= 5.1.10](https://www.vagrantup.com/docs/virtualbox/)
* [vagrant-hostmanager](https://github.com/smdahlen/vagrant-hostmanager) plugin
* [PHP >= 7.1](http://php.net/manual/en/install.php)
* [Install composer globally](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) on your host machine.


### Setting up Local Development Environment for the first time

Clone the repo

```
git clone git@gitlab.utdev.com:vscpa/drupal8.git && cd vscpa
```

Install the project with composer to download drupalvm.

```bash
composer install
```

> Note if you are on Windows, or if your host does not meet the same requirements as the project, you may have to use `composer install --ignore-platform-reqs --no-autoloader`.

Start the Vagrant box.

```bash
vagrant up
````

Login to the Vagrant box to run the yarn and gulp commands.

```bash
vagrant ssh
```

Fully install all of the composer managed assets (and create an autoloader this time)

```bash
composer install
```

Install nodeJS dependencies using Yarn.

```bash
yarn install
````

Install frontend JS dependencies.

```
bower install
````

Compile the theme assets.

```bash
gulp build
````

Exit vagrant if still logged into the vagrant box.

```bash
exit
```

Create a settings.local.php by copying the example. This will define the database credentials that drupal needs to run.

```bash
cp ./web/sites/default/example-dev.settings.local.php ./web/sites/default/settings.local.php
```

Comment out the redis configuration in the `./web/sites/default/settings.php` in order to initially import the database. This can be uncommented after you local site is set up.

Import the database and rsync the files from the staging site by running the following on your local host (not logged into vagrant).

```bash
./scripts/local--sync-from-staging.sh
```

Revert the changes (the commenting out of redis) made in a previous step.

```bash
git checkout -- web/sites/default/settings.php
```

Your local development site can be found at (http://vscpa.test)


See http://dashboard.vscpa.test for more information on the development box.

### Troubleshooting / Notes

#### Vagrant up fails

* Try `vagrant reload --provision`

* You may want to review the ["gotchas" defined in DrupalVM's docs](http://docs.drupalvm.com/en/latest/other/windows/) if
using a Windows host.

> Note: you can override any DrupalVM configuration for your local installation by creating a `./config/local.config.yml`
file and defining your overrides there. See [DrupalVM's docs on overriding configurations](http://docs.drupalvm.com/en/latest/other/overriding-configurations/).

If you run into any hickups or see any errors while the Vagrant box is
provisioning, refer to the [DrupalVM documentation](http://docs.drupalvm.com/)


## Notes:


With `composer require ...` you can download new dependencies to your 
installation.

```
composer require drupal/address:^1.0
```

The `composer create-project` command passes ownership of all files to the 
project that is created. You should create a new git repository, and commit 
all files not excluded by the .gitignore file.

## What does the template do?

When installing the given `composer.json` some tasks are taken care of:

* Drupal will be installed in the `web`-directory.
* Autoloader is implemented to use the generated composer autoloader in `vendor/autoload.php`,
  instead of the one provided by Drupal (`web/vendor/autoload.php`).
* Modules (packages of type `drupal-module`) will be placed in `web/modules/contrib/`
* Theme (packages of type `drupal-theme`) will be placed in `web/themes/contrib/`
* Profiles (packages of type `drupal-profile`) will be placed in `web/profiles/contrib/`
* Creates default writable versions of `settings.php` and `services.yml`.
* Creates `sites/default/files`-directory.
* Latest version of drush is installed locally for use at `vendor/bin/drush`.
* Latest version of DrupalConsole is installed locally for use at `vendor/bin/drupal`.

## Updating Drupal Core

This project will attempt to keep all of your Drupal Core files up-to-date; the 
project [drupal-composer/drupal-scaffold](https://github.com/drupal-composer/drupal-scaffold) 
is used to ensure that your scaffold files are updated every time drupal/core is 
updated. If you customize any of the "scaffolding" files (commonly .htaccess), 
you may need to merge conflicts if any of your modfied files are updated in a 
new release of Drupal core.

Follow the steps below to update your core files.

1. Run `composer update drupal/core --with-dependencies` to update Drupal Core and its dependencies.
1. Run `git diff` to determine if any of the scaffolding files have changed. 
   Review the files for any changes and restore any customizations to 
  `.htaccess` or `robots.txt`.
1. Commit everything all together in a single commit, so `web` will remain in
   sync with the `core` when checking out branches or running `git bisect`.
1. In the event that there are non-trivial conflicts in step 2, you may wish 
   to perform these steps on a branch, and use `git merge` to combine the 
   updated core files with your customized files. This facilitates the use 
   of a [three-way merge tool such as kdiff3](http://www.gitshah.com/2010/12/how-to-setup-kdiff-as-diff-tool-for-git.html). This setup is not necessary if your changes are simple; 
   keeping all of your modifications at the beginning or end of the file is a 
   good strategy to keep merges easy.

## Generate composer.json from existing project

With using [the "Composer Generate" drush extension](https://www.drupal.org/project/composer_generate)
you can now generate a basic `composer.json` file from an existing project. Note
that the generated `composer.json` might differ from this project's file.


## Deployments

Deployments to staging and production are automated using [Deployer](https://unleashed.atlassian.net/wiki/spaces/WTKB/pages/73007106/Automated+Deployments+with+Deployer).  Gitlab CI will automatically deploy the following branches:

 - `develop` - Deployed immediately to staging once changes are merged in here
 - `master` - Must be triggered manually from [the CI pipelines screen](https://gitlab.utdev.com/interimage/drupal-8/pipelines); deploys to production

**Do not attempt to manually deploy changes** using any methodology besides the Deployer CLI (and only if you're familiar with this process.  Also, keep in mind that **uncommitted changes will be lost upon deployment!**

### Configuration Management

Deployments to **staging** will **always overwrite** any configuration changes not present in `develop`.  This is because staging is supposed to reflect the current state of the repo and is not a place to perform development.

Deployments to **production** will **fail with a big red warning** if it detects somebody has made changes to previously-synced configs.  It will then be up to you to check the changes and decide whether they should be overwritten or synced to the repository. (However, if no config changes are present, the deployment will go through without issue)

You can check for configuration changes by running the following command from your local machine:

```bash
./vendor/bin/dep deploy:config:check production
```

If changes exist, you have two options to "fix" this:

1. Manually run that deployment with the `--override-config` flag which will overwrite all unsynced changes.
2. Run `./vendor/bin/dep sync:config production` locally to pull down those changes; commit them to git and then deploy them.

### Deployer CLI Commands

To "manually" deploy `develop` to staging, simply run this command from the root of the repository:

```bash
./vendor/bin/dep deploy staging --branch=develop --override-config
```

Or use this command for a deployment to production:

```bash
./vendor/bin/dep deploy production --branch=master
```

(Excluding the `--branch` option would deploy the current branch you're working on locally)

### Other Commands

Deployer is configured with other helpful commands to run tasks on servers, download database backups, and more.  For example:

```bash
./vendor/bin/dep list                             # List all the commands you can use
./vendor/bin/dep database:backup:create staging   # Create a new backup on staging
./vendor/bin/dep database:backup:download staging # Download the latest backup from staging
./vendor/bin/dep sync:all                         # Download the latest database backup and sync all files from staging
./vendor/bin/dep deploy:config:check production   # Check production for uncommitted config changes
./vendor/bin/dep sync:config production           # Replace your local config/sync files with a fresh export from production
```

## FAQ

### Should I commit the contrib modules I download?

Composer recommends **no**. They provide [argumentation against but also 
workrounds if a project decides to do it anyway](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md).

### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull 
request is often a better solution), you can do so with the 
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra 
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL to patch"
        }
    }
}
```
