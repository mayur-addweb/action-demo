<?php

namespace Drupal\am_net;

/**
 * AM.net Location Colleges Helper trait implementation.
 */
trait LocationCollegesTrait {

  /**
   * Fetches all AM.net Colleges.
   *
   * @return array
   *   List of AM.net Colleges.
   */
  public function getAllColleges() {
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    if ($_client === NULL) {
      return [];
    }
    $query = $_client->get('Lists', ['listkey' => 'CUCC']);
    if ($query->hasError()) {
      return [];
    }
    return $query->getResult();
  }

}
