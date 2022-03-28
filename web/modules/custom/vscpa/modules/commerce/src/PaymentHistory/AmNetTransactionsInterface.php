<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

/**
 * Defines a common interface for handle AmNet transactions.
 */
interface AmNetTransactionsInterface {

  /**
   * Add Transactions to a given array of transactions.
   *
   * @param string $am_net_name_id
   *   The AM.net name Id.
   * @param \Drupal\vscpa_commerce\PaymentHistory\TransactionInterface[] $transactions
   *   The transactions list.
   */
  public function addTransactions($am_net_name_id = NULL, array &$transactions = []);

  /**
   * Create Transaction instance from array of order data.
   *
   * @param array $order
   *   The order data coming from AM.net.
   *
   * @return \Drupal\vscpa_commerce\PaymentHistory\TransactionInterface
   *   The new Transaction object.
   */
  public function createTransaction(array $order = []);

  /**
   * Set AM.net API HTTP Client.
   *
   * @param \UnleashedTech\AMNet\Api\Client|null $client
   *   The AM.net API HTTP Client.
   */
  public function setClient($client = NULL);

}
