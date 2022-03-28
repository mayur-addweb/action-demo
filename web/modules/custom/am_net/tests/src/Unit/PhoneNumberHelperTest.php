<?php

namespace Drupal\Tests\phpunit_example\Unit;

use Drupal\am_net\PhoneNumberHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the PhoneNumberHelper class.
 */
class PhoneNumberHelperTest extends UnitTestCase {

  /**
   * Tests the validation/formatting of valid US numbers.
   *
   * @param string $input
   *   A valid phone number to test.
   *
   * @dataProvider provideValidNumbers
   */
  public function testValidNumbers(string $input) {
    $helper = new PhoneNumberHelper();

    $this->assertSame('410-864-8980', $helper->validateAndFormatPhoneNumber($input));
  }

  /**
   * Tests the validation/formatting of invalid US numbers.
   *
   * @param string $input
   *   An invalid phone number to test.
   *
   * @dataProvider provideInvalidNumbers
   */
  public function testInvalidNumbers(string $input) {
    $helper = new PhoneNumberHelper();

    $this->assertNull($helper->validateAndFormatPhoneNumber($input));
  }

  /**
   * Provides valid US phone numbers.
   */
  public function provideValidNumbers() {
    yield ['410-864-8980'];
    yield ['(410) 864-8980'];
    yield ['410 864 8980'];
    yield ['4108648980'];
    yield ['410.864.8980'];
    yield ['1-410-864-8980'];
    yield ['+1 410-864-8980'];
  }

  /**
   * Provides phone numbers which should not be valid.
   */
  public function provideInvalidNumbers() {
    yield ['ABC'];
    yield ['12345'];
    yield ['864-8980'];
  }

}
