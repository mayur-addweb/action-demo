<?php

namespace Drupal\rss_list\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for RSS Page entities.
 */
class RssPageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    return $data;
  }

}
