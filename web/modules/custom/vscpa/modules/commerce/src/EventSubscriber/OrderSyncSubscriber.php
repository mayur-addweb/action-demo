<?php

namespace Drupal\vscpa_commerce\EventSubscriber;

use Drupal\vscpa_commerce\AmNetSyncManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles AM.net synchronizations for Drupal Commerce orders.
 */
class OrderSyncSubscriber implements EventSubscriberInterface {

  /**
   * The AM.net sync manager.
   *
   * @var \Drupal\vscpa_commerce\AmNetSyncManagerInterface
   */
  protected $syncManager;

  /**
   * Constructs a new EventRegistrationSubscriber object.
   *
   * @param \Drupal\vscpa_commerce\AmNetSyncManagerInterface $am_net_sync_manager
   *   The AM.net sync manager.
   */
  public function __construct(AmNetSyncManagerInterface $am_net_sync_manager) {
    $this->syncManager = $am_net_sync_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => ['onOrderPlace'],
    ];
  }

  /**
   * Handles the sync of an order with AM.net after the order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function onOrderPlace(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->syncManager->pushOrder($order);
  }

}
