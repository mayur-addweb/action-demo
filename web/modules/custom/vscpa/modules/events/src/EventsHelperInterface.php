<?php

namespace Drupal\vscpa_events;

use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Defines an events helper interface.
 *
 * @package Drupal\vscpa_events
 */
interface EventsHelperInterface {

  /**
   * Returns the first related conference for the given event product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   An event product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The first related conference product, if found.
   */
  public function getRelatedConference(ProductInterface $product);

  /**
   * Returns the first related webcast for the given event product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   An event product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The first related webcast product, if found.
   */
  public function getRelatedWebcast(ProductInterface $product);

  /**
   * Gets the taxonomy term for the conference event type.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The conference taxonomy term, or NULL if not found.
   */
  public function getConferenceTaxonomyTerm();

  /**
   * Gets the taxonomy term for the webcast or webinar event type.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The webcast taxonomy term, or NULL if not found.
   */
  public function getWebcastTaxonomyTerm();

  /**
   * Determines if a product is a conference event.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The event product.
   *
   * @return bool
   *   TRUE if the product is a conference event.
   */
  public function isConference(ProductInterface $product);

  /**
   * Determines if a product is a webcast event.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The event product.
   *
   * @return bool
   *   TRUE if the product is a webcast event.
   */
  public function isWebcast(ProductInterface $product);

}
