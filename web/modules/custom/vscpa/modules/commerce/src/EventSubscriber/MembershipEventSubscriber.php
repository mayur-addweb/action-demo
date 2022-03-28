<?php

namespace Drupal\vscpa_commerce\EventSubscriber;

use Drupal\am_net\AmNetData;
use Drupal\commerce_price\Price;
use Drupal\am_net_donations\DonationManager;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\am_net_membership\Event\MembershipEvents;
use Drupal\am_net_membership\MembershipCheckerInterface;
use Drupal\am_net_membership\Event\MembershipRenewalEvent;
use Drupal\am_net_membership\Event\MembershipApplicationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responds to membership events.
 */
class MembershipEventSubscriber implements EventSubscriberInterface {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The donation manager.
   *
   * @var \Drupal\am_net_donations\DonationManager
   */
  protected $donationManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The membership service manager.
   *
   * @var \Drupal\am_net_membership\MembershipCheckerInterface
   */
  protected $membershipChecker;

  /**
   * The 'Dues Payment Plan' Manager.
   *
   * @var \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanManagerInterface
   */
  protected $duesPaymentPlanManager;

  /**
   * Constructs a new MembershipEventSubscriber object.
   *
   * @param \Drupal\am_net_membership\MembershipCheckerInterface $membership_checker
   *   The membership service manager.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\am_net_donations\DonationManager $donation_manager
   *   The donation manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(MembershipCheckerInterface $membership_checker, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, CurrentStoreInterface $current_store, DonationManager $donation_manager, EventDispatcherInterface $event_dispatcher) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->currentStore = $current_store;
    $this->donationManager = $donation_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->membershipChecker = $membership_checker;
  }

  /**
   * Responds to new membership application submissions.
   *
   * @param \Drupal\am_net_membership\Event\MembershipApplicationEvent $event
   *   The membership application event.
   */
  public function onSubmitApplication(MembershipApplicationEvent $event) {
    $this->addMembershipAndDonationProducts($event);
  }

  /**
   * Responds to new membership renewal submissions.
   *
   * @param \Drupal\am_net_membership\Event\MembershipRenewalEvent $event
   *   The membership renewal event.
   */
  public function onSubmitRenewal(MembershipRenewalEvent $event) {
    $this->addMembershipAndDonationProducts($event);
  }

  /**
   * Adds to cart membership and donation products for membership submissions.
   *
   * @param \Drupal\am_net_membership\Event\MembershipApplicationEvent|\Drupal\am_net_membership\Event\MembershipRenewalEvent $event
   *   A membership application or renewal event.
   */
  protected function addMembershipAndDonationProducts($event) {
    $user = $event->getAccount();
    $cart_owner = $event->getCartOwnerAccount();
    $store = $this->currentStore->getStore();
    if (!$cart = $this->cartProvider->getCart('default', $store, $cart_owner)) {
      $cart = $this->cartProvider->createCart('default', $store, $cart_owner);
    }
    // Remove duplicate membership order items before add the new one.
    $current_order_items = $cart->getItems();
    if (!empty($current_order_items)) {
      foreach ($current_order_items as $cart_order_item) {
        $cart_order_item_field_user = $cart_order_item->get('field_user')->getString();
        $remove_order_item = ($cart_order_item->bundle() == 'membership') && ($cart_order_item_field_user == $user->id());
        if ($remove_order_item) {
          $this->cartManager->removeOrderItem($cart, $cart_order_item);
        }
      }
    }
    // Add the Membership into the Cart.
    if ($event->enrollPaymentPlan()) {
      $this->doAddMembershipToCartWithPaymentPlan($event, $cart);
    }
    else {
      $this->doAddMembershipToCart($event, $cart);
    }
  }

