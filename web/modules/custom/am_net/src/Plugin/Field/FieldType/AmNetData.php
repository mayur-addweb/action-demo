<?php

namespace Drupal\am_net\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\am_net\AmNetData as AmNetInfo;
use Drupal\am_net\AmNetDataInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'amnet_data' field type.
 *
 * @FieldType(
 *   id = "amnet_data",
 *   label = @Translation("AM.net Data"),
 *   module = "amnet",
 *   description = @Translation("Stores the AM.Net Data associated with this record."),
 *   category = @Translation("AM.net"),
 *   default_widget ="amnet_data",
 *   default_formatter = "amnet_data"
 * )
 */
class AmNetData extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'data' => [
          'description' => 'Stores the AM.Net Data associated with this record.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
        'target_id' => [
          'description' => 'The ID of store data.',
          'type' => 'varchar',
          'length' => 40,
        ],
        'owner_id' => [
          'description' => 'The owner ID of the stored data.',
          'type' => 'varchar',
          'length' => 40,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Data property definition.
    $properties['data'] = MapDataDefinition::create()
      ->setLabel(t('The AM.Net record data'))
      ->setDescription(t('Stores the AM.Net Data associated with this record.'));
    $properties['target_id'] = DataDefinition::create('string')
      ->setLabel(t('The ID of store data.'))
      ->setRequired(FALSE);
    $properties['owner_id'] = DataDefinition::create('string')
      ->setLabel(t('The owner ID of the stored data.'))
      ->setRequired(FALSE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Allow callers to pass a AmNetData value object as the field item value.
    if ($values instanceof AmNetDataInterface) {
      $data = $values;
      $values = [
        'target_id' => $data->getTargetId(),
        'owner_id' => $data->getOwnerId(),
        'data' => $data->getData(),
      ];
    }
    if (!empty($values)) {
      parent::setValue($values, $notify);
    }
  }

  /**
   * Get the AM.net Target ID.
   *
   * @return string|null
   *   The target ID.
   */
  public function getTargetId() {
    return $this->target_id;
  }

  /**
   * Get the AM.net Owner ID.
   *
   * @return string|null
   *   The owner ID.
   */
  public function getOwnerId() {
    return $this->owner_id;
  }

  /**
   * Get the AM.net raw data.
   *
   * @return array|null
   *   The raw data.
   */
  public function getData() {
    return $this->data ?? [];
  }

  /**
   * Get property value.
   *
   * @return string|null
   *   The property value.
   */
  public function getPropertyValue($name = NULL) {
    if (empty($name)) {
      return NULL;
    }
    return $this->data[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->target_id);
  }

  /**
   * Gets the AmNet Data value object for the current field item.
   *
   * @return \Drupal\am_net\AmNetDataInterface
   *   The AmNet Data object.
   */
  public function toAmNetData() {
    $info = new AmNetInfo($this->getTargetId(), $this->getOwnerId(), $this->getData());
    return $info;
  }

}
