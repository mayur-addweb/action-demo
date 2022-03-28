<?php

namespace Drupal\vscpa_commerce\EventSubscriber;

use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\vscpa_commerce\PeerReview\Event\PeerReviewPaymentEvent;
use Drupal\vscpa_commerce\PeerReview\Event\PeerReviewPaymentEvents;

/**
 * Responds to the Peer Review Payment events.
 */
class PeerReviewPaymentEventSubscriber implements EventSubscriberInterface {

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
   * Constructs a new DonationEventSubscriber object.
   *
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, CurrentStoreInterface $current_store) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->currentStore = $current_store;
  }

  /**
   * Responds to new Peer Review payment submissions.
   *
   * @param \Drupal\vscpa_commerce\PeerReview\Event\PeerReviewPaymentEvent $event
   *   The membership application event.
   */
  public function onSubmitPeerReviewPayment(PeerReviewPaymentEvent $event) {
    $user = $event->getAccount();
    $store = $this->currentStore->getStore();
    $cart = $this->getCart('default', $store, $user);
    if (!$cart) {
      // Stop Here.
      return;
    }
    $product = $event->getDefaultPeerReviewPaymentProduct();
    if (!$product) {
      // Stop Here.
      return;
    }
    $variation = $product->getDefaultVariation();
    if (!$variation) {
      // Stop Here.
      return;
    }
    $order_item = $this->cartManager->createOrderItem($variation, 1)
      ->set('field_peer_review_transaction', $event->getPeerReviewPaymentInfo())
      ->setUnitPrice($event->getAmount(), TRUE);
    if (!$order_item) {
      // Stop Here.
      return;
    }
    $this->cartManager->addOrderItem($cart, $order_item, FALSE);
    $url = Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $cart->id(),
      'step' => 'login',
    ]);
    $event->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      PeerReviewPaymentEvents::SUBMIT_PAYMENT => ['onSubmitPeerReviewPayment'],
    ];
    return $events;
  }

  /**
   * Gets the cart order for the given store and user.
   *
   * @param string $order_type
   *   The order type ID.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store. If empty, the current store is assumed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The cart order, or NULL if none found.
   */
  public function getCart($order_type, StoreInterface $store = NULL, AccountInterface $account = NULL) {
    $cart = $this->cartProvider->getCart($order_type, $store, $account);
    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type, $store, $account);
    }
    return $cart;
  }

}
