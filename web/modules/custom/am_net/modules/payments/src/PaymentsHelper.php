<?php

namespace Drupal\am_net_payments;

/**
 * Provides help for AM.net payment operations.
 *
 * @package Drupal\am_net_payments
 */
class PaymentsHelper implements PaymentsHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function getAmNetCardNumber(array $payment_details) {
    $card_number = $payment_details['number'];
    $card_number_masked = $this->getMaskedNumber($card_number, '*');
    $card_number_masked_dashed = $this->getDashedNumber($card_number_masked);

    return $card_number_masked_dashed;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommerceCardType($masked_card_number) {
    $length = strlen(str_replace('-', '', $masked_card_number));
    $first_digit = (int) substr($masked_card_number, 0, 1);
    if ($first_digit === 4) {
      return 'visa';
    }
    elseif (in_array($first_digit, [2, 5]) && $length === 16) {
      return 'mastercard';
    }
    elseif ($first_digit === 3 && $length === 15) {
      return 'amex';
    }
    elseif ($first_digit === 6 && $length === 16) {
      // This could also be a 'unionpay' card but we can't know without digit 2.
      return 'discover';
    }
    elseif ($first_digit === 3 && $length === 14) {
      return 'dinersclub';
    }
    elseif ($first_digit === 3) {
      return 'jcb';
    }
  }

  /**
   * Gets a number masked with given symbol for all but first (1) and last (4).
   *
   * From https://github.com/Payum/Payum/commit/f43561d340b9d5823b21d9a51f50f23c8a378be2.
   *
   * @param string $number
   *   The number to mask.
   * @param string $mask_symbol
   *   The mask symbol to use, default to '*'.
   * @param int $show_last
   *   The number of digits to show at the end (0-indexed), defaults to 4 (3).
   *
   * @return string
   *   A string where all but the first (1) and last (4) characters are masked.
   */
  protected function getMaskedNumber($number, $mask_symbol = '*', $show_last = 3) {
    return preg_replace("/(?!^.?)[0-9a-zA-Z](?!(.){0,$show_last}$)/", $mask_symbol, $number);
  }

  /**
   * Gets a number with dashes between every 4th number.
   *
   * From https://gist.github.com/nikhilben/512659.
   *
   * @param string $number
   *   The number.
   *
   * @return bool|string
   *   The number with dashes between every 4th number.
   */
  protected function getDashedNumber($number) {
    $cleaned = str_replace(['-', ' '], '', $number);
    $length = strlen($number);
    $new_number = substr($cleaned, -4);
    for ($i = $length - 5; $i >= 0; $i--) {
      if ((($i + 1) - $length) % 4 == 0) {
        $new_number = '-' . $new_number;
      }
      $new_number = $cleaned[$i] . $new_number;
    }

    return $new_number;
  }

}
