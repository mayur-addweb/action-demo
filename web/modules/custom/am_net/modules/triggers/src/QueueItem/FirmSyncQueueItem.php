<?php

namespace Drupal\am_net_triggers\QueueItem;

/**
 * A firm sync queue item.
 *
 * @package Drupal\am_net_triggers\QueueItem
 */
class FirmSyncQueueItem {

  /**
   * The AM.net firm id.
   *
   * @var int
   */
  public $id;

  /**
   * The AM.net changed date.
   *
   * @var string
   */
  public $changeDate;

  /**
   * FirmSyncQueueItem constructor.
   *
   * @param int $id
   *   The AM.net name id.
   * @param string $change_date
   *   The AM.net changed date.
   */
  public function __construct($id, $change_date = '') {
    if (empty($id)) {
      return new \Exception('ID property required.');
    }
    $this->id = $id;
    $this->changeDate = $change_date;
  }

}
