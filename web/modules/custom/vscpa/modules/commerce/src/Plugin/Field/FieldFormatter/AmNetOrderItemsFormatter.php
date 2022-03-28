<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'am_net_order_items' formatter.
 *
 * @FieldFormatter(
 *   id = "am_net_order_items",
 *   module = "am_net_order_items",
 *   label = @Translation("AM.net Order Items formatter"),
 *   field_types = {
 *     "am_net_order_items"
 *   }
 * )
 */
class AmNetOrderItemsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    return $elements;
  }

}
