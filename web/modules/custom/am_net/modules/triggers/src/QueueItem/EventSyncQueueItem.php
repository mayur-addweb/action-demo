<?php

namespace Drupal\am_net_triggers\QueueItem;

/**
 * An event sync queue item.
 *
 * @package Drupal\am_net_triggers\QueueItem
 */
class EventSyncQueueItem {

  /**
   * The event year.
   *
   * @var string
   */
  public $year;

  /**
   * The event code.
   *
   * @var string
   */
  public $code;

  /**
   * EventSyncQueueItem constructor.
   *
   * @param string $year
   *   The 2-digit AM.net event year.
   * @param string $code
   *   The AM.net event code.
   */
  public function __construct($year, $code) {
    if (empty($year) || empty($code)) {
      return new \Exception('Year and code properties required.');
    }
    $this->year = $year;
    $this->code = $code;
  }

}
