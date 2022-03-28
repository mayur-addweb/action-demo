<?php

namespace Drupal\vscpa_commerce\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_promotion\Plugin\Commerce\CheckoutPane\CouponRedemption;

/**
 * Add enhancements to the Coupon Redemption Checkout Pane.
 */
class CouponRedemptionAMNet extends CouponRedemption {

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    $order_total = $this->order->getTotalPrice();
    if ($order_total->isZero()) {
      return FALSE;
    }
    // Check if is an donation.
    $bundle = $this->order->bundle();
    // @todo - refactoring.
    if ($bundle == 'donation') {
      return FALSE;
    }
    return TRUE;
  }

}
