<?php

namespace Drupal\am_net_payments\EventSubscriber;

use Drupal\am_net_payments\PaymentsHelperInterface;
use Drupal\am_net_payments\PaymentsUpdaterInterface;
use Drupal\commerce_paypal\Event\PayPalEvents;
use Drupal\commerce_paypal\Event\PostCreatePayFlowPaymentMethodEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responds to payment events.
 */
class PaymentEventsSubscriber implements EventSubscriberInterface {

  /**
   * The payments helper.
   *
   * @var \Drupal\am_net_payments\PaymentsHelperInterface
   */
  protected $paymentsHelper;

  /**
   * The payments updater.
   *
   * @var \Drupal\am_net_payments\PaymentsUpdaterInterface
   */
  protected $paymentsUpdater;

  /**
   * Constructs a new PaymentEventsSubscriber.
   *
   * @param \Drupal\am_net_payments\PaymentsUpdaterInterface $payments_updater
   *   The payments updater.
   * @param \Drupal\am_net_payments\PaymentsHelperInterface $payments_helper
   *   The payments helper.
   */
  public function __construct(PaymentsUpdaterInterface $payments_updater, PaymentsHelperInterface $payments_helper) {
    $this->paymentsHelper = $payments_helper;
    $this->paymentsUpdater = $payments_updater;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PayPalEvents::POST_CREATE_PAYMENT_METHOD_PAYFLOW => ['postCreatePayFlowPaymentMethod'],
    ];
  }

  /**
   * Saves the AM.net version of the card number for a PayFlow payment method.
   *
   * @param \Drupal\commerce_paypal\Event\PostCreatePayFlowPaymentMethodEvent $event
   *   The payment method post-create event.
   */
  public function postCreatePayFlowPaymentMethod(PostCreatePayFlowPaymentMethodEvent $event) {
    $payment_method = $event->getPaymentMethod();
    $payment_method->set('amnet_number', $this->paymentsHelper->getAmNetCardNumber($event->getPaymentDetails()));
    $event->setPaymentMethod($payment_method);
  }

}
