<?php

namespace Drupal\am_net;

use Drupal\user\UserInterface;

/**
 * AM.net Payment Plans Helper trait implementation.
 */
trait PaymentPlansTrait {

  /**
   * Get the Payment Plans associated to a given person(Statically Cached).
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   * @param string $dues_year
   *   The dues year of reference.
   *
   * @return bool|array
   *   The AMNet Response with the the array list of Dues Balance,
   *   otherwise FALSE.
   */
  public function getUserActivePaymentPlan(UserInterface $user = NULL, $dues_year = NULL) {
    if (!$user) {
      return [];
    }
    if (!is_numeric($dues_year)) {
      return [];
    }
    $plans = $this->getUserPlansStaticallyCached($user);
    if (empty($plans)) {
      return [];
    }
    foreach ($plans as $delta => $plan) {
      $plan_dues_year = $plan['DuesYear'] ?? NULL;
      if (empty($plan_dues_year)) {
        continue;
      }
      if ($plan_dues_year != $dues_year) {
        continue;
      }
      $total_to_pay = $plan['TotalToPay'] ?? NULL;
      $total_paid = $plan['TotalPaid'] ?? NULL;
      if (empty($total_to_pay) || empty($total_paid)) {
        continue;
      }
      return $plan;
    }
    return [];
  }

  /**
   * Get the Payment Plans associated to a given person(Statically Cached).
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   *
   * @return bool|array
   *   The AMNet Response with the the array list of Dues Balance,
   *   otherwise FALSE.
   */
  public function getUserPlansStaticallyCached(UserInterface $user = NULL) {
    if (!$user) {
      return [];
    }
    $stored_entities = &drupal_static(__METHOD__, []);
    // Get the AM.net ID.
    $key = $user->get('field_amnet_id')->getString();
    $key = trim($key);
    if (!isset($stored_entities[$key])) {
      $stored_entities[$key] = $this->getUserPlans($user);
    }
    return $stored_entities[$key];
  }

  /**
   * Get the Payment Plans associated to a given person.
   *
   * @param string $id
   *   The AM.net name Id.
   *
   * @return bool|array
   *   The AMNet Response with the the array list of Dues Balance,
   *   otherwise FALSE.
   */
  public function getUserPlansById($id = NULL) {
    if (empty($id)) {
      return FALSE;
    }
    $id = trim($id);
    $_client = $this->getClient();
    if (!$_client) {
      // The service is not reachable.
      return FALSE;
    }
    $query = $_client->get("person/{$id}/paymentplans");
    $result = [];
    if (!$query->hasError()) {
      $result = $query->getResult();
    }
    return $result;
  }

  /**
   * Get the Payment Plans associated to a given person.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   *
   * @return bool|array
   *   The AMNet Response with the the array list of Dues Balance,
   *   otherwise FALSE.
   */
  public function getUserPlans(UserInterface $user = NULL) {
    if (!$user) {
      return FALSE;
    }
    // Get the AM.net ID.
    $id = $user->get('field_amnet_id')->getString();
    if (empty($id)) {
      return FALSE;
    }
    $id = trim($id);
    $_client = $this->getClient();
    if (!$_client) {
      // The service is not reachable.
      return FALSE;
    }
    $query = $_client->get("person/{$id}/paymentplans");
    $result = [];
    if (!$query->hasError()) {
      $result = $query->getResult();
    }
    return $result;
  }

}
