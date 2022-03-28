Raven Sentry integration
========================

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usage
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

Raven module provides integration with [Sentry](https://sentry.io/), an open
source error tracking platform.

This module can capture all (or a subset of) Drupal log messages as well as
errors that typically are not logged by Drupal: fatal PHP errors such as memory
limit exceeded, fatal JavaScript errors, and exceptions thrown by Drush
commands, and provides a full stacktrace and customizable metadata for each
event.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/raven

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/raven


REQUIREMENTS
------------

Dependencies are defined in the composer.json file.


INSTALLATION
------------

Run `composer require drupal/raven` to install this module and its dependencies.

You can use the Sentry hosted service or install the Sentry app on your own
infrastructure (e.g. using Docker).


CONFIGURATION
-------------

This module logs errors to Sentry in a few ways:

 * Register a Drupal logger implementation (for uncaught exceptions, PHP errors,
   and Drupal log messages),
 * Record Sentry breadcrumbs for system events,
 * Register an error handler for Drush command exceptions,
 * Register an error handler for fatal errors, and
 * Handle JavaScript exceptions via Sentry browser client (if user has the "Send
   JavaScript errors to Sentry" permission).
 * Provide a handler to optionally send Content Security Policy (CSP) reports to
   Sentry (if [CSP module](https://www.drupal.org/project/csp) is installed).

You can choose which events you want to capture by visiting the Raven
configuration page at admin/config/development/logging and enabling desired
error handlers and selecting error levels.

Additional customizations can be performed by implementing hooks:

 * `hook_raven_options_alter()`: Modify the Raven client configuration.
 * `hook_raven_filter_alter()`: Modify or ignore Drupal log events.
 * `hook_raven_breadcrumb_alter()`: Modify or ignore Sentry breadcrumbs.

The logger records a Sentry breadcrumb for each log message. If sensitive debug
data is recorded in a breadcrumb, and a log message is later captured by Sentry,
this could result in sending sensitive data to the Sentry server. To modify or
suppress breadcrumbs, you may implement hook_raven_breadcrumb_alter().

The Sentry browser client configuration can be modified via the
`$page['#attached']['drupalSettings']['raven']['options']` object in PHP or the
`drupalSettings.raven.options` object in JavaScript. Sentry callbacks can be
configured via custom JavaScript (using library weight to ensure your custom
configuration is added early enough), for example:

```
drupalSettings.raven.options.beforeSend = function(event) {
  var isUnsupportedBrowser = navigator.userAgent.match(/Trident.*rv:11\./);
  if (isUnsupportedBrowser) {
    // Do not log the event to Sentry.
    return null;
  }
  else {
    // Do not alter the event.
    return event;
  }
};
```

If desired, the SENTRY_DSN, SENTRY_ENVIRONMENT and SENTRY_RELEASE environment
variables can be used to configure this module, overriding the corresponding
settings at admin/config/development/logging.


USAGE
-----

Assuming the applicable PHP log levels have been enabled at
admin/config/development/logging, Drupal's exception and error handlers will
send events to Sentry, and developers can use the normal Drupal (or PHP) APIs to
send events to Sentry:

```
try {
  throw new \Exception('Oopsie');
}
catch (\Exception $e) {
  // Capture event via Drupal logger:
  \Drupal::logger('oops')->error($e);
  // Capture event via Drupal watchdog (which calls logger internally):
  watchdog_exception('oops', $e);
  // Capture event via PHP user notice:
  trigger_error($e);
  // Capture event via Drupal exception handler (or Drush console error event):
  throw $e;
}
```

In addition, the Raven_Client object is available to developers at:
`\Drupal::service('logger.raven')->client`

You can find documentation for the (now deprecated) Raven_Client at:
 * https://docs.sentry.io/clients/php/usage/#reporting-exceptions
 * https://docs.sentry.io/clients/php/config/#sentry-php-request-context


TROUBLESHOOTING
---------------

If the client is configured incorrectly (e.g. wrong Sentry DSN) it should fail
silently. Sentry browser client may log an error in the browser console.

If you have code that generates thousands of PHP notices - for example,
processing a large set of data, with one notice for each item - you may find
that storing and sending the errors to Sentry requires a large amount of memory
and execution time, enough to exceed your configured `memory_limit` and
`max_execution_time` settings. This could result in a stalled or failed request.
A workaround for this case would be to disable sending notice-level events to
Sentry, or, for long-running processes, to periodically call
`\Drupal::service('logger.raven')->flush()`.


DRUSH INTEGRATION
-----------------

The `drush raven:captureMessage` command sends a message to Sentry.

If the Drush error handler configuration option is enabled, exceptions thrown by
Drush commands will be sent to Sentry.


MAINTAINERS
-----------

This module is not affiliated with Sentry. It was originally created by
[nodge](https://www.drupal.org/u/nodge) and is now developed by
[mfb](https://www.drupal.org/u/mfb).

Maintenance of this module is supported by the Electronic Frontier Foundation.

 * Build status: https://www.drupal.org/node/2599354/qa
