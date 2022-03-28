<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldFormatter;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Order Item Helper trait implementation.
 */
trait OrderItemFormatterTrait {

  /**
   * {@inheritdoc}
   */
  public function getEntitySummary(EntityInterface $entity = NULL, $verbose = FALSE) {
    if (!$entity) {
      return NULL;
    }
    $summary = NULL;
    if ($entity instanceof OrderItemInterface) {
      // Get the bundle.
      $bundle = $entity->bundle();
      switch ($bundle) {
        case 'event_registration':
          $summary = $this->getEventRegistrationSummary($entity, $verbose);
          break;

        case 'self_study_registration':
          $summary = $this->getSelfStudyRegistrationSummary($entity);
          break;

        case 'peer_review_administrative_fee':
          $summary = $entity->label();
          break;

        case 'donation':
          $summary = $entity->label();
          break;

        default:
          $summary = $entity->label();
          break;
      }
    }
    else {
      $summary = $entity->label();
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckoutOrderItemSummary(OrderItemInterface $entity = NULL) {
    if (!$entity) {
      return NULL;
    }
    $items = [];
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $entity->getPurchasedEntity();
    if (!$variation) {
      return NULL;
    }
    $product = $variation->getProduct();
    if (!$product) {
      return NULL;
    }
    $bundle = $product->bundle();
    if ($bundle == 'cpe_event') {
      $product_summary = $this->getEventRegistrationSummary($entity);
    }
    elseif ($bundle == 'cpe_self_study') {
      $product_summary = $this->getSelfStudyRegistrationSummary($entity);
    }
    else {
      $product_title = $entity->label();
      $product_summary = $this->getDefaultProductSummary($product_title);
    }
    if (!empty($product_summary)) {
      $items[] = $product_summary;
    }
    $user_summary = $this->getRegistrantInfo($entity);
    if (!empty($user_summary)) {
      $items[] = $user_summary;
    }
    $summary = implode('', $items);
    return "<div class='checkout-order-item-summary'>" . $summary . "</div>";
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrantInfo(OrderItemInterface $order_item = NULL, $verbose = FALSE) {
    if (!$order_item->hasField('field_user')) {
      return NULL;
    }
    $field = $order_item->get('field_user');
    if ($field->isEmpty()) {
      return NULL;
    }
    $users = $field->referencedEntities();
    $user = current($users);
    if (!$user || !($user instanceof UserInterface)) {
      return NULL;
    }
    return $this->getUserEntitySummary($user, $verbose);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProductSummary($title = NULL) {
    if (empty($title)) {
      return NULL;
    }
    $items[] = "<strong class='label inline'>Order Item:</strong> {$title}</br>";
    $summary = implode('', $items);
    return "<div class='order-item-summary'>" . $summary . "</div>";
  }

  /**
   * {@inheritdoc}
   */
  public function getUserEntitySummary(UserInterface $entity = NULL, $verbose = FALSE) {
    if (!$entity) {
      return NULL;
    }
    // Get First name.
    $first_name = $entity->get('field_givenname')->getString();
    // Get Last name.
    $last_name = $entity->get('field_familyname')->getString();
    // Get the Email.
    $email = $entity->getEmail();
    $items = [];
    $items[] = "<strong class='label inline'>Name:</strong> {$first_name} {$last_name}</br>";
    $items[] = "<strong class='label inline'>Email:</strong> {$email}</br>";
    if ($verbose) {
      // Add Name ID.
      $am_net_name_id = $entity->get('field_amnet_id')->getString();
      $am_net_name_id = trim($am_net_name_id);
      $items[] = "<strong class='label inline'>Name ID:</strong> {$am_net_name_id}</br>";
    }
    $summary = implode('', $items);
    return "<div class='order-item-summary'>" . $summary . "</div>";
  }

  /**
   * {@inheritdoc}
   */
  public function getEventRegistrationSummary(OrderItemInterface $entity = NULL, $verbose = FALSE) {
    if (!$entity) {
      return NULL;
    }
    $items = [];
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $entity->getPurchasedEntity();
    if (!$variation) {
      return NULL;
    }
    $product = $variation->getProduct();
    if (!$product) {
      return NULL;
    }
    $division_value = $product->get('field_division')->getString();
    $label_top = $product->label();
    $label_bottom = $variation->label();
    if (am_net_is_self_study($division_value)) {
      $items[] = "<strong class='label inline'>Event:</strong> {$label_top}</br>";
    }
    elseif ($label_top == $label_bottom) {
      $items[] = "<strong class='label inline'>Event:</strong> {$label_top}.</br>";
    }
    else {
      $items[] = "<strong class='label inline'>Event:</strong> {$label_top}.</br>";
      $items[] = "<strong class='label inline'>Registration:</strong> {$label_bottom}.</br>";
    }
    if ($verbose) {
      // Include Event Code/Year.
      $event_code = $product->field_amnet_event_id->code ?? NULL;
      $event_year = $product->field_amnet_event_id->year ?? NULL;
      if (!empty($event_code) && !empty($event_year)) {
        $items[] = "<strong class='label inline'>Event Code:</strong> {$event_code}/{$event_year}.</br>";
      }
      // Include user information.
      $user_summary = $this->getRegistrantInfo($entity, $verbose);
      if (!empty($user_summary)) {
        $items[] = $user_summary;
      }
      // Added.
      $time = $entity->getCreatedTime();
      $date = date("Y-m-d H:i:s", $time);
      $items[] = "<strong class='label inline'>Added:</strong> {$date}.</br>";
      // Last Changed.
      $time = $entity->getChangedTime();
      $date = date("Y-m-d H:i:s", $time);
      $items[] = "<strong class='label inline'>Last Changed:</strong> {$date}.</br>";
    }
    $summary = implode('', $items);
    return "<div class='order-item-summary'>" . $summary . "</div>";
  }

  /**
   * {@inheritdoc}
   */
  public function getSelfStudyRegistrationSummary(OrderItemInterface $entity = NULL) {
    if (!$entity) {
      return NULL;
    }
    $items = [];
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $entity->getPurchasedEntity();
    if (!$variation) {
      return NULL;
    }
    $product = $variation->getProduct();
    if (!$product) {
      return NULL;
    }
    $label_top = $product->label();
    $label_bottom = $variation->label();
    if ($label_top == $label_bottom) {
      $items[] = "<strong class='label inline'>Course:</strong> {$label_top}.</br>";
    }
    else {
      $items[] = "<strong class='label inline'>Course:</strong> {$label_top}.</br>";
      $items[] = "<strong class='label inline'>Registration:</strong> {$label_bottom}.</br>";
    }
    $summary = implode('', $items);
    return "<div class='order-item-summary'>" . $summary . "</div>";
  }

}
