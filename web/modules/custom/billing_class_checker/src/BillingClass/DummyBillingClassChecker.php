<?php

namespace Drupal\billing_class_checker\BillingClass;

use Drupal\user\UserInterface;
use Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface;

/**
 * The Dummy Billing Class checker implementation.
 */
class DummyBillingClassChecker implements BillingClassCheckerInterface {

  /**
   * Get the ID of the checker.
   */
  public function getId() {
    return 'dummy_billing_class';
  }

  /**
   * Get the name of the checker.
   */
  public function getName() {
    return 'Dummy Billing Class Handler';
  }

  /**
   * {@inheritdoc}
   */
  public function getCode(UserInterface $user) {
    $billingClassCode = FALSE;
    return $billingClassCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return [
      '#type' => 'item',
      '#markup' => '<h3>Billing Classes - The Help Info goes here.</h3>',
    ];
  }

}
