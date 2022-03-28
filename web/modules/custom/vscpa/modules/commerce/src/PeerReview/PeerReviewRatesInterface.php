<?php

namespace Drupal\vscpa_commerce\PeerReview;

/**
 * Defines a common interface for handle Peer Review rates.
 */
interface PeerReviewRatesInterface {

  /**
   * Name of store key variable.
   */
  const STORE_KEY = 'vscpa_commerce.peer_review_rates';

  /**
   * Name of store key variable for Peer Review Administrative Fees - Node Id.
   */
  const STORE_KEY_PEER_REVIEW_ADMINISTRATIVE_FEES_NODE_ID = 'vscpa_commerce.peer_review_fees_node_id';

  /**
   * Name of store key variable for Allow users to change their firm size.
   */
  const STORE_KEY_ALLOW_CHANGE_FIRM_SIZE = 'vscpa_commerce.peer_review_allow_change_firm_size';

  /**
   * Get table header.
   *
   * @return array
   *   The table header user for list Peer Review Rates.
   */
  public function getHeader();

  /**
   * Save Rates changes.
   *
   * @return bool|string
   *   SAVED_NEW., SAVED_UPDATED, or FALSE.
   */
  public function save();

  /**
   * Save from submitted Entries.
   *
   * @param array $rates
   *   The array of rates.
   *
   * @return bool|string
   *   SAVED_NEW., SAVED_UPDATED, or FALSE.
   */
  public function saveFromSubmittedEntries(array $rates = []);

  /**
   * Format submitted rate entries.
   *
   * @param array $rates
   *   The array of rates.
   *
   * @return array
   *   Formatted Peer Review rates Array.
   */
  public function formatEntries(array $rates = []);

  /**
   * Fetch rates changes from AM.Net.
   *
   * @return bool
   *   TRUE if the process was successfully completed, Otherwise FALSE.
   */
  public function fetchRatesChangesFromAmNet();

  /**
   * Change the indexes from array of rates using Billing codes.
   *
   * @param array $rates
   *   The array of rates.
   *
   * @return array
   *   Formatted Peer Review Rates array.
   */
  public function indexRatesByBillingCode(array $rates = []);

  /**
   * Get Peer Review Administrative Fees - Node Id.
   *
   * @return string|null
   *   The Peer Review Administrative Fees - Node Id.
   */
  public function getPeerReviewAdministrativeFeesNodeId();

  /**
   * Set Peer Review Administrative Fees - Node Id.
   *
   * @param string $node_id
   *   The Node Id.
   */
  public function setPeerReviewAdministrativeFeesNodeId($node_id = NULL);

  /**
   * Get flag value Allow change firm size.
   *
   * @return bool
   *   TRUE if user is allowed to change firm size, Otherwise FALSE.
   */
  public function getAllowChangeFirmSize();

  /**
   * Get Amount by Billing Class Code.
   *
   * @return string|null
   *   The amount related to the given billing code, Otherwise NULL.
   */
  public function getAmountByBillingCode($code);

  /**
   * Get Rate by Billing Class Code.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface|null
   *   The Rate object related to the given billing code, Otherwise NULL.
   */
  public function getRateByBillingClassCode($code);

  /**
   * Set flag value Allow change firm size.
   *
   * @param bool $flag
   *   The flag value.
   */
  public function setAllowChangeFirmSize($flag = NULL);

  /**
   * Get firm size options.
   *
   * @return array
   *   The list of firm size options keyed by billing code.
   */
  public function getFirmSizeOptions();

}
