<?php

namespace Drupal\vscpa_commerce;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * AM.net Synchronization Manager.
 */
interface AmNetSyncManagerInterface {

  /**
   * Pushes a Drupal Commerce order entity to relevant record(s) in AM.net.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   */
  public function pushOrder(OrderInterface $order);

  /**
   * Pulls a list of Product sales from AM>net for the given Name id.
   *
   * @param int $am_net_name_id
   *   The AM.net Name ID.
   *
   * @return array
   *   An array of product sales records from AM.net.
   */
  public function pullProductSales($am_net_name_id);

  /**
   * Syncs an AM.net CPE product sale from AM.net to Drupal.
   *
   * @param array $order
   *   An AM.net product sale order record.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The Drupal order created/synced from the AM.net record,
   *   or NULL if order not created.
   */
  public function syncAmNetCpeProductSale(array $order);

}