  /**
   * Do add membership to cart with dues payment plan.
   *
   * @param \Drupal\am_net_membership\Event\MembershipApplicationEvent|\Drupal\am_net_membership\Event\MembershipRenewalEvent $event
   *   A membership application or renewal event with dues payment plan.
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart Instance.
   */
  protected function doAddMembershipToCartWithPaymentPlan($event, OrderInterface $cart) {
    $plan = $event->getPaymentPlanInfo();
    $user = $event->getAccount();
    $uid = $user->id();
    $variation = $this->membershipChecker->getDefaultMembershipProduct()->getDefaultVariation();
    $membership_order_item = $this->cartManager->createOrderItem($variation, 1);
    $membership_order_item->set('field_user', $uid);
    // Override Unit Price here to be able to use a different user that the
    // current user in the Price Resolver.
    $membership_order_item->setUnitPrice($plan->getFirstInstallmentMembershipPrice(), TRUE);
    // Set 'Dues Payment Plan' Info.
    $info = new AmNetData($uid, $uid, $plan->toArray());
    $membership_order_item->set('field_payment_plan_info', $info);
    // Add Membership order item to the Cart.
    $this->cartManager->addOrderItem($cart, $membership_order_item, $combine = FALSE);
    // Handle Donations.
    $donations = $event->getDonations();
    if (empty($donations)) {
      // There are no donations, stop here.
      return;
    }
    $zero = new Price(0, 'USD');
    foreach ($donations as $donation) {
      $amount = $plan->getFirstInstallmentMonthlyContribution($donation['destination']);
      if (!$amount->greaterThan($zero)) {
        continue;
      }
      $donation_variation = $this->donationManager->getDefaultMembershipDonationProduct($donation['destination'])->getDefaultVariation();
      $donation_order_item = $this->cartManager
        ->createOrderItem($donation_variation, 1)
        ->set('field_order_item', $membership_order_item->id())
        ->set('field_user', $uid)
        ->set('field_donation_anonymous', $donation['anonymous'])
        ->set('field_donation_source', $donation['source'])
        ->setUnitPrice($amount, TRUE);
      $this->cartManager->addOrderItem($cart, $donation_order_item, FALSE);
    }
    // Clear 'Payment Plan Cache' related to this user.
    $this->getDuesPaymentPlanManager()->delete($uid);
  }

  /**
   * Do add membership to cart.
   *
   * @param \Drupal\am_net_membership\Event\MembershipApplicationEvent|\Drupal\am_net_membership\Event\MembershipRenewalEvent $event
   *   A membership application or renewal event.
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart Instance.
   */
  protected function doAddMembershipToCart($event, OrderInterface $cart) {
    $user = $event->getAccount();
    $variation = $this->membershipChecker->getDefaultMembershipProduct()->getDefaultVariation();
    $order_item = $this->cartManager->createOrderItem($variation, 1)->set('field_user', $user->id());
    // Override Unit Price here to be able to use a different user that the
    // current user in the Price Resolver.
    $membership_amount = $this->membershipChecker->getMembershipPrice($user);
    $price = new Price($membership_amount, 'USD');
    $order_item->setUnitPrice($price, TRUE);
    // Add Membership order item to the Cart.
    $this->cartManager->addOrderItem($cart, $order_item, $combine = FALSE);
    // Handle Donations.
    $donations = $event->getDonations();
    if (empty($donations)) {
      // There are no donations, stop here.
      return;
    }
    foreach ($donations as $donation) {
      $donation_variation = $this->donationManager->getDefaultMembershipDonationProduct($donation['destination'])->getDefaultVariation();
      $donation_order_item = $this->cartManager
        ->createOrderItem($donation_variation, 1)
        ->set('field_order_item', $order_item->id())
        ->set('field_user', $user->id())
        ->set('field_donation_anonymous', $donation['anonymous'])
        ->set('field_donation_source', $donation['source'])
        ->setUnitPrice($donation['amount'], TRUE);
      $this->cartManager->addOrderItem($cart, $donation_order_item, FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      MembershipEvents::SUBMIT_APPLICATION => ['onSubmitApplication'],
      MembershipEvents::SUBMIT_RENEWAL => ['onSubmitRenewal'],
    ];

    return $events;
  }

  /**
   * Gets dues payment plan manager.
   *
   * @return \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanManagerInterface
   *   The dues payment plan manager.
   */
  public function getDuesPaymentPlanManager() {
    if (!$this->duesPaymentPlanManager) {
      $this->duesPaymentPlanManager = \Drupal::service('am_net_membership.payment_plans.manager');
    }
    return $this->duesPaymentPlanManager;
  }

}
