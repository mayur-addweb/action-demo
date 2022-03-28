<?php

namespace Drupal\vscpa_commerce\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\vscpa_commerce\PriceManagerInterface;

/**
 * Returns the price based on the event session price fields.
 */
class EventSessionPriceResolver implements PriceResolverInterface {

  /**
   * The price manager.
   *
   * @var \Drupal\vscpa_commerce\PriceManagerInterface
   */
  protected $priceManager;

  /**
   * Constructs a new EventPriceResolver.
   *
   * @param \Drupal\vscpa_commerce\PriceManagerInterface $price_manager
   *   The price manager.
   */
  public function __construct(PriceManagerInterface $price_manager) {
    $this->priceManager = $price_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    if ($entity->bundle() !== 'event_session') {
      return NULL;
    }

    return $this->priceManager->getEventSessionPricingOptions($entity, $quantity, $context)['current_option']['price'];
  }

}
