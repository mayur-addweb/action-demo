<?php

namespace Drupal\vscpa_commerce\Resolver;

use Drupal\am_net_membership\MembershipCheckerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\user\UserInterface;
use Drupal\vscpa_commerce\PriceManagerInterface;

/**
 * Returns the price based on the Self Study Registration price fields.
 */
class SelfStudyRegistrationResolver implements PriceResolverInterface {

  /**
   * The membership checker.
   *
   * @var \Drupal\am_net_membership\MembershipCheckerInterface
   */
  protected $membershipChecker;

  /**
   * The price manager.
   *
   * @var \Drupal\vscpa_commerce\PriceManagerInterface
   */
  protected $priceManager;

  /**
   * Constructs a new EventPriceResolver.
   *
   * @param \Drupal\am_net_membership\MembershipCheckerInterface $am_net_membership_checker
   *   The AM.net membership checker.
   * @param \Drupal\vscpa_commerce\PriceManagerInterface $price_manager
   *   The price manager.
   */
  public function __construct(MembershipCheckerInterface $am_net_membership_checker, PriceManagerInterface $price_manager) {
    $this->membershipChecker = $am_net_membership_checker;
    $this->priceManager = $price_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    if ($entity->bundle() !== 'self_study_registration') {
      return NULL;
    }
    if (!$pricing_options = $this->priceManager->getSelfStudyPricingOptions($entity, $quantity, $context)) {
      return NULL;
    }
    $customer = $context->getCustomer();
    if (($customer instanceof UserInterface) && ($this->membershipChecker->isMemberInGoodStanding($customer) || $customer->hasRole('vscpa_administrator'))) {
      return $pricing_options['member_price'];
    }
    else {
      return $pricing_options['price'];
    }
  }

}
