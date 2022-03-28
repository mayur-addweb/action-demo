<?php

namespace Drupal\am_net_triggers\QueueItem;

/**
 * A name merges sync queue item.
 *
 * @package Drupal\am_net_triggers\QueueItem
 */
class NameMergesSyncQueueItem {

  /**
   * The old AM.net name id.
   *
   * @var int
   */
  public $deletedId;

  /**
   * The New AM.net name id.
   *
   * @var int
   */
  public $mergeIntoId;

  /**
   * The merged date time.
   *
   * @var string
   */
  public $mergedDateTime;

  /**
   * NameSyncQueueItem constructor.
   *
   * @param int $deleted_id
   *   The old AM.net name id.
   * @param int $merge_into_id
   *   The New AM.net name id.
   * @param string $merged_date_time
   *   The merged date time.
   */
  public function __construct($deleted_id, $merge_into_id, $merged_date_time) {
    if (empty($deleted_id)) {
      return new \Exception('Deleted Id property required.');
    }
    if (empty($merge_into_id)) {
      return new \Exception('New Id property required.');
    }
    $this->deletedId = $deleted_id;
    $this->mergeIntoId = $merge_into_id;
    $this->mergedDateTime = $merged_date_time;
  }

}
