<?php

namespace Drupal\vscpa_commerce\EventSubscriber;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderItemEvent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rng\RegistrantFactoryInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles registrations for event registration order items.
 */
class CpeOrderRegistrationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The registrant factory.
   *
   * @var \Drupal\rng\RegistrantFactoryInterface
   */
  protected $registrantFactory;

  /**
   * The registration entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $registrationStorage;

  /**
   * Constructs a new EventRegistrationSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rng\RegistrantFactoryInterface $registrant_factory
   *   The registrant factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger_channel
   *   The 'vscpa_commerce' logger channel.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RegistrantFactoryInterface $registrant_factory, LoggerChannelInterface $logger_channel) {
    $this->registrantFactory = $registrant_factory;
    $this->registrationStorage = $entity_type_manager->getStorage('registration');
    $this->logger = $logger_channel;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => ['onOrderPlace'],
      OrderEvents::ORDER_ITEM_PREDELETE => ['beforeOrderItemDelete'],
      OrderEvents::ORDER_UPDATE => ['onOrderUpdate'],
      OrderEvents::ORDER_PREDELETE => ['beforeOrderDelete'],
    ];
  }

  /**
   * Handle the creation of registrations after the order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function onOrderPlace(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    foreach ($order->getItems() as $orderItem) {
      if (!$orderItem->getPurchasedEntity()) {
        continue;
      }
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
      $purchased_entity = $orderItem->getPurchasedEntity();
      if (!$purchased_entity) {
        continue;
      }
      $product = $purchased_entity->getProduct();
      if (!$product) {
        continue;
      }
      switch ($orderItem->bundle()) {
        case 'event_registration':
          $this->tryRegistration($product, $orderItem);
          break;

        case 'self_study_registration':
          $this->tryRegistration($product, $orderItem);
          break;

        case 'session_registration':
          if ($product->hasField('field_session') && $session = $product->field_session->entity) {
            $this->tryRegistration($session, $orderItem);
          }
          break;
      }
    }
  }

  /**
   * Listener for order update events.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function onOrderUpdate(OrderEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();
    if (in_array($order->getState()->getValue()['value'], ['completed'])) {
      foreach ($order->getItems() as $orderItem) {
        if ($registrations = $this->registrationStorage->loadByProperties(['field_order_item' => $orderItem->id()])) {
          continue;
        }
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
        $purchased_entity = $orderItem->getPurchasedEntity();
        if (!$purchased_entity) {
          continue;
        }
        $product = $purchased_entity->getProduct();
        if (!$product) {
          continue;
        }
        switch ($orderItem->bundle()) {
          case 'event_registration':
          case 'self_study_registration':
            $this->tryRegistration($product, $orderItem);
            break;

          case 'session_registration':
            if ($product->hasField('field_session') && $session = $product->field_session->entity) {
              $this->tryRegistration($session, $orderItem);
            }
            break;
        }
      }
    }
  }

  /**
   * Attempt to create a registration for a given event and order item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The event entity.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item that generated the registration.
   */
  protected function tryRegistration(EntityInterface $event, OrderItemInterface $order_item) {
    if ($event->hasField('rng_registration_type') && $registration_type = $event->get('rng_registration_type')->target_id) {
      $context = [
        'event' => $event,
        'order_item' => $order_item,
      ];
      $this->createRegistration($registration_type, $context);
    }
  }

  /**
   * Creates an event registration.
   *
   * @param string $registration_type
   *   The registration type.
   * @param array $context
   *   The context.
   */
  protected function createRegistration($registration_type, array $context) {
    $event = $context['event'];
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $context['order_item'];
    /** @var \Drupal\user\UserInterface $user */
    $user = $order_item->get('field_user')->entity;

    try {
      /** @var \Drupal\rng\RegistrationInterface $registration */
      $registration = $this->registrationStorage->create(['type' => $registration_type]);
      $registration->setEvent($event);
      $registration->set('field_order_item', $order_item->id());
      $registration->save();

      // Create registrant from user.
      $registrant = $this->registrantFactory->createRegistrant($context);
      $registrant->setIdentity($user);
      $registrant->setRegistration($registration);
      $registrant->save();

      $registration->save();

      drupal_set_message($this->t(':name is now registered for :event.', [
        ':name' => $user->label(),
        ':event' => $registration->getEvent()->label(),
      ]));
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());

      drupal_set_message($this->t(':name could not be registered for :event.  Please contact customer support.', [
        ':name' => $user->label(),
        ':event' => $registration->getEvent()->label(),
      ]));
    }
  }

  /**
   * Listener for order delete events.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function beforeOrderDelete(OrderEvent $event) {
    $order = $event->getOrder();
    foreach ($order->getItems() as $order_item) {
      $this->handleRemovedOrderItem($order_item);
    }
  }

  /**
   * Listener for order item delete events.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $order_item_event
   *   The order item event.
   */
  public function beforeOrderItemDelete(OrderItemEvent $order_item_event) {
    $this->handleRemovedOrderItem($order_item_event->getOrderItem());
  }

  /**
   * Handles order item removal.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item being removed.
   */
  public function handleRemovedOrderItem(OrderItemInterface $order_item) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
    if ($entity = $order_item->getPurchasedEntity()) {
      $product = $entity->getProduct();
      switch ($order_item->bundle()) {
        case 'event_registration':
        case 'self_study_registration':
          $this->deleteRegistration($product, $order_item);
          break;

        case 'session_registration':
          if ($product->hasField('field_session') && $session = $product->field_session->entity) {
            $this->deleteRegistration($session, $order_item);
          }
          break;
      }
    }
  }

  /**
   * Deletes a registration for a given event and order item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The event entity.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item that generated the registration.
   */
  protected function deleteRegistration(EntityInterface $event, OrderItemInterface $order_item) {
    // Only act on order items that have an order.
    // Only then will a registration exist.
    if ($order_item->getOrder()) {
      /** @var \Drupal\rng\RegistrationInterface $registration */
      if ($registrations = $this->registrationStorage->loadByProperties([
        'event__target_id' => $event->id(),
        'event__target_type' => $event->getEntityTypeId(),
        'field_order_item' => $order_item->id(),
      ])) {
        foreach ($registrations as $registration) {
          try {
            $registration->delete();
          }
          catch (EntityStorageException $e) {
            $this->logger->error($e->getMessage());

            drupal_set_message($this->t('Registration :id could not be deleted from order :num (order item :item).', [
              ':id' => $registration->id(),
              ':num' => $order_item->getOrder()->getOrderNumber(),
              ':item' => $order_item->id(),
            ]));
          }
        }
      }
    }
  }

}
