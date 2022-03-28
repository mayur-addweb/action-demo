<?php

namespace Drupal\am_net_payments;

use Drupal\user\UserInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\PaymentMethodStorage as PaymentMethodStorageBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;

/**
 * Defines the list builder for payment methods.
 */
class PaymentMethodStorage extends PaymentMethodStorageBase {

  /**
   * {@inheritdoc}
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway, array $billing_countries = []) {
    // Anonymous users cannot have reusable payment methods.
    if ($account->isAnonymous()) {
      return [];
    }
    if (!($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface)) {
      return [];
    }

    $query = $this->getQuery();
    // Add Status Field.
    $group = $query->orConditionGroup()
      ->notExists('status')
      ->condition('status', '0', '<>');
    $query->condition('uid', $account->id())
      ->condition('payment_gateway', $payment_gateway->id())
      ->condition('reusable', TRUE)
      ->condition($query->orConditionGroup()
        ->condition('expires', $this->time->getRequestTime(), '>')
        ->condition('expires', 0))
      ->condition($group)
      ->sort('method_id', 'DESC');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface[] $payment_methods */
    $payment_methods = $this->loadMultiple($result);
    if (!empty($billing_countries)) {
      // Filter out payment methods that don't match the billing countries.
      // Payment methods without a billing profile should also be filtered out.
      // @todo Use a query condition once #2822359 is fixed.
      foreach ($payment_methods as $id => $payment_method) {
        $country_code = 'ZZ';
        if ($billing_profile = $payment_method->getBillingProfile()) {
          $country_code = $billing_profile->address->first()->getCountryCode();
        }

        if (!in_array($country_code, $billing_countries)) {
          unset($payment_methods[$id]);
        }
      }
    }

    return $payment_methods;
  }

}
