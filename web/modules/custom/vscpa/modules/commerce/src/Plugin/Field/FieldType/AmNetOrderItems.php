<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\vscpa_commerce\AmNetOrderInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'am_net_order_items' field type.
 *
 * @FieldType(
 *   id = "am_net_order_items",
 *   label = @Translation("AM.net Order Items"),
 *   module = "am_net_order_items",
 *   description = @Translation("AM.net Order Items"),
 *   category = @Translation("Commerce"),
 *   default_widget = "am_net_order_items",
 *   default_formatter = "am_net_order_items"
 * )
 */
class AmNetOrderItems extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'items' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
        'sync_status' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['items'] = MapDataDefinition::create()->setLabel(t('Order Items data'))->setDescription(t('Stores Order Items data.'));
    $properties['sync_status'] = DataDefinition::create('integer')->setLabel(t('Sync Status'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (!isset($values)) {
      return;
    }
    elseif (!empty($values['order_items'])) {
      $values['items'] = $values['order_items'];
      unset($values['order_items']);
    }
    if (isset($values['items']) && !empty($values['items'])) {
      $synchronized = [];
      // Set values:  sync_status.
      foreach ($values['items'] as $delta => $item) {
        $sync_status = $item['sync_status'] ?? NULL;
        $synchronized[] = ($sync_status == AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED);
      }
      // Set sync status.
      $contains_sync = in_array(AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED, $synchronized);
      $contains_not_sync = in_array(AmNetOrderInterface::ORDER_ITEM_NOT_SYNCHRONIZED, $synchronized);
      if ($contains_sync && $contains_not_sync) {
        $sync_status = AmNetOrderInterface::ORDER_PARTIALLY_SYNCHRONIZED;
      }
      elseif ($contains_sync) {
        $sync_status = AmNetOrderInterface::ORDER_SYNCHRONIZED;
      }
      else {
        $sync_status = AmNetOrderInterface::ORDER_NOT_SYNCHRONIZED;
      }
      $values['sync_status'] = $sync_status;
    }
    else {
      $values['sync_status'] = AmNetOrderInterface::ORDER_NOT_SYNCHRONIZED;
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('items')->getValue();
    return $value === NULL || $value === '';
  }

}
