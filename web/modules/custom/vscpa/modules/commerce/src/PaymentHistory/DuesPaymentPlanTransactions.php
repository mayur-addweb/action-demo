<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

/**
 * AmNet Dues Payment Plan Transactions object representation.
 */
class DuesPaymentPlanTransactions extends AmNetTransactions {

  /**
   * The state prefix.
   *
   * @var string
   */
  protected $statePrefix = 'am_net_payment_plan_transactions';

  /**
   * {@inheritdoc}
   */
  public function loadRecords($am_net_name_id) {
    $plans = $this->client->getUserPlansById($am_net_name_id);
    if (!$plans) {
      return [];
    }
    return $plans;
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
      $plan_transactions = $data['Transactions'] ?? NULL;
      if (empty($plan_transactions)) {
        continue;
      }
      $this->doLoadTransactionsFromRecords($plan_transactions, $transactions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createTransaction(array $order = []) {
    return new DuesPaymentPlanTransaction($order);
  }

}
