<?php

namespace Drupal\am_net;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Provides useful methods for parsing/validating phone numbers.
 */
final class PhoneNumberHelper {

  /**
   * The helper instance.
   *
   * @var \libphonenumber\PhoneNumberUtil
   */
  private $util;

  /**
   * PhoneNumberHelper constructor.
   */
  public function __construct() {
    $this->util = PhoneNumberUtil::getInstance();
  }

  /**
   * Formata given number in a phone US formatted number.
   *
   * @param string $input
   *   User-provided phone number.
   *
   * @return string|null
   *   Properly-formatted US phone number, or NULL if invalid.
   */
  public function format(string $input): ?string {
    try {
      $number = $this->util->parse($input, "US");
    }
    catch (NumberParseException $ex) {
      return NULL;
    }

    return $this->util->format($number, PhoneNumberFormat::INTERNATIONAL);
  }

  /**
   * Returns a properly-formatted number, if valid.
   *
   * @param string $input
   *   User-provided phone number.
   *
   * @return string|null
   *   Properly-formatted US phone number, or NULL if invalid.
   */
  public function validateAndFormatPhoneNumber(string $input): ?string {
    try {
      $number = $this->util->parse($input, "US");
    }
    catch (NumberParseException $ex) {
      return NULL;
    }

    if (!$this->util->isValidNumber($number)) {
      return NULL;
    }

    $formatted = $this->util->format($number, PhoneNumberFormat::INTERNATIONAL);

    // Remove the country code, which will always be `+` followed by
    // some number(s) followed by a space, so we'll just
    // take everything after the first space.
    $formatted = substr($formatted, strpos($formatted, ' ') + 1);

    return $formatted;
  }

}
