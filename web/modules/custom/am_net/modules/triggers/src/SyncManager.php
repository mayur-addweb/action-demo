<?php

namespace Drupal\am_net_triggers;

use Drupal\Core\Queue\QueueFactory;
use Drupal\am_net\AssociationManagementClient;
use Drupal\am_net_triggers\QueueItem\FirmSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\NameSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\EventSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\ProductSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\NameMergesSyncQueueItem;

/**
 * Default implementation of the  am_net_triggers Sync Manager.
 */
class SyncManager {

  /**
   * The AM.net REST API client.
   *
   * @var \Drupal\am_net\AssociationManagementClient
   */
  protected $client;

  /**
   * The Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The Queue Name.
   *
   * @var string
   */
  protected $queueName = 'am_net_triggers';

  /**
   * SyncManager constructor.
   *
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The AM.net REST API client.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(AssociationManagementClient $client, QueueFactory $queue_factory) {
    $this->client = $client;
    $this->queueFactory = $queue_factory;
  }

  /**
   * Fetch firm records with changes on AM.net and add them to Queue for Sync.
   */
  public function addToQueueFirmsChangedRecentlyFromAmNet() {
    $queue_number_of_items = 0;
    $records = $this->client->getFirmsChangedRecentlyFromAmNet();
    if (!empty($records) && is_array($records)) {
      $queue = $this->queueFactory->get($this->queueName);
      $added = [];
      foreach ($records as $key => $record) {
        $change_date = $record['ChangeDate'] ?? FALSE;
        $am_net_id = $record['Firm']['FirmCode'] ?? FALSE;
        $am_net_id = trim($am_net_id);
        if (!empty($am_net_id) && !isset($added[$am_net_id])) {
          $item = new FirmSyncQueueItem($am_net_id, $change_date);
          $queue->createItem($item);
          $added[$am_net_id] = TRUE;
        }
      }
      $queue_number_of_items = $queue->numberOfItems();
    }
    return $queue_number_of_items;
  }

  /**
   * Fetch name records with changes on AM.net and add them to Queue for Sync.
   */
  public function addToQueueNamesChangedRecentlyFromAmNet() {
    $queue_number_of_items = 0;
    $records = $this->client->getNamesChangedRecentlyFromAmNet();
    if (!empty($records) && is_array($records)) {
      $queue = $this->queueFactory->get($this->queueName);
      foreach ($records as $key => $record) {
        $change_date = $record['ChangeDate'] ?? FALSE;
        $am_net_id = $record['Person']['NamesID'] ?? FALSE;
        if (!empty($am_net_id)) {
          $am_net_id = trim($am_net_id);
          $item = new NameSyncQueueItem($am_net_id, $change_date);
          $queue->createItem($item);
        }
      }
      $queue_number_of_items = $queue->numberOfItems();
    }
    return $queue_number_of_items;
  }

  /**
   * Fetch name records merges on AM.net and add them to Queue for Sync.
   */
  public function addToQueueNamesMergesRecentlyFromAmNet() {
    $queue_number_of_items = 0;
    $records = $this->client->getNamesMergesRecentlyFromAmNet();
    if (empty($records) || !is_array($records)) {
      return $queue_number_of_items;
    }
    $queue = $this->queueFactory->get($this->queueName);
    foreach ($records as $key => $record) {
      $deleted_id = $record['DeletedId'] ?? FALSE;
      $merge_into_id = $record['MergeIntoId'] ?? FALSE;
      $merged_date_time = $record['MergedDateTime'] ?? FALSE;
      if (empty($deleted_id) || empty($merge_into_id)) {
        continue;
      }
      $deleted_id = trim($deleted_id);
      $merge_into_id = trim($merge_into_id);
      $item = new NameMergesSyncQueueItem($deleted_id, $merge_into_id, $merged_date_time);
      $queue->createItem($item);
    }
    $queue_number_of_items = $queue->numberOfItems();
    return $queue_number_of_items;
  }

  /**
   * Fetch Events records with changes on AM.net and add them to Queue for Sync.
   */
  public function addToQueueEventsChangedRecentlyFromAmNet() {
    $queue_number_of_items = 0;
    $records = $this->client->getEventsChangedRecentlyFromAmNet();
    if (!empty($records) && is_array($records)) {
      $queue = $this->queueFactory->get($this->queueName);
      foreach ($records as $key => $record) {
        $code = $record['EventCode'] ?? FALSE;
        $year = $record['EventYear'] ?? FALSE;
        if (!empty($code) && !empty($year)) {
          $item = new EventSyncQueueItem($year, $code);
          $queue->createItem($item);
        }
      }
      $queue_number_of_items = $queue->numberOfItems();
    }
    return $queue_number_of_items;
  }

  /**
   * Fetch Product records with changes on AM.net, add them to Queue for Sync.
   */
  public function addToQueueProductsChangedRecentlyFromAmNet() {
    $queue_number_of_items = 0;
    $records = $this->client->getProductsChangedRecentlyFromAmNet();
    if (!empty($records) && is_array($records)) {
      $queue = $this->queueFactory->get($this->queueName);
      foreach ($records as $key => $record) {
        $code = $record['Product']['ProductCode'] ?? FALSE;
        $code = trim($code);
        if (!empty($code)) {
          $item = new ProductSyncQueueItem($code);
          $queue->createItem($item);
        }
      }
      $queue_number_of_items = $queue->numberOfItems();
    }
    return $queue_number_of_items;
  }

}
