<?php

$settings['hash_salt'] = '';

/**
 * @file
 * Local development override configuration feature.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/default/settings.local.php'. Then, go to the bottom of
 * 'sites/default/settings.php' and uncomment the commented lines that mention
 * 'settings.local.php'.
 *
 * If you are using a site name in the path, such as 'sites/example.com', copy
 * this file to 'sites/example.com/settings.local.php', and uncomment the lines
 * at the bottom of 'sites/example.com/settings.php'.
 */

$databases['default']['default'] = array (
  'database' => 'vscpacom',
  'username' => 'vscpacom',
  'password' => '',
  'prefix' => '',
  'host' => 'vscpa-datastore0',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

/**
 * Redis Servers
 */
$settings['redis.connection']['host'] = 'vscpa-redis0';

/**
 * Private file path:
 *
 * A local file system path where private files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * Note: Caches need to be cleared when this value is changed to make the
 * private:// stream wrapper available to the system.
 *
 * See https://www.drupal.org/documentation/modules/file for more information
 * about securing private files.
 */
$settings['file_private_path'] = '/srv/vscpa.com/shared/private';

/**
 * Google Tag manager config.
 */
$config['google_tag.settings']['container_id'] = 'GTM-NEEDFROMCLIENT';

// SimpleSAMLphp configuration.
# Provide universal absolute path to the installation.
if (is_dir('/srv/vscpa.com/current/vendor/simplesamlphp/simplesamlphp')) {
  $settings['simplesamlphp_dir'] = '/srv/vscpa.com/current/vendor/simplesamlphp/simplesamlphp';
}
else {
  // Local SAML path.
  if (is_dir(DRUPAL_ROOT . '/../vendor/simplesamlphp/simplesamlphp')) {
    $settings['simplesamlphp_dir'] = DRUPAL_ROOT . '/../vendor/simplesamlphp/simplesamlphp';
  }
}

// SimpleSAMLphp_auth module settings
$config['simplesamlphp_auth.settings'] = [
  // Basic settings.
  'activate'                => FALSE, // Enable or Disable SAML login.
  'auth_source'             => 'default-sp',
  'login_link_display_name' => 'Login with your SSO account',
  'register_users'          => TRUE,
  'debug'                   => FALSE,
  // Local authentication.
  'allow' => array(
    'default_login'         => TRUE,
    'set_drupal_pwd'        => TRUE,
    'default_login_users'   => '',
    'default_login_roles'   => array(
      'authenticated' => FALSE,
      'administrator' => 'administrator',
      'content_manager' => 'content_manager',
      'content_author' => 'content_author',
      'store_manager' => 'store_manager',
      'firm_administrator' => 'firm_administrator',
      'member' => 'member',
    ),
  ),
  'logout_goto_url'         => 'https://vscpa.com/',
  // User info and syncing.
  // 'unique_id' the value which is unique in the saml response coming from IDP.
  'unique_id'               => 'mail',
  'user_name'               => 'user_name',
  'mail_attr'               => 'mail',
  'sync' => array(
    'mail'      => TRUE,
  ),
];

// Sentry configuration
$config['raven.settings']['client_key'] = 'https://29b81994715f4157999bea5f68359b3f:25d45986574140b58b917b4708914224@sentry.utdev.com/9';
$config['raven.settings']['public_dsn'] = 'https://29b81994715f4157999bea5f68359b3f@sentry.utdev.com/9';
$config['raven.settings']['environment'] = 'production';
$config['raven.settings']['release'] = trim(exec('git log --pretty="%h" -n1 HEAD'));
$config['raven.settings']['log_levels'] = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 0, 7 => 0, 8 => 0];
