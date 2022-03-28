<?php

namespace Drupal\vscpa_commerce\Plugin\Commerce\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;

/**
 * Provides the product condition for order items.
 */
class OrderItemProduct extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'products' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $product_ids = array_column($this->configuration['products'], 'product_id');
    $form['products'] = [
      '#type' => 'order_item_products',
      '#default_value' => $product_ids,
      '#target_type' => 'commerce_product',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $items = $values['products']['order_item_products']['product'] ?? [];
    $this->configuration['products'] = [];
    if (!empty($items)) {
      foreach ($items as $target_id) {
        if (!empty($target_id) && is_numeric($target_id)) {
          $target_id = trim($target_id);
          $this->configuration['products'][] = [
            'product_id' => $target_id,
          ];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchasable_entity */
    $purchasable_entity = $order_item->getPurchasedEntity();
    if (!$purchasable_entity || $purchasable_entity->getEntityTypeId() != 'commerce_product_variation') {
      return FALSE;
    }
    $product_ids = array_column($this->configuration['products'], 'product_id');

    return in_array($purchasable_entity->getProductId(), $product_ids);
  }

}
