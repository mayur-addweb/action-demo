<?php

namespace Drupal\vscpa_commerce;

use Drupal\commerce\AvailabilityCheckerInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce\Context;

/**
 * The entry point for availability checking through Commerce Stock.
 *
 * Proxies requests to stock services configured for each entity.
 */
class EventRegistrationAvailabilityChecker implements AvailabilityCheckerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(PurchasableEntityInterface $entity = NULL) {
    if (!$entity) {
      return FALSE;
    }
    return ($entity->bundle() == 'event_registration');
  }

  /**
   * {@inheritdoc}
   */
  public function check(PurchasableEntityInterface $purchased_entity, $quantity, Context $context) {
    /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    if (!$purchased_entity) {
      return FALSE;
    }
    $product = $purchased_entity->getProduct();
    if (!$product) {
      return FALSE;
    }
    return vscpa_commerce_is_event_product_open_for_registration($product);
  }

}
