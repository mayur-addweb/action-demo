<?php

namespace Drupal\vscpa_search\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity reference AM.net ID' formatter.
 *
 * @FieldFormatter(
 *   id = "am_net_entity_reference_entity_id",
 *   label = @Translation("AM.NET Entity ID"),
 *   description = @Translation("Display the AM.net ID of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AMNetIdFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($entity->id()) {
        $fields = [
          'field_amnet_interest_code',
          'field_amnet_position_code',
          'field_amnet_gb_code',
        ];
        foreach ($fields as $key => $field_name) {
          if ($entity->hasField($field_name)) {
            $value = $entity->get($field_name)->getString();
            if (!empty($value)) {
              $elements[$delta] = [
                '#plain_text' => $value,
                '#cache' => [
                  'tags' => $entity->getCacheTags(),
                ],
              ];
            }
            break;
          }
        }

      }
    }

    return $elements;
  }

}
