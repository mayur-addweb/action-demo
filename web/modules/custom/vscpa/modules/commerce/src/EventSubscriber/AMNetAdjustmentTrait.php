<?php

namespace Drupal\vscpa_commerce\EventSubscriber;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\user\UserInterface;
use Drupal\commerce\Context;

/**
 * AM.net Adjustment trait implementation.
 */
trait AMNetAdjustmentTrait {

  /**
   * The seminar discount prefix.
   *
   * @var string
   */
  protected $seminarDiscountPrefix = 'Seminar Discount';

  /**
   * The AICPA discount prefix.
   *
   * @var string
   */
  protected $aicpaDiscountPrefix = 'AICPA Discount';

  /**
   * The goodwill discount prefix.
   *
   * @var string
   */
  protected $goodWillDiscountPrefix = '% Off!';

  /**
   * The event price resolver.
   *
   * @var string
   */
  protected $eventPriceResolver = NULL;

  /**
   * Get the event Price Resolver.
   */
  public function eventPriceResolver() {
    if (is_null($this->eventPriceResolver)) {
      $this->eventPriceResolver = \Drupal::service('vscpa_commerce.event_price_resolver');
    }
    return $this->eventPriceResolver;
  }

  /**
   * Resolves a price for the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param \Drupal\user\UserInterface $registrant
   *   The registrant.
   *
   * @return \Drupal\commerce_price\Price|null
   *   A price value object, if resolved. Otherwise NULL, indicating that the
   *   next resolver in the chain should be called.
   */
  public function getPurchasableEntityPrice(PurchasableEntityInterface $entity, UserInterface $registrant) {
    $resolver = $this->eventPriceResolver();
    if (!$resolver) {
      return NULL;
    }
    /* @var \Drupal\commerce_store\Entity\Store $store */
    $store = Store::load(1);
    $context = new Context($registrant, $store);
    return $resolver->resolve($entity, 1, $context);
  }

  /**
   * Apply a given discount to one or more Order item.
   *
   * @param string $discount_key
   *   The entity add to cart event.
   * @param array $order_items
   *   The order items.
   */
  public function applyDiscount($discount_key = NULL, array $order_items = []) {
    if (empty($discount_key) || empty($order_items)) {
      return;
    }
    if ($discount_key == 'SeminarVolumeDiscount') {
      // Apply Seminar Volume Discount.
      foreach ($order_items as $key => $item) {
        $this->orderItemApplyAdjustmentSeminarVolumeDiscount($item);
      }
    }
    elseif ($discount_key == 'AICPADiscount') {
      // Apply AICPA Discount: would correspond to events whose vendor is
      // listed as the AICPA. If an event has an "AICPA Discount ($30)" fee
      // listed, it qualifies for that discount.
      foreach ($order_items as $key => $item) {
        // Check if the order item has product with AICPA Discount Adjustment.
        $this->orderItemApplyAdjustmentAicpaDiscountCode($item);
      }
    }
    elseif ($discount_key == 'GoodwillDiscount') {
      // Apply 'Goodwill Discount': would correspond to events with the
      // "Percentage Discount" fee listed, it qualifies for that discount.
      foreach ($order_items as $key => $item) {
        // Check if the item has product with 'Goodwill Discount' Adjustment.
        $this->orderItemApplyAdjustmentGoodwillDiscountCode($item);
      }
    }
  }

  /**
   * Remove a given discount to one or more Order item.
   *
   * @param string $discount_key
   *   The entity add to cart event.
   * @param array $order_items
   *   The order items.
   */
  public function removeDiscount($discount_key = NULL, array $order_items = []) {
    if (empty($discount_key) || empty($order_items)) {
      return;
    }
    // Check Seminar Volume Discount.
    if ($discount_key == 'SeminarVolumeDiscount') {
      foreach ($order_items as $key => $item) {
        $this->orderItemRemoveAdjustment($item, $this->seminarDiscountPrefix);
      }
    }
    elseif ($discount_key == 'AICPADiscount') {
      foreach ($order_items as $key => $item) {
        $this->orderItemRemoveAdjustment($item, $this->aicpaDiscountPrefix);
      }
    }
  }

