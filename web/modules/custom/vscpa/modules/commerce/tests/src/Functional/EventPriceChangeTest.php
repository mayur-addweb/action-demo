<?php

namespace Drupal\Tests\vscpa_commerce\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce_product\Functional\ProductBrowserTestBase;

/**
 * Tests the date based pricing changes for events and sessions.
 *
 * @group vscpa_commerce
 */
class EventPriceChangeTest extends ProductBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'vscpa_commerce',
  ];

  /**
   * The timezone used for user (test) and events.
   */
  const TIMEZONE = 'America/New_York';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Set the user timezone to EST to match the expected server setup.
    $config = $this->container->get('config.factory');
    $config->getEditable('system.date')
      ->set('timezone.user.configurable', 1)
      ->set('timezone.default', self::TIMEZONE)
      ->save();
    // Log in a basic authenticated user.
    $end_user = $this->drupalCreateUser([
      'access content',
      'view commerce_product',
      'view own unpublished commerce_product',
    ]);
    $this->drupalLogin($end_user);
  }

  /**
   * Test the page text contains correct pricing before early bird expires.
   */
  public function testBeforeEarlyBirdExpiration() {
    $start = new DrupalDateTime('+60 Days', self::TIMEZONE);
    $end = new DrupalDateTime('+62 Days', self::TIMEZONE);
    $expiration = new DrupalDateTime('+61 Days', self::TIMEZONE);
    $variations = [
      [
        'start_date' => new DrupalDateTime('+60 Days', self::TIMEZONE),
        'end_date' => new DrupalDateTime('+61 Days', self::TIMEZONE),
        'early_bird_expiry' => new DrupalDateTime('+30 Days', self::TIMEZONE),
      ],
    ];
    $product = $this->createEventRegistrationProduct($start, $end, $expiration, $variations);
    $this->drupalGet($product->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($product->label());
    // Check for early bird pricing.
    $this->assertSession()->pageTextContains('earlybird');
    $this->assertSession()->pageTextContains('$249.99');
    $this->assertSession()->pageTextContains('$209.99');
    // Check for expected early bird end date.
    $this->assertSession()->pageTextContains('End date');
    $this->assertSession()->pageTextContains((new DrupalDateTime('+30 Days', self::TIMEZONE))->format('Y-m-d'));
    // Make sure regular pricing isn't showing.
    $this->assertSession()->pageTextNotContains('regular');
    $this->assertSession()->pageTextNotContains('$269.99');
    $this->assertSession()->pageTextNotContains('$229.99');
  }

  /**
   * Creates an event product.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The event start date.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The event end date.
   * @param \Drupal\Core\Datetime\DrupalDateTime $expiration
   *   The event registration expiration date.
   * @param array $variations
   *   An array of variations where each is an array with these DrupalDateTimes:
   *     'start_date' The start date this variation applies to.
   *     'end_date' The end date this variation applies to.
   *     'early_bird_expiry' The date early bird pricing expires.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An event registration product.
   */
  protected function createEventRegistrationProduct(DrupalDateTime $start, DrupalDateTime $end, DrupalDateTime $expiration, array $variations) {
    return $this->createEntity('commerce_product', [
      'title' => $this->randomString(),
      'type' => 'event_registration',
      'stores' => [$this->store],
      'field_event' => $this->createEntity('node', [
        'type' => 'event',
        'title' => $this->randomString(),
        'field_event_expiry' => $expiration->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'field_dates_times' => [
          [
            'value' => $start->format(DATETIME_DATE_STORAGE_FORMAT),
            'end_value' => $end->format(DATETIME_DATE_STORAGE_FORMAT),
          ],
        ],
      ]),
      'variations' => array_map(function ($v) {
        return $this->createEntity('commerce_product_variation', [
          'type' => 'event_registration',
          'title' => $this->randomString(),
          'sku' => $this->randomString(),
          'status' => 1,
          'applies_to_date_range' => [
            'value' => $v['start_date']->format(DATETIME_DATE_STORAGE_FORMAT),
            'end_value' => $v['end_date']->format(DATETIME_DATE_STORAGE_FORMAT),
          ],
          'field_early_bird_expiry' => [
            'value' => $v['early_bird_expiry']->format(DATETIME_DATETIME_STORAGE_FORMAT),
          ],
          'price' => [
            'number' => '269.99',
            'currency_code' => 'USD',
          ],
          'field_price_early' => [
            'number' => '249.99',
            'currency_code' => 'USD',
          ],
          'field_price_member' => [
            'number' => '229.99',
            'currency_code' => 'USD',
          ],
          'field_price_member_early' => [
            'number' => '209.99',
            'currency_code' => 'USD',
          ],
        ]);
      }, $variations),
    ]);
  }

}
