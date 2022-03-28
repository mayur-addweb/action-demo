<?php

namespace Drupal\announcement_pop_up\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Announcement entities.
 */
class AnnouncementViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
