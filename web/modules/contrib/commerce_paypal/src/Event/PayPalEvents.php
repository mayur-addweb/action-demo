<?php

namespace Drupal\commerce_paypal\Event;

/**
 * Defines events for the Commerce PayPal module.
 */
final class PayPalEvents {

  /**
   * Name of the event fired after creating a new PayFlow payment method.
   *
   * This event is fired before the payment method is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\PostCreatePayFlowPaymentMethodEvent.php
   */
  const POST_CREATE_PAYMENT_METHOD_PAYFLOW = 'commerce_paypal.post_create_payment_method_payflow';

  /**
   * Name of the event fired when performing the Express Checkout requests.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\ExpressCheckoutRequestEvent.php
   */
  const EXPRESS_CHECKOUT_REQUEST = 'commerce_paypal.express_checkout_request';

  /**
   * Name of the event fired when calling the PayPal API for creating an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\CheckoutOrderRequestEvent.php
   */
  const CHECKOUT_CREATE_ORDER_REQUEST = 'commerce_paypal.checkout_create_order_request';

  /**
   * Name of the event fired when calling the PayPal API for updating an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\CheckoutOrderRequestEvent.php
   */
  const CHECKOUT_UPDATE_ORDER_REQUEST = 'commerce_paypal.checkout_update_order_request';

  /**
   * Name of the event fired when performing the Payflow Link requests.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\PayflowLinkRequestEvent.php
   */
  const PAYFLOW_LINK_REQUEST = 'commerce_paypal.payflow_link_request';

}
