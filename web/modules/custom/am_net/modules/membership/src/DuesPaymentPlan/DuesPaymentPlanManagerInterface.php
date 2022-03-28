<?php

namespace Drupal\am_net_membership\DuesPaymentPlan;

/**
 * Defines a common interface for Dues Payment Manager implementation.
 */
interface DuesPaymentPlanManagerInterface {

  /**
   * Gets plan associated with user.
   *
   * @param string $uid
   *   The user ID.
   *
   * @return \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface|null
   *   An plan object. NULL if no matching Dues Plan is found.
   */
  public function get($uid = NULL);

  /**
   * Resets the internal from an static cached 'Plan'.
   *
   * @param string $uid
   *   The user ID.
   */
  public function delete($uid = NULL);

  /**
   * Stores entities in the static entity cache.
   *
   * @param \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface $plan
   *   The dues plan info.
   * @param string $uid
   *   The user ID.
   */
  public function save(DuesPaymentPlanInfoInterface $plan = NULL, $uid = NULL);

}
