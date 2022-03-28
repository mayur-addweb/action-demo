<?php

namespace Drupal\am_net_membership\DuesPaymentPlan;

use Drupal\commerce_price\Price;

/**
 * The Dues Payment Plan Transaction implementation.
 */
class DuesPaymentPlanTransaction implements DuesPaymentPlanTransactionInterface {

  /**
   * The transaction amount.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $amount = NULL;

  /**
   * The transaction info.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $info;

  /**
   * Constructs a new MembershipChecker object.
   *
   * @param array $info
   *   The transaction info.
   */
  public function __construct(array $info = []) {
    $this->info = $info;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingItemLabel() {
    $item = '';
    $billing_items = $this->getBillingItems();
    if (empty($billing_items)) {
      return $item;
    }
    foreach ($billing_items as $delta => $billing_item) {
      $label = $billing_item['label'] ?? NULL;
      $amount = $billing_item['amount'] ?? NULL;
      if (empty($label) || empty($amount)) {
        continue;
      }
      $item .= '<div class="order-item-summary">' . $label . ' — <strong class="label inline">' . $this->formatAmount($amount) . '</strong></div>';
    }
    return '<div class="checkout-order-item-summary">' . $item . '</div>';
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingItems() {
    return $this->info['billing_items'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function formatAmount($amount = NULL) {
    if (empty($amount)) {
      return NULL;
    }
    return '$' . number_format($amount, 2, '.', ',');
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->info['id'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMonth() {
    return $this->info['month'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBilledDate() {
    return $this->info['billed_date'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedAmount() {
    $price = $this->getTotalPrice();
    if (empty($price)) {
      return NULL;
    }
    $amount = $price->getNumber();
    return '$' . number_format($amount, 2, '.', ',');
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPrice() {
    $billing_items = $this->getBillingItems();
    /** @var \Drupal\commerce_price\Price $total */
    $total = NULL;
    foreach ($billing_items as $delta => $billing_item) {
      $amount = $billing_item['amount'] ?? NULL;
      if (empty($amount)) {
        continue;
      }
      $price = new Price($amount, 'USD');
      if (empty($total)) {
        $total = $price;
      }
      else {
        $total = $total->add($price);
      }
    }
    return $total;
  }

}
