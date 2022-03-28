<?php

namespace Drupal\vscpa_commerce\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Event session entities.
 */
class EventSessionViewsData extends EntityViewsData {

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
