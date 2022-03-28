<?php

namespace Drupal\vscpa_commerce\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartOrderItemRemoveEvent;
use Drupal\commerce_cart\Event\OrderItemComparisonFieldsEvent;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Link;
use Drupal\commerce_price\Price;
use Drupal\commerce\Context;
use Drupal\vscpa_commerce\PriceManagerInterface;

/**
 * Responds to cart events.
 */
class CartEventSubscriber implements EventSubscriberInterface {

  use AMNetAdjustmentTrait;
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
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, CurrentStoreInterface $current_store, EntityTypeManagerInterface $entity_type_manager) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->currentStore = $current_store;
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CartEvents::ORDER_ITEM_COMPARISON_FIELDS => ['onOrderItemComparisonFields'],
      CartEvents::CART_ENTITY_ADD => ['onAddToCart', 2],
      CartEvents::CART_ORDER_ITEM_REMOVE => ['onOrderItemRemove'],
    ];
  }

  /**
   * Adds the 'User' field to the list of fields to compare for combination.
   *
   * @param \Drupal\commerce_cart\Event\OrderItemComparisonFieldsEvent $event
   *   The order item comparison fields event.
   */
  public function onOrderItemComparisonFields(OrderItemComparisonFieldsEvent $event) {
    $order_item = $event->getOrderItem();
    $comparison_fields = $event->getComparisonFields();
    if ($order_item->hasField('field_user')) {
      $comparison_fields[] = 'field_user';
    }
    $event->setComparisonFields($comparison_fields);
  }

  /**
   * Responds to Add to cart events.
   *
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *   The entity add to cart event.
   *
   * @throws \Exception
   */
  public function onAddToCart(CartEntityAddEvent $event) {
    $order_item = $event->getOrderItem();
    $quantity = $order_item->getQuantity();
    if ($quantity > 1) {
      $order_item->setQuantity(1);
      $order_item->save();
    }

    if ($order_item->hasField('field_sessions_selected')) {
      $user = $order_item->field_user->entity;
      $cart = $event->getCart();
      foreach ($order_item->field_sessions_selected as $session) {
        if ($session->entity->hasField('field_session_product') && $product = $session->entity->field_session_product->entity) {
          // Add Session selected products.
          $this->addSessionProductToCart($product, $cart, $user, $order_item);
        }
      }
    }
    // Prevent Duplicated Event registration & product purchased.
    $order = $event->getCart();
    $items = $order->getItems();
    $reference_order_item_delta = $this->generateOrderItemDelta($order_item);
    $reference_order_item_id = $order_item->id();
    foreach ($items as $key => $item) {
      $item_delta = $this->generateOrderItemDelta($item);
      if (($reference_order_item_delta == $item_delta) && ($item->id() != $reference_order_item_id)) {
        // Product purchase duplicated - removed it.
        $this->cartManager->removeOrderItem($order, $item);
      }
    }

    // Check orderitem bundle and pass to applyMemberPriceOnCart
    if($order_item->bundle() === 'event_registration' || $order_item->bundle() === 'membership') {
      $this->applyMemberPriceOnCart($event);
    }

    if ($order_item->bundle() === 'event_registration') {
      // Add Special Adjustments to order item if applies.
      $discount_key = 'SeminarVolumeDiscount';
      if ($this->appliesToDiscount($discount_key, $items)) {
        $this->applyDiscount($discount_key, $items);
      }
      // Add AICPA Discount to order item if applies.
      $discount_key = 'AICPADiscount';
      if ($this->appliesToDiscount($discount_key, $items)) {
        $this->applyDiscount($discount_key, $items);
      }
      // Add Goodwill Discount to order item if applies.
      $discount_key = 'GoodwillDiscount';
      if ($this->appliesToDiscount($discount_key, $items)) {
        $this->applyDiscount($discount_key, $items);
      }
    }
    // Change the Add to Cart message.
    $messenger = \Drupal::messenger();
    /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    $purchased_entity = $event->getEntity();
    $product = $purchased_entity->getProduct();
    $label = $product ? $product->label() : $purchased_entity->label();
    $type = 'status';
    $message = t('@entity added to @cart-link.', [
      '@entity' => $label,
      '@cart-link' => Link::createFromRoute(t('your cart', [], ['context' => 'cart link']), 'commerce_cart.page')
        ->toString(),
    ]);
    $messenger->addMessage($message, $type, FALSE);
    $event->stopPropagation();
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

    // Check 'membership' order item
    if ($order_item->bundle() === 'membership') {
      $this->removeMemberPriceOnCart($event);
    }

    // Remove session registration order item(s) when a [parent] event
    // registration is removed from the cart.
    if ($order_item->bundle() === 'event_registration') {
      if ($session_item_ids = $this->orderItemStorage->getQuery()->condition('field_order_item', $order_item->id())->execute()) {
        foreach ($this->orderItemStorage->loadMultiple($session_item_ids) as $session_order_item) {
          $this->cartManager->removeOrderItem($cart, $session_order_item);
        }
      }
      // Remove Special Adjustments if applies.
      $order = $event->getCart();
      $items = $order->getItems();
      $discount_key = 'SeminarVolumeDiscount';
      if (!$this->appliesToDiscount($discount_key, $items)) {
        $this->removeDiscount($discount_key, $items);
      }
      // Remove AICPA Discount to order item if applies.
      $discount_key = 'AICPADiscount';
      if (!$this->appliesToDiscount($discount_key, $items)) {
        $this->removeDiscount($discount_key, $items);
      }
    }
    // Remove session selection from event registration order item if a product
    // (required) for a session is removed from the cart.
    if ($order_item->bundle() === 'session_registration') {
      if ($order_item->hasField('field_order_item') && $event_order_item = $order_item->field_order_item->entity) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $session_product_variation */
        $session_product_variation = $order_item->getPurchasedEntity();
        $product = $session_product_variation->getProduct();
        if ($product->hasField('field_session') && $session = $product->field_session->entity) {
          /** @var \Drupal\vscpa_commerce\Entity\EventSessionInterface $session */
          /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $session_selections */
          $session_selections = $event_order_item->field_sessions_selected;
          foreach ($event_order_item->field_sessions_selected->getValue() as $i => $selection) {
            if ($selection['target_id'] == $session->id()) {
              $session_selections->removeItem($i);
              $event_product = $event_order_item->getPurchasedEntity()->getProduct();
              if (isset($event_product->field_event->entity)) {
                $event_registration = $event_product->field_event->entity->label();
                drupal_set_message(t(':session removed from your :event_registration schedule.', [
                  ':session' => $session->label(),
                  ':event_registration' => $event_registration,
                ]));
              }
            }
          }
          $event_order_item->field_sessions_selected = $session_selections;
        }
      }
    }
  }

  /**
   * Adds a session registration product to the cart.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The session registration product.
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart.
   * @param \Drupal\user\UserInterface $user
   *   The user selected to attend the session.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $event_order_item
   *   The order item of the event registration product that generated this.
   */
  protected function addSessionProductToCart(ProductInterface $product, OrderInterface $cart, UserInterface $user, OrderItemInterface $event_order_item) {
    // There will only be one variation, since the user will not be manually
    // selecting their variation.
    // @todo: Ensure only one variation per event registration product exists.
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = current($product->getVariations());

    // Do not combine so session registration fees are broken out, if multiple
    // event registrations are generating multiple of the same session
    // registrations e.g. for registering multiple people to the same session.
    $order_item = $this->cartManager->addEntity($cart, $variation, 1, FALSE);

    // Set the user and event order item reference from the event registration.
    $order_item->set('field_user', $user);
    $order_item->set('field_order_item', $event_order_item);

    try {
      $order_item->save();
    }
    catch (\Exception $e) {
      drupal_set_message(t(':variation could not be added to cart.  Please contact customer support.', [
        ':variation' => $variation->label(),
      ]));
    }
  }

  /**
   * Gets or creates a cart for the given store and order type.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param string $order_type_id
   *   The order type id.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   A cart.
   */
  protected function getCart(StoreInterface $store, $order_type_id) {
    $cart = $this->cartProvider->getCart($order_type_id, $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type_id, $store);
    }

    return $cart;
  }

  /**
   * Selects the store for the given purchasable entity.
   *
   * If the entity is sold from one store, then that store is selected.
   * If the entity is sold from multiple stores, and the current store is
   * one of them, then that store is selected.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The entity being added to cart.
   *
   * @throws \Exception
   *   When the entity can't be purchased from the current store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The selected store.
   */
  protected function selectStore(PurchasableEntityInterface $entity) {
    $stores = $entity->getStores();
    if (count($stores) === 1) {
      $store = reset($stores);
    }
    elseif (count($stores) === 0) {
      // Malformed entity.
      throw new \Exception('The given entity is not assigned to any store.');
    }
    else {
      $store = $this->currentStore->getStore();
      if (!in_array($store, $stores)) {
        // Indicates that the site listings are not filtered properly.
        throw new \Exception("The given entity can't be purchased from the current store.");
      }
    }

    return $store;
  }

  /**
   * Generate Order Item Delta.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return string|null
   *   The order item generated delta.
   */
  public function generateOrderItemDelta(OrderItemInterface $order_item = NULL) {
    if (!$order_item) {
      return NULL;
    }
    if (!$order_item->hasField('field_user')) {
      return NULL;
    }
    $reference_order_item_uid = $order_item->get('field_user')->getString();
    $reference_order_item_purchased_entity = $order_item->get('purchased_entity')->getString();
    $reference_order_item_type = $order_item->get('type')->getString();
    $delta = "{$reference_order_item_uid}.{$reference_order_item_purchased_entity}.{$reference_order_item_type}";
    return $delta;
  }

  /**
   * Remove session registration order item(s).
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The order.
   */
  public function removeSessionRegistrationOrderItem(OrderItemInterface $order_item = NULL, OrderInterface $cart = NULL) {
    if (!$order_item || !$cart) {
      return;
    }
    if ($order_item->bundle() != 'event_registration') {
      return;
    }
    $session_item_ids = $this->orderItemStorage->getQuery()
      ->condition('field_order_item', $order_item->id())
      ->execute();
    if (empty($session_item_ids)) {
      return;
    }
    $session_order_items = $this->orderItemStorage->loadMultiple($session_item_ids);
    if (empty($session_order_items)) {
      return;
    }
    /* @var \Drupal\commerce_order\Entity\OrderItemInterface $session_order_item */
    // Remove session registration order item(s) when a [parent] event
    // registration is updated from the cart.
    foreach ($session_order_items as $session_order_item) {
      $this->cartManager->removeOrderItem($cart, $session_order_item);
    }
  }

  /**
   * Apply Member Price on Event Registrations for cart items if mebership item_
   * present in the cart
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *   The cart event.
   */
  public function applyMemberPriceOnCart(CartEntityAddEvent $event)
  {
    // check member status and return
    $order_item = $event->getOrderItem();
    $user = $order_item->field_user->entity;
    $memberShip = trim($user->get('field_member_status')->getValue()[0]['value']);
    if ($memberShip == "M") {
      return;
    }

    $order = $event->getCart();
    $items = $order->getItems();
    $product_types = [];
    foreach ($items as $key => $item) {
      $product_types[]=trim($item->get('type')->getString());
    }

    if(count($product_types)>0) {
      if(in_array("membership", $product_types)) {

        foreach ($items as $key => $item) {
          $item_type = trim($item->get('type')->getString());
          if($item_type === "event_registration") {
            $purchased_entity = $item->getPurchasedEntity();

            $current_user = \Drupal::currentUser();
            $context = new Context($current_user, $this->currentStore->getStore());

            $price_manager = \Drupal::service('vscpa_commerce.price_manager');
            $pricing_options = $price_manager->getEventPricingOptions($purchased_entity, 1, $context);
            $member_price = $pricing_options['current_option']['member_price'];
            $item->setUnitPrice($member_price, TRUE);
          }
        }

      }

    }

  }

  /**
   * Apply nonmember Price for Event Registrations in cart if_
   * membership remove from the cart
   * @param \Drupal\commerce_cart\Event\CartOrderItemRemoveEvent $event
   *   The cart event.
   */

  public function removeMemberPriceOnCart(CartOrderItemRemoveEvent $event)
  {
    // check member status and return
    $order_item = $event->getOrderItem();
    $user = $order_item->field_user->entity;
    $memberShip = trim($user->get('field_member_status')->getValue()[0]['value']);
    if ($memberShip == "M") {
      return;
    }

    $order = $event->getCart();
    foreach ($order->getItems() as $key => $item) {
      $item_type = trim($item->get('type')->getString());
      if($item_type === "event_registration") {

        $purchased_entity = $item->getPurchasedEntity();
        $current_user = \Drupal::currentUser();
        $context = new Context($current_user, $this->currentStore->getStore());

        $price_manager = \Drupal::service('vscpa_commerce.price_manager');
        $pricing_options = $price_manager->getEventPricingOptions($purchased_entity, 1, $context);
        $non_member_price = $pricing_options['current_option']['price'];
        $item->setUnitPrice($non_member_price, TRUE);

      }
    }

  }

}
