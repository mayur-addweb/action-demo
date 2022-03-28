<?php

namespace Drupal\am_net\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'commerce_price' field type.
 *
 * @FieldType(
 *   id = "amnet_event_id",
 *   label = @Translation("AM.net Event ID"),
 *   description = @Translation("Stores an event code and event year."),
 *   category = @Translation("VSCPA"),
 *   default_widget = "amnet_event_id_default",
 *   default_formatter = "amnet_event_id_default",
 * )
 */
class EventId extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['code'] = DataDefinition::create('string')
      ->setLabel(t('Code'))
      ->setRequired(FALSE);

    $properties['year'] = DataDefinition::create('integer')
      ->setLabel(t('Year'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'code';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'code' => [
          'description' => 'The event code.',
          'type' => 'varchar',
          'length' => 8,
        ],
        'year' => [
          'description' => 'The event year.',
          'type' => 'numeric',
          'precision' => 2,
          'scale' => 0,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->code) || empty($this->year);
  }

}