  /**
   * Check if the order items applies to the given discount.
   *
   * @param string $discount_key
   *   The entity add to cart event.
   * @param array $order_items
   *   The order items.
   *
   * @return bool
   *   TRUE if the cart applies to the given discount.
   */
  public function appliesToDiscount($discount_key = NULL, array $order_items = []) {
    if (empty($discount_key) || empty($order_items)) {
      return FALSE;
    }
    $apply = FALSE;
    // Check Seminar Volume Discount.
    if ($discount_key == 'SeminarVolumeDiscount') {
      $count = 0;
      foreach ($order_items as $key => $item) {
        // Check if the order item has product with SVD Adjustment.
        if ($this->orderItemHasDiscountFeeCode($item, 'SV')) {
          $count += 1;
        }
      }
      // SVD should be applied ONLY if 3 or more qualifying classes are
      // in the cart.
      $apply = $count >= 3;
    }
    elseif ($discount_key == 'AICPADiscount') {
      // AICPA Discount: would correspond to events whose vendor is listed as
      // the AICPA. If an event has an "AICPA Discount ($30)" fee listed, it
      // qualifies for that discount.
      foreach ($order_items as $key => $item) {
        // Check if the order item has product with AICPA Discount Adjustment.
        if ($this->orderItemHasAdjustmentAicpaDiscountCode($item)) {
          return TRUE;
        }
      }
    }
    elseif ($discount_key == 'GoodwillDiscount') {
      // Apply 'Goodwill Discount': would correspond to events with the
      // "Percentage Discount" fee listed, it qualifies for that discount.
      foreach ($order_items as $key => $item) {
        // Check if the item has product with 'Goodwill Discount' Adjustment.
        if ($this->orderItemHasDiscountFeeCode($item, 'DP')) {
          return TRUE;
        }
      }
    }
    return $apply;
  }

  /**
   * Remove Seminar Volume Discount code to the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   * @param string $adjustment_label
   *   The discount prefix.
   */
  public function orderItemRemoveAdjustment(OrderItemInterface $order_item = NULL, $adjustment_label = NULL) {
    if (!$order_item) {
      return;
    }
    if ($order_item->bundle() != 'event_registration') {
      return;
    }
    $adjustment_type = 'custom';
    $adjustments = $order_item->getAdjustments();
    if (empty($adjustments)) {
      return;
    }
    $save_changes = FALSE;
    foreach ($adjustments as $delta => $adjustment) {
      if (($adjustment->getType() == $adjustment_type) && (strpos($adjustment->getLabel(), $adjustment_label) !== FALSE)) {
        $order_item->removeAdjustment($adjustment);
        $save_changes = TRUE;
      }
    }
    // Save change if apply.
    if ($save_changes) {
      try {
        $order_item->save();
      }
      catch (EntityStorageException $e) {
        return;
      }
    }
  }

