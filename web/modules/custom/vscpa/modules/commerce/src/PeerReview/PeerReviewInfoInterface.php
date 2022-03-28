<?php

namespace Drupal\vscpa_commerce\PeerReview;

/**
 * Defines a common interface for handle individual peer review info by firm.
 */
interface PeerReviewInfoInterface {

  /**
   * The Active Status code.
   */
  const ACTIVE_STATUS_CODE = 'A';

  /**
   * Retrieve Peer Review info form AM.Net.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfo|null
   *   The current instance or null.
   */
  public function retrievePeerReviewInfo();

  /**
   * Get The AM.Net AICPA Firm ID.
   *
   * @return string|null
   *   The AM.Net AICPA Firm ID related with the given firm.
   */
  public function getAmNetAicpaNumber();

  /**
   * Get the previous Billing Code associated with the operation.
   *
   * @return string|null
   *   The Previous Billing Code.
   */
  public function getPreviousBillingCode();

  /**
   * Get the previous Billing Code associated with the operation.
   *
   * @param string $billing_class_code
   *   The AM.net Billing Class Code.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   *   The current Peer Review Info instance.
   */
  public function setPreviousBillingCode($billing_class_code = NULL);

  /**
   * Get the new Billing Code associated with the operation.
   *
   * @return string|null
   *   The new Billing Code.
   */
  public function getNewBillingCode();

  /**
   * Get the new Billing Code associated with the operation.
   *
   * @param string $billing_class_code
   *   The AM.net Billing Class Code.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   *   The current Peer Review Info instance.
   */
  public function setNewBillingCode($billing_class_code = NULL);

  /**
   * Get the flag value 'Has Firm Size Changes'?.
   *
   * @return bool
   *   The flag value.
   */
  public function hasFirmSizeChanges();

  /**
   * Sets the flag value "Firm has Size changes?".
   *
   * @param bool $changes
   *   The flag value.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   *   The current Peer Review Info instance.
   */
  public function setFirmSizeChanges($changes = FALSE);

  /**
   * Get the billing items associated with the Peer Review payment.
   *
   * @return array|null
   *   The array of billing items.
   */
  public function getItems();

  /**
   * Gets The AM.net Firm ID associated with the current Peer review process.
   *
   * @return string|null
   *   The AM.net Firm ID.
   */
  public function getFirmId();

  /**
   * Sets The AM.net Firm ID associated with the current Peer review process.
   *
   * @param string $firm_id
   *   The AM.net Firm ID.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   *   The current Peer Review Info instance.
   */
  public function setFirmId($firm_id = NULL);

  /**
   * Get The AM.Net Status Code.
   *
   * @return string|null
   *   The AM.Net Status Code related with the given firm.
   */
  public function getAmNetStatusCode();

  /**
   * Get The AM.Net Aicpa Name.
   *
   * @return string|null
   *   The AM.Net Aicpa Name related with the given firm.
   */
  public function getAmNetAicpaName();

  /**
   * Get The AM.Net Contact Email.
   *
   * @return string|null
   *   The AM.Net Contact Email related with the given firm.
   */
  public function getAmNetContactEmail();

  /**
   * Get The AM.Net Contact Phone.
   *
   * @return string|null
   *   The AM.Net Contact Phone related with the given firm.
   */
  public function getAmNetContactPhone();

  /**
   * Get The AM.Net Billing Class Code.
   *
   * @return string|null
   *   The AM.Net Billing Class Code related with the given firm.
   */
  public function getBillingClassCode();

  /**
   * Get The AM.Net Balance.
   *
   * @return string|null
   *   The AM.Net Balance related with the given firm.
   */
  public function getBalance();

  /**
   * Gets the amount of the AM.Ne Peer Review Payment.
   *
   * @return \Drupal\commerce_price\Price
   *   The amount for the Peer Review Payment.
   */
  public function getAmount();

  /**
   * Get The list of AM.Net peer review transactions.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface[]
   *   The List of peer review transactions.
   */
  public function getTransactions();

  /**
   * Get List of Balance sorted by years.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface[]
   *   The List of Balance sorted by years.
   */
  public function getBalanceByYears();

  /**
   * Get List of Balance sorted by years and applying Firm Size changes.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface[]
   *   The List of Balance sorted by years.
   */
  public function getBalanceByYearsBasedOnFirmSizeChanges();

  /**
   * Check if the Firm is active on the Peer Review system.
   *
   * @return bool
   *   Check if the Firm is active on the Peer Review system.
   */
  public function isFirmActiveOnPeerReviewSystem();

  /**
   * Reset AM.Net peer review data.
   */
  public function resetData();

  /**
   * Gets the Formatted Balance.
   *
   * @return string
   *   The Formatted Balance, NULL otherwise.
   */
  public function getFormattedBalance();

  /**
   * Gets the AM.net raw data associated with the current Peer review process.
   *
   * @return array
   *   The AM.net Data
   */
  public function getData();

  /**
   * Sets the AM.net raw data associated with the current Peer review process.
   *
   * @param string $data
   *   The AM.net raw data.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   *   The current Peer Review Info instance.
   */
  public function setData($data = NULL);

  /**
   * Gets an array of all property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public function toArray();

  /**
   * Sets the AM.net client.
   *
   * @param mixed $amnet_client
   *   The AM.net API HTTP Client.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   *   The current Peer Review Info instance.
   */
  public function setAmNetClient($amnet_client = NULL);

  /**
   * Gets the AM.net client.
   *
   * @return mixed
   *   The AM.net API HTTP Client.
   */
  public function getAmNetClient();

  /**
   * Checks if the current peer review process has balance.
   *
   * @return bool
   *   TRUE if the Peer review has balance, Otherwise FALSE.
   */
  public function hasBalance();

  /**
   * Set the Peer Review Rate service.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   *   The current Peer Review Info instance.
   */
  public function setRates(PeerReviewRatesInterface $rates_service);

  /**
   * Gets the Balance based on Firm size changes.
   *
   * @param string $current_fiscal_year
   *   The current fiscal year.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface
   *   The Peer Review Transaction.
   */
  public function getBalanceBasedOnFirmSizeChanges($current_fiscal_year);

  /**
   * Get List of Transactions Items used for Sync with AM.net.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface[]
   *   The List of Transactions.
   */
  public function getTransactionsForSync();

  /**
   * Convert Transactions into Negative Adjustments.
   *
   * @param \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $transaction
   *   The base transaction.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface[]
   *   The List of Transactions.
   */
  public function convertTransactionIntoNegativeAdjustment(PeerReviewTransactionInterface $transaction = NULL);

  /**
   * Get Billing Items.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface[]
   *   The List of Transactions.
   */
  public function getBillingItems();

}
