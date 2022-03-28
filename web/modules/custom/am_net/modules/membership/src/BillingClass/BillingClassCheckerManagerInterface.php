<?php

namespace Drupal\am_net_membership\BillingClass;

/**
 * The Billing Class Checker manager interface.
 */
interface BillingClassCheckerManagerInterface {

  /**
   * Adds a Billing Class checker.
   *
   * @param \Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface $billing_class_checker
   *   The Billing Class checker.
   */
  public function addChecker(BillingClassCheckerInterface $billing_class_checker);

  /**
   * Get selected Checker relevant for the entity.
   *
   * @return \Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface
   *   The appropriate Billing Class checker for the customer entity.
   */
  public function getChecker();

  /**
   * Get a checker relevant for the entity.
   *
   * @param string $checker_id
   *   The checker Id.
   *
   * @return \Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface
   *   The appropriate Billing Class checker for the given checker Id.
   */
  public function getCheckerById($checker_id = NULL);

  /**
   * Returns an array of all registered Billing Class checkers.
   *
   * @return \Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface[]
   *   All registered Billing Class checkers keyed by checker ID.
   */
  public function listCheckers();

  /**
   * Returns an array of the IDs of all registered Billing Class checkers.
   *
   * @return array
   *   Array of the IDs of all registered Billing Class checkers.
   *   Format is: ['checker key' => 'checker name']
   */
  public function listCheckerIds();

}