  /**
   * Apply Goodwill Discount code to the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   */
  public function orderItemApplyAdjustmentGoodwillDiscountCode(OrderItemInterface $order_item = NULL) {
    if (!$order_item) {
      return;
    }
    if ($order_item->bundle() != 'event_registration') {
      return;
    }
    $target_adjustment_code = 'DP';
    /* @var \Drupal\commerce_product\Entity\ProductvariationInterface $variation */
    $variation = $order_item->getPurchasedEntity();
    $product = $variation->getProduct();
    if (!$product || !$product->hasField('field_am_net_adjustment')) {
      return;
    }
    $product_label = $product->label();
    $product_label = text_summary($product_label, $format = NULL, $size = 20) . '...';
    $adjustment_label = $product_label . " - Now [percentage]" . $this->goodWillDiscountPrefix;
    $adjustment_type = 'custom';
    // Check if the order item already have applies the Adjustment.
    if ($this->orderItemHasAdjustmentApplied($adjustment_type, $order_item, $this->goodWillDiscountPrefix)) {
      // The adjustment only can be applied once.
      return;
    }
    /** @var \Drupal\user\UserInterface $registrant */
    $registrant = !$order_item->get('field_user')->isEmpty() ? $order_item->get('field_user')->entity : NULL;
    $is_member = !$registrant ? FALSE : $registrant->hasRole('member');
    $currency_code = 'USD';
    $zero = new Price('0', $currency_code);
    $adjustments = $product->get('field_am_net_adjustment')->getString();
    $adjustments = !empty($adjustments) ? json_decode($adjustments, TRUE) : NULL;
    if (empty($adjustments)) {
      return;
    }
    foreach ($adjustments as $delta => $adjustment) {
      $applies_by_code = isset($adjustment['Ty2']) && ($adjustment['Ty2'] == $target_adjustment_code);
      $applies_by_member_type = FALSE;
      $apply_to_member_type = $adjustment['ApplyToMemberType'] ?? NULL;
      if ($apply_to_member_type == 'A') {
        $applies_by_member_type = TRUE;
      }
      elseif (($apply_to_member_type == 'M') && $is_member) {
        $applies_by_member_type = TRUE;
      }
      if (($apply_to_member_type == 'N') && !$is_member) {
        $applies_by_member_type = TRUE;
      }
      if ($applies_by_code && $applies_by_member_type) {
        // Resolve the price.
        $unit_price = $this->getPurchasableEntityPrice($variation, $registrant);
        // Calculate the discount amount.
        $percentage_amount = $adjustment['Amount'] ?? 0;
        $percentage_amount = is_numeric($percentage_amount) ? $percentage_amount : 0;
        $percentage = (string) ($percentage_amount / 100);
        $adjustment_amount = $unit_price->multiply($percentage);
        // Ensure that the amount represents a discount.
        if (!$adjustment_amount->greaterThan($zero)) {
          // None discount amount available to apply - Stop here.
          return;
        }
        // Change the percentage in the adjustment label.
        $adjustment_label = str_replace('[percentage]', $percentage_amount, $adjustment_label);
        // Build adjustment.
        $values = [
          'type' => $adjustment_type,
          'label' => $adjustment_label,
          'percentage' => $percentage,
          'amount' => $adjustment_amount->multiply('-1'),
          'locked' => TRUE,
        ];
        $adjustment = new Adjustment($values);
        // Add Adjustment.
        $order_item->addAdjustment($adjustment);
        // Save Adjustment.
        $order_item->save();
        // Stop Here.
        return;
      }
    }
  }

  /**
   * Apply Seminar Volume Discount code to the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   */
  public function orderItemApplyAdjustmentSeminarVolumeDiscount(OrderItemInterface $order_item = NULL) {
    if (!$order_item) {
      return;
    }
    if ($order_item->bundle() != 'event_registration') {
      return;
    }
    $target_adjustment_code = 'SV';
    /* @var \Drupal\commerce_product\Entity\ProductvariationInterface $variation */
    $variation = $order_item->getPurchasedEntity();
    $product = $variation->getProduct();
    if (!$product || !$product->hasField('field_am_net_adjustment')) {
      return;
    }
    $product_label = $product->label();
    $product_label = text_summary($product_label, $format = NULL, $size = 20) . '...';
    $adjustment_label = $this->seminarDiscountPrefix . "(" . $product_label . ")";
    $adjustment_type = 'custom';
    // Check if the order item already have applies the Adjustment.
    if ($this->orderItemHasAdjustmentApplied($adjustment_type, $order_item, $this->seminarDiscountPrefix)) {
      // The adjustment only can be applied once.
      return;
    }
    /** @var \Drupal\user\UserInterface $registrant */
    $registrant = !$order_item->get('field_user')->isEmpty() ? $order_item->get('field_user')->entity : NULL;
    $is_member = !$registrant ? FALSE : $registrant->hasRole('member');
    $currency_code = 'USD';
    $zero = new Price('0', $currency_code);
    $adjustments = $product->get('field_am_net_adjustment')->getString();
    $adjustments = !empty($adjustments) ? json_decode($adjustments, TRUE) : NULL;
    if (empty($adjustments)) {
      return;
    }
    foreach ($adjustments as $delta => $adjustment) {
      $applies_by_code = isset($adjustment['Ty2']) && ($adjustment['Ty2'] == $target_adjustment_code);
      $applies_by_member_type = FALSE;
      $apply_to_member_type = $adjustment['ApplyToMemberType'] ?? NULL;
      if ($apply_to_member_type == 'A') {
        $applies_by_member_type = TRUE;
      }
      elseif (($apply_to_member_type == 'M') && $is_member) {
        $applies_by_member_type = TRUE;
      }
      if (($apply_to_member_type == 'N') && !$is_member) {
        $applies_by_member_type = TRUE;
      }
      if ($applies_by_code && $applies_by_member_type) {
        $amount = $adjustment['Amount'] ?? 0;
        $amount = is_numeric($amount) ? $amount : 0;
        $adjustment_amount = new Price($amount, $currency_code);
        // Ensure that the amount represents a discount.
        if ($adjustment_amount->isZero()) {
          // None discount amount available to apply - Stop here.
          return;
        }
        // Ensure negative value.
        if ($adjustment_amount->greaterThanOrEqual($zero)) {
          $adjustment_amount = $adjustment_amount->multiply('-1');
        }
        // Save Adjustment.
        $order_item->addAdjustment(new Adjustment([
          'type' => $adjustment_type,
          'label' => $adjustment_label,
          'amount' => $adjustment_amount,
          'locked' => TRUE,
        ]));
        $order_item->save();
        // Stop Here.
        return;
      }
    }
  }

