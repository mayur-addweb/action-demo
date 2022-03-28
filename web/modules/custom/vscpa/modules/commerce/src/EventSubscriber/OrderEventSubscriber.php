<?php

namespace Drupal\vscpa_commerce\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderAssignEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responds to order events.
 */
class OrderEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_ASSIGN => ['onOrderAssign'],
    ];
  }

  /**
   * Responds to Order assign events.
   *
   * @param \Drupal\commerce_order\Event\OrderAssignEvent $event
   *   The order assign event.
   */
  public function onOrderAssign(OrderAssignEvent $event) {
    $order = $event->getOrder();
    $account = $event->getAccount();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $item */
    foreach ($order->getItems() as $item) {
      // Update registration order items when order account assignment changes.
      if ($item->bundle() === 'event_registration' || $item->bundle() === 'session_registration') {
        try {
          $item->set('field_user', $account)->save();
        }
        catch (\Exception $e) {
          continue;
        }
      }
    }
  }

}
