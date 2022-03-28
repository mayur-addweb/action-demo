<?php

namespace Drupal\vscpa_commerce\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentInformation;

/**
 * Add enhancements to the Payment Information Checkout Pane.
 */
class PaymentInformationAMNet extends PaymentInformation {

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    $order_total = $this->order->getTotalPrice();
    return (!$order_total->isZero());
  }

}
