<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

/**
 * AmNet Product Sales Transactions object representation.
 */
class ProductSalesTransactions extends AmNetTransactions {

  /**
   * The state prefix.
   *
   * @var string
   */
  protected $statePrefix = 'am_net_product_sales_transactions';

  /**
   * {@inheritdoc}
   */
  public function loadRecords($am_net_name_id) {
    $am_net_name_id = trim($am_net_name_id);
    $sales = [];
    $endpoint = "/Person/{$am_net_name_id}/productsales";
    $response = $this->client->get($endpoint);
    if (!$response->hasError()) {
      $sales = $response->getResult();
    }
    return $sales;
  }

  /**
   * {@inheritdoc}
   */
  public function createTransaction(array $order = []) {
    return new ProductSaleTransaction($order);
  }

}
