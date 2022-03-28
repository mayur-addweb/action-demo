<?php

namespace Drupal\am_net\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_price_default' formatter.
 *
 * @FieldFormatter(
 *   id = "amnet_event_id_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "amnet_event_id"
 *   }
 * )
 */
class EventIdDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => "{{ year }} / {{ code }}",
        '#context' => [
          'code' => $item->code,
          'year' => $item->year,
        ],
      ];
    }

    return $elements;
  }

}
