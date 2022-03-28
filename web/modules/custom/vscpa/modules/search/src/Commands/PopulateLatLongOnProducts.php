<?php

namespace Drupal\vscpa_search\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drush\Commands\DrushCommands;

/**
 * Command to Populate Lat/Long Pair on products.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\am_net_cpe\Commands
 */
class PopulateLatLongOnProducts extends DrushCommands {

  /**
   * Fetch Lat/Long Pair on products from AM.net.
   *
   * @command populate-lat-long-on-products
   *
   * @usage drush populate-lat-long-on-products
   *   Fetch Lat/Long Pair on products from AM.net.
   *
   * @aliases populate_lat_long_on_products
   */
  public function popule() {
    $product_ids = $this->getProductsIdsWithLocation();
    if (empty($product_ids)) {
      drush_log('There are no Event product with locations related.', 'alert');
      return;
    }
    try {
      $products = \Drupal::entityTypeManager()
        ->getStorage('commerce_product')
        ->loadMultiple($product_ids);
    }
    catch (InvalidPluginDefinitionException $e) {
      drush_log('There are no Event product with locations related.', 'alert');
      return;
    }
    catch (PluginNotFoundException $e) {
      drush_log('There are no Event product with locations related.', 'alert');
      return;
    }
    $num_updated = 0;
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    foreach ($products as $delta => $product) {
      $field = $product->get('field_search_index_geolocation');
      if (!$field->isEmpty()) {
        continue;
      }
      try {
        $product->save();
        $num_updated += 1;
        $message = "- Updating Product: {$product->id()} - Name: {$product->label()}.";
        drush_log($message, 'success');
      }
      catch (EntityStorageException $e) {
        continue;
      }
    }
    $message = "- Number of products affected: {$num_updated}.";
    drush_log($message, 'success');
  }

  /**
   * Get Products Ids With Location.
   *
   * @return array
   *   The array of product IDs that contains location info.
   */
  public function getProductsIdsWithLocation() {
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_firm', 'field_firm');
    $query->fields('field_firm', ['entity_id']);
    $query->condition('field_firm.bundle', 'cpe_event');
    $result = $query->execute();
    return $result->fetchAllKeyed(0, 0);
  }

}
