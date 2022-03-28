<?php

namespace Drupal\Tests\raven\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Raven and CSP modules.
 *
 * @group raven
 */
class CspTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['csp', 'raven'];

  /**
   * Tests Sentry browser client configuration UI.
   */
  public function testRavenJavascriptConfig() {
    $admin_user = $this->drupalCreateUser([
      'administer csp configuration',
      'administer site configuration',
      'send javascript errors to sentry',
    ]);

    $this->drupalLogin($admin_user);

    $this->drupalPostForm('admin/config/development/logging', [
      'raven[js][javascript_error_handler]' => TRUE,
      'raven[js][public_dsn]' => 'https://a@domain.test/1',
    ], $this->t('Save configuration'));

    $this->drupalPostForm('admin/config/system/csp', [
      'report-only[reporting][handler]' => 'raven',
    ], $this->t('Save configuration'));

    $this->assertSession()->responseHeaderEquals('Content-Security-Policy-Report-Only', "script-src 'self'; style-src 'self'; frame-ancestors 'self'; report-uri https://domain.test/api/1/security/?sentry_key=a");

    $this->drupalPostForm('admin/config/system/csp', [
      'report-only[directives][connect-src][enable]' => TRUE,
      'report-only[directives][connect-src][base]' => 'self',
    ], $this->t('Save configuration'));

    $this->assertSession()->responseHeaderEquals('Content-Security-Policy-Report-Only', "connect-src 'self' https://domain.test/api/1/store/; script-src 'self'; style-src 'self'; frame-ancestors 'self'; report-uri https://domain.test/api/1/security/?sentry_key=a");
  }

}
