<?php

namespace Drupal\am_net_membership\BillingClass;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The Billing Class checker manager.
 *
 * Responsible for handling Billing Code checkers.
 *
 * @package Drupal\am_net_membership\BillingClass
 */
class BillingClassCheckerManager implements BillingClassCheckerManagerInterface {

  /**
   * The Billing Class checkers.
   *
   * @var \Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface[]
   */
  protected $billingClassCheckers = [];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a BillingClassCheckerManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function addChecker(BillingClassCheckerInterface $billing_class_checker) {
    $this->billingClassCheckers[$billing_class_checker->getId()] = $billing_class_checker;
  }

  /**
   * {@inheritdoc}
   */
  public function getChecker() {
    $config = $this->configFactory->get('am_net_membership.billing_class_checker_manager');
    $checker_id = $config->get('default_checker_id');
    return $this->billingClassCheckers[$checker_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckerById($checker_id = NULL) {
    $checker = FALSE;
    if (!is_null($checker_id)) {
      $checker = $this->billingClassCheckers[$checker_id];
    }
    return $checker;
  }

  /**
   * {@inheritdoc}
   */
  public function listCheckers() {
    return $this->billingClassCheckers;
  }

  /**
   * {@inheritdoc}
   */
  public function listCheckerIds() {
    $ids = [];
    foreach ($this->billingClassCheckers as $checker) {
      $ids[$checker->getId()] = $checker->getName();
    }
    return $ids;
  }

}
