<?php

namespace Drupal\am_net_donations\EventSubscriber;

use Drupal\Core\Url;
use Drupal\am_net_donations\DonationManager;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\am_net_donations\Event\DonationEvent;
use Drupal\am_net_donations\Event\DonationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responds to donation events.
 */
class DonationEventSubscriber implements EventSubscriberInterface {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  private $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  private $cartProvider;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  private $currentStore;

  /**
   * The donation manager.
   *
   * @var \Drupal\am_net_donations\DonationManager
   */
  protected $donationManager;

  /**
   * Constructs a new DonationEventSubscriber object.
   *
   * @param \Drupal\am_net_donations\DonationManager $donation_manager
   *   The donation manager.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   */
  public function __construct(DonationManager $donation_manager, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, CurrentStoreInterface $current_store) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->currentStore = $current_store;
    $this->donationManager = $donation_manager;
  }

  /**
   * Adds a donation product to the cart when an donation form is submitted.
   *
   * @param \Drupal\am_net_donations\Event\DonationEvent $event
   *   The donation event.
   */
  public function onDonationSubmission(DonationEvent $event) {
    $user = $event->getAccount();
    $store = $this->currentStore->getStore();
    if (!$cart = $this->cartProvider->getCart('default', $store, $user)) {
      $cart = $this->cartProvider->createCart('default', $store, $user);
    }
    $product = $this->donationManager->getDefaultDonationProduct($event->getDestination());
    if ($product) {
      $variation = $product->getDefaultVariation();
      $order_item = $this->cartManager->createOrderItem($variation, 1)
        ->set('field_donation_anonymous', $event->isAnonymous())
        ->set('field_donation_source', $event->getSource())
        ->set('field_am_net_recurring', $event->isRecurring())
        ->set('field_am_net_recurring_interval', $event->getRecurringInterval())
        ->setUnitPrice($event->getAmount(), TRUE);
      $fund = $event->getFund();
      if (!empty($fund)) {
        $order_item->set('field_fund', $fund);
      }
      $donation_destination = $event->getDestination();
      if (!empty($donation_destination)) {
        $order_item->set('field_donation_destination', $donation_destination);
      }
      $this->cartManager->addOrderItem($cart, $order_item, FALSE);
      $is_anonymous = \Drupal::currentUser()->isAnonymous();
      if ($is_anonymous) {
        $order_id = $cart->id();
        $destination = "/checkout/{$order_id}/order_information";
        $link_options = [
          'query' => ['destination' => $destination],
          'absolute' => TRUE,
        ];
        $url = Url::fromRoute('user.login', [], $link_options);
      }
      else {
        $url = Url::fromRoute('commerce_checkout.form', [
          'commerce_order' => $cart->id(),
          'step' => 'login',
        ]);
      }
      $event->setRedirectUrl($url);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      DonationEvents::SUBMIT_DONATION => ['onDonationSubmission'],
    ];
    return $events;
  }

}
