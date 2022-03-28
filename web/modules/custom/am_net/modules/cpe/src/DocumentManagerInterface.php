<?php

namespace Drupal\am_net_cpe;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface DocumentManagerInterface.
 *
 * @package Drupal\am_net_cpe
 */
interface DocumentManagerInterface {

  /**
   * Syncs all CPE Event documents from AM.net to Drupal.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $event
   *   The Drupal CPE event product entity.
   */
  public function syncEventDocuments(ContentEntityInterface $event);

}
