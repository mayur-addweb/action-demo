<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines object that represents general AM.net Transaction.
 */
abstract class Transaction implements TransactionInterface {

  /**
   * Constructs a new Event Registration Transaction object.
   *
   * @param array $data
   *   The order data info from AM.net API.
   */
  public function __construct(array $data = []) {
    $this->order = $data;
    // Set the target entity IDs.
    $this->setTargetEntitiesIds();
  }

  /**
   * The AM.net target entity IDs.
   *
   * @var array
   */
  protected $targetEntityIDs = [];

  /**
   * The AM.net target entity.
   *
   * @var array
   */
  protected $referencedEntities = [];

  /**
   * {@inheritdoc}
   */
  public function availableToBeListed() {
    // Show transaction only from 4 calendar years (current through -3),
    // to align with the licensing cycle for CPAs (at any moment, they
    // may need to retrieve certificates to prove attendance if audited
    // by the Virgjnia Board of Accountancy).
    $current_time = new DrupalDateTime();
    $placed_time = new DrupalDateTime();
    $placed_time->setTimestamp($this->getPlacedTime());
    $diff = $current_time->diff($placed_time);
    $diff_year = $diff->y ?? 0;
    return ($diff_year <= 3);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlacedDate() {
    $placed_time = new DrupalDateTime();
    $placed_time->setTimestamp($this->getPlacedTime());
    return $placed_time->format('F j, Y');
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal() {
    $total = $this->order['Paid'] ?? NULL;
    if (is_numeric($total)) {
      $total = '$' . number_format($total, 2, ".", ",");
    }
    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function parseOrderItemAttributes($attributes = []) {
    if (empty($attributes)) {
      return NULL;
    }
    $items = '';
    foreach ($attributes as $key => $value) {
      $items .= " {$key}='{$value}' ";
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTemplate($title = NULL, array $attributes = []) {
    $template = NULL;
    if (!empty($title)) {
      $title = trim($title);
      $data = $this->parseOrderItemAttributes($attributes);
      $template = "<div class='order-item-summary' {$data}>{$title}.</div>";
    }
    return $template;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function setTargetEntitiesIds();

  /**
   * {@inheritdoc}
   */
  abstract public function getEntityTypeContext();

  /**
   * {@inheritdoc}
   */
  public function getTitleFromReferencedEntity(array $entity = []) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributesFromReferencedEntity(array $entity = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencedEntities() {
    if (empty($this->referencedEntities) && !empty($this->targetEntityIDs)) {
      foreach ($this->targetEntityIDs as $id) {
        $this->referencedEntities[] = \Drupal::service('am_net.entity.repository')->getEntity($id, $this->getEntityTypeContext());
      }
    }
    return $this->referencedEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemsSummary() {
    $entities = $this->getReferencedEntities();
    if (empty($entities)) {
      return NULL;
    }
    $summaries = [];
    foreach ($entities as $delta => $entity) {
      if (!$entity) {
        continue;
      }
      $title = $this->getTitleFromReferencedEntity($entity);
      if (empty($title)) {
        continue;
      }
      $attributes = $this->getAttributesFromReferencedEntity($entity);
      $summaries[] = $this->getOrderItemTemplate($title, $attributes);
    }
    if (empty($summaries)) {
      return NULL;
    }
    return implode('</br>', $summaries);
  }

}
