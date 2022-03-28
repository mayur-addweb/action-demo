<?php

namespace Drupal\vscpa_commerce;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines the interface for a VSCPA price manager.
 *
 * @package Drupal\vscpa_commerce
 */
interface PriceManagerInterface {

  /**
   * Gets current pricing options for an event.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return array
   *   An associative arrays of pricing options with the following properties:
   *    - type: string 'regular' or 'early_bird'.
   *    - price: \Drupal\commerce_price\Price Non-members' price.
   *    - member_price: \Drupal\commerce_price\Price Members' price.
   *    - remaining_days: The number of days remaining until the next option.
   *   Parent keys:
   *    - current_option: The current selected option (defaults to base price).
   *    - next_option: The next available option.
   *
   * @todo Make sure return array has more data needed for price display(s).
   */
  public function getEventPricingOptions(PurchasableEntityInterface $entity, $quantity, Context $context);

  /**
   * Gets the current pricing options for an event session.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return array
   *   An array of pricing options for the given session.
   */
  public function getSessionPricingOptions(PurchasableEntityInterface $entity, $quantity, Context $context);

  /**
   * Gets current pricing options for a Self-study course.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return array
   *   An array of pricing options with the following properties:
   *    - price: \Drupal\commerce_price\Price Non-members' price.
   *    - member_price: \Drupal\commerce_price\Price Members' price.
   */
  public function getSelfStudyPricingOptions(PurchasableEntityInterface $entity, $quantity, Context $context);

  /**
   * Check if is Event Registration status Open.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The event product variation.
   *
   * @return bool
   *   TRUE if the Event Registration status is Open otherwise FALSE.
   */
  public function isEventRegistrationOpen(PurchasableEntityInterface $purchased_entity = NULL);

  /**
   * Get the trend badge class associated with an event.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The event product variation.
   *
   * @return string|null
   *   The badge class, otherwise NULL.
   */
  public function getEventProductBadgeEventClass(PurchasableEntityInterface $purchased_entity = NULL);

  /**
   * Get the 'Discount Off Label' associated with an event.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The event product variation.
   *
   * @return array|null
   *   The "Discount Off Label" render array, otherwise NULL.
   */
  public function getEventProductDiscountOffLabel(PurchasableEntityInterface $purchased_entity = NULL);

}