  /**
   * Apply AICPA Discount Discount code to the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   */
  public function orderItemApplyAdjustmentAicpaDiscountCode(OrderItemInterface $order_item = NULL) {
    if (!$order_item) {
      return;
    }
    if ($order_item->bundle() != 'event_registration') {
      return;
    }
    $adjustments = $this->getProductAdjustmentsFromOrderItem($order_item);
    if (empty($adjustments)) {
      return;
    }
    $adjustment_type = 'custom';
    // Check if the order item already have applies the Adjustment.
    if ($this->orderItemHasAdjustmentApplied($adjustment_type, $order_item, $this->aicpaDiscountPrefix)) {
      // The adjustment only can be applied once.
      return;
    }
    $adjustment_label = $this->getAdjustmentLabel($order_item, $this->aicpaDiscountPrefix);
    /** @var \Drupal\user\UserInterface $registrant */
    $registrant = !$order_item->get('field_user')->isEmpty() ? $order_item->get('field_user')->entity : NULL;
    $is_member = !$registrant ? FALSE : $registrant->hasRole('member');
    $zero = new Price('0', 'USD');
    $target_adjustment_code = 'AD';
    foreach ($adjustments as $delta => $adjustment) {
      $applies_by_code = isset($adjustment['Ty2']) && ($adjustment['Ty2'] == $target_adjustment_code);
      if (!$applies_by_code) {
        continue;
      }
      $apply_to_member_type = $adjustment['ApplyToMemberType'] ?? NULL;
      $applies_by_member_type = $this->checkIfDiscountApplyByMemberType($apply_to_member_type, $is_member);
      if (!$applies_by_member_type) {
        continue;
      }
      // Ensure that the amount represents a discount.
      $amount = $adjustment['Amount'] ?? 0;
      $amount = is_numeric($amount) ? $amount : 0;
      $adjustment_amount = new Price($amount, 'USD');
      if ($adjustment_amount->isZero()) {
        // None discount amount available to apply - Stop here.
        continue;
      }
      // Ensure negative value.
      if ($adjustment_amount->greaterThanOrEqual($zero)) {
        $adjustment_amount = $adjustment_amount->multiply('-1');
      }
      // Apply Discount.
      $order_item->addAdjustment(new Adjustment([
        'type' => $adjustment_type,
        'label' => $adjustment_label,
        'amount' => $adjustment_amount,
        'locked' => TRUE,
      ]));
      // Save Adjustment.
      try {
        $order_item->save();
      }
      catch (EntityStorageException $e) {
        continue;
      }
    }
  }

