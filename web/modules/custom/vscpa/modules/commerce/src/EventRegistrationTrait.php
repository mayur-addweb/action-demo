<?php

namespace Drupal\vscpa_commerce;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Event Registration Helper trait implementation.
 */
trait EventRegistrationTrait {

  /**
   * Check if is Event Registration status Open.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The event product variation.
   *
   * @return bool
   *   TRUE if the Event Registration status is Open otherwise FALSE.
   */
  public function isEventRegistrationOpen(PurchasableEntityInterface $purchased_entity = NULL) {
    /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    if (!$purchased_entity) {
      return FALSE;
    }
    $product = $purchased_entity->getProduct();
    if (!$product) {
      return FALSE;
    }
    return $this->isEventProductOpenForRegistration($product);
  }

  /**
   * Check if is Event Product status Open.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The event product entity.
   *
   * @return bool
   *   TRUE if the Event Registration status is Open otherwise FALSE.
   */
  public function isEventProductOpenForRegistration(ProductInterface $product = NULL) {
    return vscpa_commerce_is_event_product_open_for_registration($product);
  }

  /**
   * Get the 'Discount Off Label' associated with an event.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The event product variation.
   *
   * @return array|null
   *   The "Discount Off Label" render array, otherwise NULL.
   */
  public function getEventProductDiscountOffLabel(PurchasableEntityInterface $purchased_entity = NULL) {
    /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    if (!$purchased_entity) {
      return NULL;
    }
    $product = $purchased_entity->getProduct();
    if (!$product) {
      return NULL;
    }
    $field_name = 'field_am_net_adjustment';
    if (!$product->hasField($field_name)) {
      return NULL;
    }
    $adjustments = $product->get($field_name)->getString();
    if (empty($adjustments)) {
      return NULL;
    }
    $target_adjustment_code = 'DP';
    $adjustments = !empty($adjustments) ? json_decode($adjustments, TRUE) : [];
    foreach ($adjustments as $delta => $adjustment) {
      $applies_by_code = isset($adjustment['Ty2']) && ($adjustment['Ty2'] == $target_adjustment_code);
      if (!$applies_by_code) {
        continue;
      }
      $percentage_amount = $adjustment['Amount'] ?? 0;
      return [
        '#markup' => '<div class="discount-off-description"><span>(' . $percentage_amount . '% discount will be applied at checkout!)</span></div>',
        '#allowed_tags' => ['div', 'class', 'span'],
      ];
    }
    return NULL;
  }

  /**
   * Get the trend badge class associated with an event.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The event product variation.
   *
   * @return string|null
   *   The badge class, otherwise NULL.
   */
  public function getEventProductBadgeEventClass(PurchasableEntityInterface $purchased_entity = NULL) {
    /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    if (!$purchased_entity) {
      return FALSE;
    }
    $product = $purchased_entity->getProduct();
    if (!$product) {
      return FALSE;
    }
    $field_name = 'field_amnet_event_id';
    if (!$product->hasField($field_name)) {
      return NULL;
    }
    $am_net_event_id = $product->get($field_name)->getValue();
    $am_net_event_id = is_array($am_net_event_id) ? current($am_net_event_id) : NULL;
    $event_code = $am_net_event_id['code'] ?? NULL;
    $event_year = $am_net_event_id['year'] ?? NULL;
    if (empty($event_code) || empty($event_year)) {
      return NULL;
    }
    return \Drupal::service('am_net_cpe.product_manager')->getEventBadgeClass($event_code, $event_year);
  }

  /**
   * Get external registration Url.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The event product variation.
   *
   * @return string
   *   The external registration url, otherwise FALSE.
   */
  public function getExternalRegistrationUrl(PurchasableEntityInterface $purchased_entity = NULL) {
    /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    if (!$purchased_entity) {
      return FALSE;
    }
    $product = $purchased_entity->getProduct();
    if (!$product) {
      return FALSE;
    }
    $field_name = 'field_event_external';
    if (!$product->hasField($field_name)) {
      return FALSE;
    }
    $field_value = $product->get($field_name)->getValue();
    $field_value = is_array($field_value) ? current($field_value) : FALSE;
    if (!isset($field_value['uri'])) {
      return FALSE;
    }
    return $field_value['uri'];
  }

  /**
   * Check if the order item is related to an Event Registration.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return bool
   *   TRUE if the order item is related to an Event Registration
   *   otherwise FALSE.
   */
  public function isEventRegistration(OrderItemInterface $order_item = NULL) {
    if (!$order_item) {
      return FALSE;
    }
    return in_array($order_item->bundle(), [
      'event_registration',
    ]);
  }

  /**
   * Check if the order item is related to an Event or product Registration.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return bool
   *   TRUE if the order item is related to an Event Registration
   *   otherwise FALSE.
   */
  public function isEventOrProductRegistration(OrderItemInterface $order_item = NULL) {
    if (!$order_item) {
      return FALSE;
    }
    return in_array($order_item->bundle(), [
      'event_registration',
      'self_study_registration',
    ]);
  }

}
