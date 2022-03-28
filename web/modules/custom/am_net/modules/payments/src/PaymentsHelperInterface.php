<?php

namespace Drupal\am_net_payments;

/**
 * Defines the interface for an AM.net payments helper.
 */
interface PaymentsHelperInterface {

  /**
   * Gets the AM.net formatted card number.
   *
   * @param array $payment_details
   *   The raw payment details.
   *
   * @return string
   *   The credit card number in the masked format expected by AM.net.
   */
  public function getAmNetCardNumber(array $payment_details);

  /**
   * Gets the card type for a masked card number from AM.net.
   *
   * @param string $masked_card_number
   *   The card number in the format used within AM.net.
   *
   * @return string
   *   The credit card type in the format expected in Commerce Payment.
   */
  public function getCommerceCardType($masked_card_number);

}
