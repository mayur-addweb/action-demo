<?php

namespace Drupal\am_net_membership\DuesPaymentPlan;

/**
 * Defines a common interface for Dues Payment Plan Transaction implementation.
 */
interface DuesPaymentPlanTransactionInterface {

  /**
   * Gets the Billing Item Label.
   *
   * @return string
   *   The Billing Item Label.
   */
  public function getBillingItemLabel();

  /**
   * Gets the month.
   *
   * @return string
   *   The month number.
   */
  public function getMonth();

  /**
   * Gets the billed date.
   *
   * @return string
   *   The billed date.
   */
  public function getBilledDate();

  /**
   * Gets the Formatted Amount.
   *
   * @return string
   *   The Formatted Fee Amount, NULL otherwise.
   */
  public function getFormattedAmount();

  /**
   * Format a given Amount.
   *
   * @param string $amount
   *   The given amount.
   *
   * @return string
   *   The Formatted Amount, NULL otherwise.
   */
  public function formatAmount($amount = NULL);

  /**
   * Gets the total price.
   *
   * @return \Drupal\commerce_price\Price
   *   The Current fee price, otherwise NULL.
   */
  public function getTotalPrice();

  /**
   * Gets the item ID.
   *
   * @return string
   *   The item ID.
   */
  public function getId();

}
