<?php

namespace Drupal\vscpa_events;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default implementation of a VSCPA events helper.
 *
 * @package Drupal\vscpa_events
 */
class EventsHelper implements EventsHelperInterface {

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * EventsHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedConference(ProductInterface $product) {
    if ($this->isWebcast($product) && $product->hasField('field_related_content')) {
      foreach ($product->get('field_related_content')->referencedEntities() as $related_content) {
        if ($this->isConference($related_content)) {
          return $related_content;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedWebcast(ProductInterface $product) {
    if ($this->isConference($product) && $product->hasField('field_related_content')) {
      foreach ($product->get('field_related_content')->referencedEntities() as $related_content) {
        if ($this->isWebcast($related_content)) {
          return $related_content;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConferenceTaxonomyTerm() {
    if ($terms = $this->termStorage->loadByProperties([
      'vid' => 'cpe_type',
      'name' => 'Conference',
    ])) {
      return current($terms);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWebcastTaxonomyTerm() {
    if ($terms = $this->termStorage->loadByProperties([
      'vid' => 'cpe_type',
      'name' => 'Webinar',
    ])) {
      return current($terms);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isConference(ProductInterface $product) {
    if ($conference_term = $this->getConferenceTaxonomyTerm()) {
      return $product->bundle() === 'cpe_event' && $product->get('field_cpe_type')->target_id == $conference_term->id();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isWebcast(ProductInterface $product) {
    if ($webcast_term = $this->getWebcastTaxonomyTerm()) {
      return $product->bundle() === 'cpe_event' && $product->get('field_cpe_type')->target_id == $webcast_term->id();
    }

    return FALSE;
  }

}
