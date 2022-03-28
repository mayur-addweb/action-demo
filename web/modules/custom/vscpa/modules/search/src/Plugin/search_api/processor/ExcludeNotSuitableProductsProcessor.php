<?php

namespace Drupal\vscpa_search\Plugin\search_api\processor;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\search_api\IndexInterface;

/**
 * Exclude not suitable products from product indexes.
 *
 * @SearchApiProcessor(
 *   id = "products_exclution",
 *   label = @Translation("Products Exclution"),
 *   description = @Translation("Exclude not suitable products from being indexed."),
 *   stages = {
 *     "alter_items" = -50
 *   },
 * )
 */
class ExcludeNotSuitableProductsProcessor extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      $entity_type_id = $datasource->getEntityTypeId();
      if (!$entity_type_id) {
        continue;
      }
      // We support products entities.
      if ($entity_type_id === 'commerce_product') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
      $product = $item->getOriginalObject()->getValue();
      if (!$product instanceof ProductInterface) {
        continue;
      }
      // Check if the product is excluded.
      $field = 'field_exclude_from_web_catalog';
      if ($product->hasField($field)) {
        $excluded = (bool) $product->get($field)->getString();
        if ($excluded) {
          unset($items[$item_id]);
          continue;
        }
      }
      // Check if the product is still valid by date.
      $field = 'field_search_index_date';
      if (!$product->hasField($field)) {
        continue;
      }
      $field = $product->get($field);
      if ($field->isEmpty()) {
        continue;
      }
      $expiry_date = $field->getString();
      $date_original = new DrupalDateTime($expiry_date, 'UTC');
      $expiry_datetime = $date_original->getTimestamp();
      // 1 year ago.
      $current_datetime = strtotime('-1 year');
      // Calculate if the event is closed for registration.
      $available_now = ($expiry_datetime > $current_datetime);
      if (!$available_now) {
        unset($items[$item_id]);
        continue;
      }
    }
  }

}
