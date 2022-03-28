<?php

namespace Drupal\am_net_membership\EventSubscriber;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartOrderItemRemoveEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responds to cart events.
 */
class CartEventSubscriber implements EventSubscriberInterface {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

  /**
   * CartEventSubscriber constructor.
   *
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(CartManagerInterface $cart_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->cartManager = $cart_manager;
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CartEvents::CART_ORDER_ITEM_REMOVE => ['onOrderItemRemove'],
    ];
  }

  /**
   * Responds to order item remove events.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemRemoveEvent $event
   *   The order item remove event.
   */
  public function onOrderItemRemove(CartOrderItemRemoveEvent $event) {
    $order_item = $event->getOrderItem();
    $cart = $event->getCart();
    // Remove membership donation order item(s) when a [parent] membership
    // order item is removed from the cart.
    if ($order_item->bundle() === 'membership') {
      $attached_item_ids = $this->orderItemStorage->getQuery()
        ->condition('field_order_item', $order_item->id())
        ->execute();
      if ($attached_item_ids) {
        /* @var \Drupal\commerce_order\Entity\OrderItemInterface $donation_order_item */
        foreach ($this->orderItemStorage->loadMultiple($attached_item_ids) as $donation_order_item) {
          $this->cartManager->removeOrderItem($cart, $donation_order_item);
        }
      }
    }
  }

}
