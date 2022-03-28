<?php

namespace Drupal\am_net\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'amnet_data' field formatter.
 *
 * @FieldFormatter(
 *   id = "amnet_data",
 *   label = @Translation("AM.net Data"),
 *   field_types = {
 *     "amnet_data"
 *   }
 * )
 */
class AmNetDataFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    return $elements;
  }

}
