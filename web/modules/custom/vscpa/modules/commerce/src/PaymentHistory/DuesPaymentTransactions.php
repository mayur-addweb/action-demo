<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

/**
 * AmNet Dues Payment Transactions object representation.
 */
class DuesPaymentTransactions extends AmNetTransactions {

  /**
   * The state prefix.
   *
   * @var string
   */
  protected $statePrefix = 'am_net_payment_transactions';

  /**
   * {@inheritdoc}
   */
  public function loadRecords($am_net_name_id) {
    $records = $this->client->getUserDuesByNameId($am_net_name_id);
    if (!$records) {
      return [];
    }
    return [$records];
  }

  /**
   * {@inheritdoc}
   */
  public function doLoadTransactionsFromRecords(array $records = [], array &$transactions = []) {
    if (empty($records)) {
      return;
    }
    foreach ($records as $delta => $data) {
      $transaction = $this->createTransaction($data);
      if (!$transaction->isTransactionPaid()) {
        continue;
      }
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
  public function loadTransactionsFromRecords(array $records = [], array &$transactions = []) {
    if (empty($records)) {
      return;
    }
    foreach ($records as $delta => $data) {
      $dues_transactions = $data['Transactions'] ?? NULL;
      if (empty($dues_transactions)) {
        continue;
      }
      $this->doLoadTransactionsFromRecords($dues_transactions, $transactions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createTransaction(array $order = []) {
    return new DuesPaymentTransaction($order);
  }

}
