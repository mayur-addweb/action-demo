<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

/**
 * Defines a common interface for handle payment transactions.
 */
interface TransactionInterface {

  /**
   * Gets the order placed timestamp.
   *
   * @return int
   *   The order placed timestamp.
   */
  public function getPlacedTime();

  /**
   * Check if the transaction is available to be listed.
   *
   * @return bool
   *   TRUE if the transaction should be listed, otherwise FALSE.
   */
  public function availableToBeListed();

  /**
   * Gets the Order Items Summary.
   *
   * @return string
   *   The order item summary.
   */
  public function getOrderItemsSummary();

  /**
   * Gets the order placed date.
   *
   * @return string
   *   The formatted order placed date.
   */
  public function getPlacedDate();

  /**
   * Gets the order payment ref number.
   *
   * @return string
   *   The order payment ref number.
   */
  public function getPaymentRefNumber();

  /**
   * Gets the order operations.
   *
   * @return string
   *   The order operations.
   */
  public function getOperations();

  /**
   * Gets the order total.
   *
   * @return string
   *   The order total.
   */
  public function getTotal();

  /**
   * Gets the CPE credit(s) related to the order.
   *
   * @return string
   *   The order CPE credit(s).
   */
  public function getCredits();

}
