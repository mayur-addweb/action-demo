<?php

namespace Drupal\am_net;

/**
 * Defines a common interface for handle individual AM.Net record data.
 */
interface AmNetDataInterface {

  /**
   * Get the AM.net Target ID.
   *
   * @return string|null
   *   The target ID.
   */
  public function getTargetId();

  /**
   * Get the AM.net Owner ID.
   *
   * @return string|null
   *   The owner ID.
   */
  public function getOwnerId();

  /**
   * Get the AM.net raw data.
   *
   * @return array|null
   *   The raw data.
   */
  public function getData();

}
