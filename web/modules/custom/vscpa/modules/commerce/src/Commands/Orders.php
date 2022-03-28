<?php

namespace Drupal\vscpa_commerce\Commands;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\vscpa_commerce\AmNetOrderInterface;
use Drupal\vscpa_commerce\AmNetSyncManager;
use Drupal\commerce_order\Entity\Order;
use Drush\Commands\DrushCommands;

/**
 * Class Orders.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\vscpa_commerce\Commands
 */
class Orders extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\vscpa_commerce\AmNetSyncManager
   */
  protected $amNetSyncManager;

  /**
   * ConfigSplitCommands constructor.
   *
   * @param \Drupal\vscpa_commerce\AmNetSyncManager $amNetSyncManager
   *   The CLI service which allows interoperability.
   */
  public function __construct(AmNetSyncManager $amNetSyncManager) {
    $this->amNetSyncManager = $amNetSyncManager;
  }

  /**
   * Reset Order Sync.
   *
   * @param string $order_id
   *   The Order ID.
   *
   * @command reset-order-sync
   *
   * @usage drush reset-order-sync 1234
   *   Reset Order Sync.
   *
   * @aliases reset-order-sync
   */
  public function resetOrderSync($order_id = NULL) {
    if (empty($order_id)) {
      $message = t('Please provide a valid Order ID.');
      drush_log($message, 'warning');
      return;
    }
    $order = Order::load($order_id);
    if (!$order) {
      $message = t('Please provide a valid Order ID.');
      drush_log($message, 'warning');
      return;
    }
    $field_name = 'field_am_net_sync';
    $field = $order->get($field_name);
    if ($field->isEmpty()) {
      $message = t('The Order @id sync have been successfully reset.', ['@id' => $order_id]);
      drush_log($message, 'success');
      return;
    }
    $value = $field->first()->getValue();
    $items = $value['items'] ?? [];
    foreach ($items as $delta => $item) {
      $value['items'][$delta]['sync_status'] = AmNetOrderInterface::ORDER_ITEM_NOT_SYNCHRONIZED;
    }
    // Change the sync status.
    $value['sync_status'] = AmNetOrderInterface::ORDER_ITEM_NOT_SYNCHRONIZED;
    // Update the value.
    $order->set($field_name, $value);
    // Save the changes.
    try {
      $order->save();
      $message = t('The Order @id sync have been successfully reset.', ['@id' => $order_id]);
      drush_log($message, 'success');
    }
    catch (EntityStorageException $e) {
      $message = t('It was not possible reset the sync for the Order @id at this time, please try again later.', ['@id' => $order_id]);
      drush_log($message, 'warning');
    }
  }

}
