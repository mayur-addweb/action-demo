<?php

namespace Drupal\am_net_triggers\QueueItem;

/**
 * A registration sync queue item.
 *
 * @package Drupal\am_net_triggers\QueueItem
 */
class RegistrationSyncQueueItem {

  /**
   * The AM.net registration record.
   *
   * @var array
   */
  public $record;

  /**
   * RegistrationSyncQueueItem constructor.
   *
   * @param array $record
   *   An AM.net event registration record, as retrieved from
   *   CpeRegistrationManager::getAmNetEventRegistrations.
   */
  public function __construct(array $record) {
    $this->record = $record;
  }

}
