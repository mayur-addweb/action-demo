<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

/**
 * AmNet General Transactions object representation.
 */
abstract class AmNetTransactions implements AmNetTransactionsInterface {

  /**
   * The AM.net API HTTP Client.
   *
   * @var \UnleashedTech\AMNet\Api\Client|null
   */
  protected $client = NULL;

  /**
   * The state prefix.
   *
   * @var string
   */
  protected $statePrefix = NULL;

  /**
   * {@inheritdoc}
   */
  public function setClient($client = NULL) {
    return $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateKey($am_net_name_id) {
    return "{$this->statePrefix}.{$am_net_name_id}";
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetRecords($am_net_name_id) {
    $stored_entities = &drupal_static(__METHOD__, []);
    $key = $this->getStateKey($am_net_name_id);
    if (!isset($stored_entities[$key])) {
      // Get the date from the AM.net API.
      $event = $this->loadRecords($am_net_name_id);
      $stored_entities[$key] = $event;
    }
    return $stored_entities[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueKey($key = NULL, array $transactions = []) {
    if (!isset($transactions[$key])) {
      return $key;
    }
    $key++;
    // Increase key to find one that does not exist.
    while (isset($transactions[$key])) {
      $key++;
    }
    return $key;
  }

  /**
   * {@inheritdoc}
   */
  public function addTransactions($am_net_name_id = NULL, array &$transactions = []) {
    $records = $this->getAmNetRecords($am_net_name_id);
    if (!empty($records)) {
      $this->loadTransactionsFromRecords($records, $transactions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadTransactionsFromRecords(array $records = [], array &$transactions = []) {
    if (empty($records)) {
      return;
    }
    foreach ($records as $delta => $data) {
      $transaction = $this->createTransaction($data);
      // Sort transactions by place date.
      $place_date_time = $transaction->getPlacedTime();
      $key = $this->getUniqueKey($place_date_time, $transactions);
      if (!$transaction->availableToBeListed()) {
        continue;
      }
      // Add transaction.
      $transactions[$key] = $transaction;
    }
  }

  /**
   * {@inheritdoc}
   */
  abstract public function loadRecords($am_net_name_id);

  /**
   * {@inheritdoc}
   */
  abstract public function createTransaction(array $order = []);

}