  /**
   * Check if Discount Apply by Member Type.
   *
   * @param string $apply_to_member_type
   *   The apply to member type.
   * @param bool $is_member
   *   The flag 'is member'.
   *
   * @return bool
   *   TRUE the discount apply otherwise FALSE.
   */
  public function checkIfDiscountApplyByMemberType($apply_to_member_type = NULL, $is_member = FALSE) {
    if (empty($apply_to_member_type)) {
      return FALSE;
    }
    if ($apply_to_member_type == 'A') {
      return TRUE;
    }
    if (($apply_to_member_type == 'M') && $is_member) {
      return TRUE;
    }
    if (($apply_to_member_type == 'N') && !$is_member) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if the order item contains the given adjustment applied.
   *
   * @param string $adjustment_type
   *   The adjustment type.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   * @param string $adjustment_label
   *   The discount prefix.
   *
   * @return bool
   *   TRUE if the order item contains the given adjustment applied.
   */
  public function orderItemHasAdjustmentApplied($adjustment_type = NULL, OrderItemInterface $order_item = NULL, $adjustment_label = NULL) {
    if (!$order_item || empty($adjustment_type) || empty($adjustment_label)) {
      return FALSE;
    }
    $adjustments = $order_item->getAdjustments();
    if (empty($adjustments)) {
      return FALSE;
    }
    foreach ($adjustments as $delta => $adjustment) {
      if (($adjustment->getType() == $adjustment_type) && (strpos($adjustment->getLabel(), $adjustment_label) !== FALSE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets Adjustment Label.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   * @param string $prefix
   *   The Adjustment prefix.
   *
   * @return string|null
   *   The Adjustment Label, otherwise FALSE.
   */
  public function getAdjustmentLabel(OrderItemInterface $order_item = NULL, $prefix = NULL) {
    if (!$order_item || empty($prefix)) {
      return NULL;
    }
    /* @var \Drupal\commerce_product\Entity\ProductvariationInterface $variation */
    $variation = $order_item->getPurchasedEntity();
    if (!$variation) {
      return NULL;
    }
    $product = $variation->getProduct();
    if (!$product || !$product->hasField('field_am_net_adjustment')) {
      return NULL;
    }
    $product_label = $product->label();
    $product_label = text_summary($product_label, $format = NULL, $size = 20) . '...';
    $adjustment_label = $prefix . "(" . $product_label . ")";
    return $adjustment_label;
  }

  /**
   * Check if a given order item contains the AICPA Discount code.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   *
   * @return bool
   *   TRUE if the order item contains the given adjustment code.
   */
  public function orderItemHasAdjustmentAicpaDiscountCode(OrderItemInterface $order_item = NULL) {
    $adjustments = $this->getProductAdjustmentsFromOrderItem($order_item);
    if (empty($adjustments)) {
      return FALSE;
    }
    if ($order_item->bundle() != 'event_registration') {
      return FALSE;
    }
    $contains_aicpa_discount = FALSE;
    $target_adjustment_code = 'AD';
    // Checks for the specific Ty2 code.
    foreach ($adjustments as $delta => $adjustment) {
      if (isset($adjustment['Ty2']) && $adjustment['Ty2'] == $target_adjustment_code) {
        // Stop Here.
        $contains_aicpa_discount = TRUE;
        break;
      }
    }
    if (!$contains_aicpa_discount) {
      return FALSE;
    }
    // Check if the user is an AICPA member.
    $order = $order_item->getOrder();
    if (!$order) {
      return FALSE;
    }
    $user = $order->getCustomer();
    if (!$user) {
      return FALSE;
    }
    $aicpa_member = $user->get('field_is_aicpa_member')->getString();
    return ($aicpa_member == 1);
  }

  /**
   * Check if a given order item contains a given fee code related to Discounts.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   * @param string $target_fee_code
   *   The target fee code.
   *
   * @return bool
   *   TRUE if the order item contains the given adjustment code.
   */
  public function orderItemHasDiscountFeeCode(OrderItemInterface $order_item = NULL, $target_fee_code = NULL) {
    $adjustments = $this->getProductAdjustmentsFromOrderItem($order_item);
    if (empty($adjustments)) {
      return FALSE;
    }
    if ($order_item->bundle() != 'event_registration') {
      return FALSE;
    }
    // Checks for the specific Ty2 code.
    foreach ($adjustments as $delta => $adjustment) {
      if (isset($adjustment['Ty2']) && $adjustment['Ty2'] == $target_fee_code) {
        // Stop Here.
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets the Product Adjustments related to a given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   *
   * @return array
   *   And array of Adjustment.
   */
  public function getProductAdjustmentsFromOrderItem(OrderItemInterface $order_item = NULL) {
    if (!$order_item) {
      return [];
    }
    /* @var \Drupal\commerce_product\Entity\ProductvariationInterface $variation */
    $variation = $order_item->getPurchasedEntity();
    if (!$variation) {
      return [];
    }
    $product = $variation->getProduct();
    if (!$product || !$product->hasField('field_am_net_adjustment')) {
      return [];
    }
    $adjustments = $product->get('field_am_net_adjustment')->getString();
    $adjustments = !empty($adjustments) ? json_decode($adjustments, TRUE) : [];
    return $adjustments;
  }

}
