<?php

namespace Drupal\vscpa_commerce\PeerReview;

use Drupal\commerce_price\Price;

/**
 * Defines a common interface for handle peer review transactions.
 */
interface PeerReviewTransactionInterface {

  /**
   * Payment Transaction Type Code.
   */
  const PAYMENT_TRANSACTION_TYPE_CODE = 'P';

  /**
   * Fee Transaction Type Code.
   */
  const FEE_TRANSACTION_TYPE_CODE = 'F';

  /**
   * Adjustment Transaction Type Code.
   */
  const ADJUSTMENT_TRANSACTION_TYPE_CODE = 'A';

  /**
   * Printed Transaction Type Code.
   */
  const PRINTED_TRANSACTION_TYPE_CODE = 'V';

  /**
   * Gets the transaction Amount.
   *
   * @return string|null
   *   The Transaction Amount.
   */
  public function getAmount();

  /**
   * Gets the Transaction Year.
   *
   * @return string
   *   The Transaction Year.
   */
  public function getYear();

  /**
   * Gets the Transaction Note.
   *
   * @return string
   *   The Transaction Note.
   */
  public function getNote();

  /**
   * Gets the Billing Item Label.
   *
   * @return string
   *   The Billing Item Label.
   */
  public function getBillingItemLabel();

  /**
   * Sets the Transaction Note.
   *
   * @param string $note
   *   The new transaction note.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface
   *   The current instance, otherwise NULL.
   */
  public function setNote($note = NULL);

  /**
   * Sets the Price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The new transaction price.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface
   *   The current instance, otherwise NULL.
   */
  public function setPrice(Price $price);

  /**
   * Gets the billed date.
   *
   * @return string
   *   The billed date.
   */
  public function getBilledDate();

  /**
   * Gets the current price.
   *
   * @return \Drupal\commerce_price\Price
   *   The Current fee price, otherwise NULL.
   */
  public function getPrice();

  /**
   * Gets whether the current price is zero.
   *
   * @return bool
   *   TRUE if the price is zero, FALSE otherwise.
   */
  public function isZero();

  /**
   * Gets the Formatted Amount.
   *
   * @return string
   *   The Formatted Fee Amount, NULL otherwise.
   */
  public function getFormattedAmount();

  /**
   * Gets whether the current price is greater than zero.
   *
   * @return bool
   *   TRUE if the current price is greater than the given price,
   *   FALSE otherwise.
   */
  public function greaterThanZero();

  /**
   * Gets whether the current price is greater than or equal to zero.
   *
   * @return bool
   *   TRUE if the current price is greater than or equal to zero,
   *   FALSE otherwise.
   */
  public function greaterOrEqualThanZero();

  /**
   * Subtracts the given $fee from the current $fee.
   *
   * @param \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $fee
   *   The $fee.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface
   *   The resulting $fee.
   */
  public function subtract(PeerReviewTransactionInterface $fee);

  /**
   * Adds the given $fee from the current $fee.
   *
   * @param \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $fee
   *   The $fee.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface
   *   The resulting $fee.
   */
  public function add(PeerReviewTransactionInterface $fee);

  /**
   * Gets an array of all property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public function toArray();

  /**
   * Checks if the transaction is of type Payment.
   *
   * @return bool
   *   TRUE if the transaction of the type Payment, Otherwise FALSE.
   */
  public function isPayment();

  /**
   * Checks if the transaction is of type Fee.
   *
   * @return bool
   *   TRUE if the transaction of the type Fee, Otherwise FALSE.
   */
  public function isFee();

  /**
   * Checks if the transaction is of type Adjustment.
   *
   * @return bool
   *   TRUE if the transaction of the type Adjustment, Otherwise FALSE.
   */
  public function isAdjustment();

  /**
   * Checks if the transaction is an Positive Adjustment.
   *
   * @return bool
   *   TRUE if the transaction represent a Positive Adjustment, Otherwise FALSE.
   */
  public function isPositiveAdjustment();

  /**
   * Checks if the transaction is an Negative Adjustment.
   *
   * @return bool
   *   TRUE if the transaction represent a Negative Adjustment, Otherwise FALSE.
   */
  public function isNegativeAdjustment();

  /**
   * Checks if the transaction is of type Printed.
   *
   * @return bool
   *   TRUE if the transaction of the type Printed, Otherwise FALSE.
   */
  public function isPrinted();

  /**
   * Checks if the transaction is related to a billing Class Code Adjustment.
   *
   * @return bool
   *   TRUE if the transaction represent a billing Class Code Adjustment,
   *   Otherwise FALSE.
   */
  public function isBillingClassCodeAdjustment();

  /**
   * Gets the flag value 'Sync As Adjustment'.
   *
   * @return bool
   *   The flag value.
   */
  public function syncAsAdjustment();

  /**
   * Sets the flag value 'Sync As Adjustment'.
   *
   * @param bool $value
   *   The flag value.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface
   *   The current instance, otherwise NULL.
   */
  public function setSyncAsAdjustment($value = NULL);

  /**
   * Gets the Account Code.
   *
   * @return string
   *   The Account Code value.
   */
  public function getAccountCode();

  /**
   * Gets the Account coming from AM.Net.
   *
   * @return string
   *   The Account value coming from AM.Net.
   */
  public function getAccount();

  /**
   * Sets the Account Code.
   *
   * @param string $code
   *   The Account Code.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface
   *   The current instance, otherwise NULL.
   */
  public function setAccountCode($code = NULL);

}
