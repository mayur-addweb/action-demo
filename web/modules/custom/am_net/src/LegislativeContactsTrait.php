<?php

namespace Drupal\am_net;

/**
 * AM.net Legislative Contacts Helper trait implementation.
 */
trait LegislativeContactsTrait {

  /**
   * Fetches all AM.net legislatives contacts by Name ID.
   *
   * @param string $amnet_id
   *   The AMNet Name ID.
   *
   * @return array
   *   List of AM.net Colleges.
   */
  public function getLegislativeContacts($amnet_id = NULL) {
    if (empty($amnet_id)) {
      return [];
    }
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    if ($_client === NULL) {
      return [];
    }
    $endpoint = "Person/{$amnet_id}/legislativecontacts";
    $query = $_client->get($endpoint);
    if ($query->hasError()) {
      return [];
    }
    return $query->getResult();
  }

  /**
   * Fetches all legislator from AM.net.
   *
   * @return array
   *   List of all legislator from AM.net.
   */
  public function getAllLegislator() {
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    if ($_client === NULL) {
      return [];
    }
    $endpoint = "legislator";
    $query = $_client->get($endpoint, ['all' => 'true']);
    if ($query->hasError()) {
      return [];
    }
    return $query->getResult();
  }

}
