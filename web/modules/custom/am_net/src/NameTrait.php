<?php

namespace Drupal\am_net;

use Drupal\am_net_user_profile\Entity\Person;

/**
 * AM.net Firm Codes Helper trait implementation.
 */
trait NameTrait {

  /**
   * Create record on AM.net.
   *
   * @param string $data
   *   The serialized JSON data.
   *
   * @return array|false
   *   An AM.net name record.
   */
  public function createNameRecord($data = NULL) {
    if (empty($data)) {
      return FALSE;
    }
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    if (!$_client) {
      return FALSE;
    }
    try {
      $response = $_client->post(Person::getCreateEntityApiEndPoint(), [], $data);
    }
    catch (\Exception $e) {
      return FALSE;
    }
    if ($response->hasError()) {
      return FALSE;
    }
    return $response->getResult();
  }

}
