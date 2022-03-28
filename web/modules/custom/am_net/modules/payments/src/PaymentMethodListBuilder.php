<?php

namespace Drupal\am_net_payments;

use Drupal\commerce_payment\PaymentMethodListBuilder as PaymentMethodListBuilderBase;

/**
 * Defines the list builder for payment methods.
 */
class PaymentMethodListBuilder extends PaymentMethodListBuilderBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    /** @var \Drupal\Core\Routing\RouteMatchInterface $route */
    $route = \Drupal::service('current_route_match');
    $user = $route->getParameter('user');
    $query = $this->getStorage()->getQuery();
    $group = $query->orConditionGroup()
      ->notExists('status')
      ->condition('status', '0', '<>');
    $query = $query->condition('uid', $user->id())
      ->condition('reusable', TRUE)
      ->condition($group)
      ->sort('method_id');
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
