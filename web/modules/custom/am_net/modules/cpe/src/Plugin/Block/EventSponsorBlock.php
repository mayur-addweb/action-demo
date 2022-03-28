<?php

namespace Drupal\am_net_cpe\Plugin\Block;

use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Block\BlockBase;
use Drupal\node\NodeInterface;

/**
 * Provides the 'Event Sponsors' block.
 *
 * @Block(
 *   id = "am_net_cpe_event_sponsor_digital_rewind_block",
 *   admin_label = @Translation("Event Sponsors - Digital Rewind")
 * )
 */
class EventSponsorBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node instanceof NodeInterface) {
      return [];
    }
    if ($node->bundle() != 'digital_rewind_page') {
      return [];
    }
    $am_net_event_id = $node->get('field_digital_rewind_event_id')->getValue();
    $am_net_event_id = is_array($am_net_event_id) ? current($am_net_event_id) : NULL;
    $event_code = $am_net_event_id['code'] ?? NULL;
    $event_year = $am_net_event_id['year'] ?? NULL;
    if (empty($event_code) || empty($event_year)) {
      return [];
    }
    $product = $this->getDrupalCpeEventProduct($event_code, $event_year);
    if (!$product) {
      return [];
    }
    $field_sponsors = $product->get('field_sponsors')->getValue();
    $field_sponsors = is_array($field_sponsors) ? current($field_sponsors) : NULL;
    if (empty($field_sponsors)) {
      return [];
    }
    $text = $field_sponsors['value'] ?? NULL;
    $renderer = \Drupal::service('renderer');
    $build = [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => 'full_html',
    ];
    $renderer->addCacheableDependency($build, $product);
    return $build;
  }

  /**
   * Get Drupal Cpe Event Product.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year (two digits).
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The event product.
   */
  public function getDrupalCpeEventProduct($event_code, $event_year) {
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_amnet_event_id', 'amnet_event_id');
    $query->fields('amnet_event_id', ['entity_id']);
    $query->condition('field_amnet_event_id_code', $event_code);
    $query->condition('field_amnet_event_id_year', $event_year);
    $entity_id = $query->execute()->fetchField();
    /** @var \Drupal\commerce_product\Entity\ProductInterface $event */
    $event = NULL;
    if (!empty($entity_id)) {
      $event = Product::load($entity_id);
    }
    return $event;
  }

}
