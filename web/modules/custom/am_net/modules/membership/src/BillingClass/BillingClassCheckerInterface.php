<?php

namespace Drupal\am_net_membership\BillingClass;

use Drupal\user\UserInterface;

/**
 * Defines a common interface for Billing Class checking.
 */
interface BillingClassCheckerInterface {

  /**
   * Gets the name of the checker.
   */
  public function getName();

  /**
   * Gets the id of the checker.
   */
  public function getId();

  /**
   * Gets the Billing Class Code.
   *
   * @param \Drupal\user\UserInterface $entity
   *   The customer entity.
   *
   * @return int
   *   The Billing Class level based on customer fields.
   */
  public function getCode(UserInterface $entity);

  /**
   * Description of the Billing Class code logic.
   *
   * Provide online user help for know what is the logic associated
   * with each Billing Class code according to the user's field values.
   *
   * @return array
   *   The render array.
   */
  public function getHelp();

}
