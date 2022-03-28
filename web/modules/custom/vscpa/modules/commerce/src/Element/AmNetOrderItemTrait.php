<?php

namespace Drupal\vscpa_commerce\Element;

use Drupal\vscpa_commerce\AmNetOrderInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Wrapper methods for Order Item Types.
 */
trait AmNetOrderItemTrait {

  /**
   * The record Ids.
   *
   * @var array
   */
  protected static $recordID = [];

  /**
   * The identifications settings.
   *
   * @var array
   */
  protected static $identifications = [];

  /**
   * The order item Sync Status.
   *
   * @var array
   */
  protected static $orderItemSyncStatus = [];

  /**
   * Get AM.net ID Label.
   *
   * @return string
   *   The AM.net ID Label.
   */
  public static function getAmNetIdLabel() {
    return '<strong>AM.net ID:</strong> ';
  }

  /**
   * Gets the Order Item Title.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The Product entity.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity
   *   The Purchased entity.
   *
   * @return string
   *   The Order Item Title.
   */
  public static function getOrderItemTitle(ProductInterface $product, ProductVariationInterface $purchased_entity) {
    $title = [];
    $prefix = 'ORDER ITEM SYNC: ';
    if ($product) {
      $title[] = $product->label();
    }
    if ($purchased_entity) {
      $label = $purchased_entity->label();
      if (!in_array($label, $title)) {
        $title[] = $label;
      }
    }
    return $prefix . implode(' - ', $title);
  }

  /**
   * Get Sync Status Description.
   *
   * @param string $order_item_key
   *   Order item key.
   *
   * @return string
   *   The Sync Status Description.
   */
  public static function getSyncStatusDesc($order_item_key = '') {
    $sync_status = self::getSyncStatus($order_item_key);
    $default = t("Order Item not Synchronized.");
    $sync_status_desc = $default;
    if (!empty($sync_status)) {
      switch ($sync_status) {
        case AmNetOrderInterface::ORDER_ITEM_NOT_SYNCHRONIZED:
          $sync_status_desc = $default;
          break;

        case AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED:
          $sync_status_desc = t("Order Item Synchronized.");
          break;

      }
    }
    return $sync_status_desc;
  }

  /**
   * Gets the order items types.
   *
   * @return array
   *   The list of order items types.
   */
  public static function getOrderItemsTypes() {
    return [
      AmNetOrderInterface::ORDER_ITEM_TYPE_MEMBERSHIP => t('Membership'),
      AmNetOrderInterface::ORDER_ITEM_TYPE_DONATION => t('Donation'),
      AmNetOrderInterface::ORDER_ITEM_TYPE_EVENT_REGISTRATION => t('Event Registration'),
      AmNetOrderInterface::ORDER_ITEM_TYPE_SELF_STUDY_REGISTRATION => t('Self-study registration'),
      AmNetOrderInterface::ORDER_ITEM_TYPE_PEER_REVIEW_PAYMENT => t('Peer Review Payment'),
      AmNetOrderInterface::ORDER_ITEM_TYPE_UNDEFINED => t('Undefined'),
    ];
  }

  /**
   * Get Order Type.
   *
   * @param string $key
   *   Order item key.
   *
   * @return int
   *   The Order Type.
   */
  public static function getOrderItemType($key = '') {
    $order_item_types = self::getOrderItemsTypes();
    return isset($order_item_types[$key]) ? $order_item_types[$key] : NULL;
  }

  /**
   * Gets the Order Item Sync Status.
   *
   * @param string $key
   *   Order item key.
   *
   * @return bool
   *   The Sync Status.
   */
  public static function getSyncStatus($key = '') {
    return isset(self::$orderItemSyncStatus[$key]) ? self::$orderItemSyncStatus[$key] : NULL;
  }

  /**
   * Determine order item type.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
   *   The Purchased entity.
   *
   * @return string
   *   The order type.
   */
  public static function determineOrderItemType(ProductVariationInterface $product_variation = NULL) {
    $order_item_type = AmNetOrderInterface::ORDER_ITEM_TYPE_UNDEFINED;
    if (!$product_variation) {
      return $order_item_type;
    }
    $variation_type = $product_variation->bundle();
    if (empty($variation_type)) {
      return $order_item_type;
    }
    switch ($variation_type) {
      case 'self_study_registration':
        $order_item_type = AmNetOrderInterface::ORDER_ITEM_TYPE_SELF_STUDY_REGISTRATION;
        break;

      case 'donation':
        $order_item_type = AmNetOrderInterface::ORDER_ITEM_TYPE_DONATION;
        break;

      case 'event_registration':
        $order_item_type = AmNetOrderInterface::ORDER_ITEM_TYPE_EVENT_REGISTRATION;
        break;

      case 'membership':
        $order_item_type = AmNetOrderInterface::ORDER_ITEM_TYPE_MEMBERSHIP;
        break;

      case 'peer_review_administrative_fee':
        $order_item_type = AmNetOrderInterface::ORDER_ITEM_TYPE_PEER_REVIEW_PAYMENT;
        break;

    }
    return $order_item_type;
  }

  /**
   * Check if the current order item only contains donation.
   *
   * @param string $key
   *   Order item key.
   *
   * @return bool
   *   TRUE if the current order item contains donations, otherwise FALSE.
   */
  public static function isDonation($key = '') {
    return ($key == AmNetOrderInterface::ORDER_ITEM_TYPE_DONATION);
  }

  /**
   * Check if the current order item contains event registrations.
   *
   * @param string $key
   *   Order item key.
   *
   * @return bool
   *   TRUE if the current order item contains event registrations,
   *   otherwise FALSE.
   */
  public static function isEventRegistration($key = '') {
    return ($key == AmNetOrderInterface::ORDER_ITEM_TYPE_EVENT_REGISTRATION);
  }

  /**
   * Check if the current order item contains self study registration.
   *
   * @param string $key
   *   Order item key.
   *
   * @return bool
   *   TRUE if the current order item contains self study registration,
   *   otherwise FALSE.
   */
  public static function isSelfStudyRegistration($key = '') {
    return ($key == AmNetOrderInterface::ORDER_ITEM_TYPE_SELF_STUDY_REGISTRATION);
  }

  /**
   * Check if the current order item contains Membership Payment.
   *
   * @param string $key
   *   Order item key.
   *
   * @return bool
   *   TRUE if the current order item contains Membership Payment,
   *   otherwise FALSE.
   */
  public static function isMembership($key = '') {
    return ($key == AmNetOrderInterface::ORDER_ITEM_TYPE_MEMBERSHIP);
  }

  /**
   * Check if the current order item contains Peer Review Payments.
   *
   * @param string $key
   *   Order item key.
   *
   * @return bool
   *   TRUE if the current order item contains Peer Review Payments,
   *   otherwise FALSE.
   */
  public static function isPeerReviewPayment($key = '') {
    return ($key == AmNetOrderInterface::ORDER_ITEM_TYPE_PEER_REVIEW_PAYMENT);
  }

}
