<?php

namespace Drupal\am_net_triggers\QueueItem;

/**
 * A product sync queue item.
 *
 * @package Drupal\am_net_triggers\QueueItem
 */
class ProductSyncQueueItem {

  /**
   * The product code.
   *
   * @var string
   */
  public $code;

  /**
   * ProductSyncQueueItem constructor.
   *
   * @param string $code
   *   The AM.net product code.
   */
  public function __construct($code) {
    if (empty($code)) {
      return new \Exception('Product "code" property required.');
    }
    $this->code = $code;
  }

}
