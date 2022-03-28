<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\vscpa_commerce\AmNetOrderInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Plugin implementation of the 'am_net_order_items' widget.
 *
 * @FieldWidget(
 *   id = "am_net_order_items",
 *   module = "am_net_order_items",
 *   label = @Translation("AM.net Order Items widget"),
 *   field_types = {
 *     "am_net_order_items"
 *   }
 * )
 */
class AmNetOrderItemsWidget extends WidgetBase {

  /**
   * Gets the initial values for the widget.
   *
   * This is a replacement for the disabled default values functionality.
   *
   * @return array
   *   The initial values, keyed by property.
   */
  protected function getInitialValues() {
    $initial_values = [
      'items' => [],
      'sync_status' => AmNetOrderInterface::ORDER_NOT_SYNCHRONIZED,
    ];
    return $initial_values;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field = $items[$delta];
    $order_items_values = isset($field->items) ? $field->items : [];
    $sync_status = isset($field->sync_status) ? $field->sync_status : '';
    $order = $items->getEntity();
    if (is_null($order) || !($order instanceof OrderInterface)) {
      // No AM.net setting if this is not a Order entity.
      return [];
    }
    if (!empty($order->id())) {
      $order_id = $order->id();
    }
    else {
      $order_id = \Drupal::service('uuid')->generate();
    }
    $elements = [];
    $elements['order_items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("AM.net Sync"),
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];
    // Sync status desc.
    $order_synchronized = FALSE;
    $default = t("Order Not Synchronized.");
    $sync_status_desc = $default;
    if (!empty($sync_status)) {
      switch ($sync_status) {
        case AmNetOrderInterface::ORDER_NOT_SYNCHRONIZED:
          $sync_status_desc = $default;
          break;

        case AmNetOrderInterface::ORDER_SYNCHRONIZED:
          $sync_status_desc = t("Order Synchronized.");
          $order_synchronized = TRUE;
          break;

        case AmNetOrderInterface::ORDER_PARTIALLY_SYNCHRONIZED:
          $sync_status_desc = $default;
          break;

      }
    }
    $elements['order_items']['sync_status_desc'] = [
      '#type' => 'item',
      '#markup' => '<h2 class="label inline">Sync Status:</h2> ' . $sync_status_desc,
    ];
    $elements['order_items']['description'] = [
      '#type' => 'item',
      '#markup' => t('Please use the Sync action button to sync the records with AM.net.'),
    ];
    // Order Items.
    $order_items = $order->getItems();
    foreach ($order_items as $order_item) {
      $order_item_id = $order_item->id();
      /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
      $product_variation = $order_item->getPurchasedEntity();
      if (!$product_variation) {
        continue;
      }
      $product = $product_variation->getProduct();
      if (!$product) {
        continue;
      }
      $key = "item_{$order_id}_{$order_item_id}";
      $default_value = isset($order_items_values[$key]) ? $order_items_values[$key] : [];
      $elements['order_items'][$key] = [
        '#title' => '',
        '#type' => 'am_net_order_item',
        '#default_value' => $default_value,
        '#product' => $product,
        '#purchased_entity' => $product_variation,
        '#product_type' => $product->bundle(),
        '#order_item' => $order_item,
        '#order_id' => $order_id,
      ];
    }
    if (!$order_synchronized) {
      // Add Action to Mark the order as synchronized.
      $elements['am_net_sync_action'] = [
        '#type' => 'am_net_complete_order_sync_action',
        '#order_id' => $order_id,
      ];
    }

    return $elements;
  }

  /**
   * Validate and pre-process field values.
   *
   * @param array $element
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validate(array $element, FormStateInterface $form_state) {
    if (isset($element['#parents'])) {
      $order_items = $form_state->getValue($element['#parents']);
      if (isset($order_items['description'])) {
        unset($order_items['description']);
      }
      if (isset($order_items['sync_status_desc'])) {
        unset($order_items['sync_status_desc']);
      }
      $order_items = self::formatValues($order_items);
      $form_state->setValue($element['#parents'], $order_items);
    }
  }

  /**
   * Format Order Item Values.
   *
   * @param array $order_items
   *   Required field, The order item values.
   */
  public static function formatValues(array $order_items = []) {
    if (!empty($order_items)) {
      foreach ($order_items as $key => &$order_item) {
        // Remove unnecessary items.
        if (isset($order_item['sync'])) {
          unset($order_item['sync']);
        }
        if (isset($order_item['order_item_type_info'])) {
          unset($order_item['order_item_type_info']);
        }
        if (isset($order_item['sync_info'])) {
          unset($order_item['sync_info']);
        }
      }
    }
    return $order_items;
  }

}
