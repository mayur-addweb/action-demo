<?php

namespace Drupal\vscpa_sso\QueueItem;

/**
 * A user sync queue item.
 *
 * @package Drupal\vscpa_sso\QueueItem
 */
class UserSyncQueueItem {

  /**
   * The AM.net name id.
   *
   * @var int
   */
  public $id;

  /**
   * User Sync Queue Item constructor.
   *
   * @param int $id
   *   The AM.net name id.
   */
  public function __construct($id) {
    if (empty($id)) {
      return new \Exception('ID property required.');
    }
    $this->id = $id;
  }

}
