<?php

namespace Drupal\am_net_membership\DuesPaymentPlan;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * The Dues Payment Plan Helper.
 */
class DuesPaymentPlanHelper {

  /**
   * Check if the given order item contains an active 'Dues Payment Plan'.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The  order entity.
   *
   * @return bool
   *   TRUE if Payment Plan should be applied, FALSE otherwise.
   */
  public function orderContainsActivePaymentPlan(OrderInterface $order = NULL) {
    if (!$order) {
      return FALSE;
    }
    if (!$order->hasItems()) {
      return FALSE;
    }
    $items = $order->getItems();
    foreach ($items as $delta => $item) {
      if ($this->orderItemContainsActivePaymentPlan($item)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check if the given order item contains an active 'Dues Payment Plan'.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The membership order item.
   *
   * @return bool
   *   TRUE if Payment Plan should be applied, FALSE otherwise.
   */
  public function orderItemContainsActivePaymentPlan(OrderItemInterface $order_item) {
    $plan = $this->getPaymentPlan($order_item);
    if (!$plan) {
      return FALSE;
    }
    return $plan->isPlanActive();
  }

  /**
   * Get 'Dues Payment Plan' Info.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The membership order item.
   *
   * @return \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface|false
   *   The Dues Plan info, FALSE otherwise.
   */
  public function getPaymentPlan(OrderItemInterface $order_item) {
    // Only act on membership (ignore membership donations).
    // Donations will be merged in with these membership order items.
    if ($order_item->bundle() !== 'membership') {
      return FALSE;
    }
    $info = $order_item->get('field_payment_plan_info');
    if ($info->isEmpty()) {
      return FALSE;
    }
    /** @var \Drupal\am_net\Plugin\Field\FieldType\AmNetData $field */
    try {
      $field = $info->first();
    }
    catch (MissingDataException $e) {
      return FALSE;
    }
    $amnet_data = $field->toAmNetData();
    $data = $amnet_data->getData();
    if (empty($data)) {
      return FALSE;
    }
    return DuesPaymentPlanInfo::create($data);
  }

}
