<?php

namespace Drupal\vscpa_commerce\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\vscpa_commerce\AmNetOrderInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Provides a form element for Stores Order Items sync data.
 *
 * @FormElement("am_net_order_item")
 */
class AmNetOrderItem extends FormElement {

  use AmNetOrderItemSubmitHandlersTrait;
  use AmNetOrderItemTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#product_type' => NULL,
      '#purchased_entity' => NULL,
      '#product' => NULL,
      '#order_item' => NULL,
      '#order_id' => NULL,
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processOrderItem'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#attached' => [
        'library' => ['vscpa_commerce/widget'],
      ],
    ];
  }

  /**
   * Processes a Order Items form element.
   *
   * @param array $element
   *   Render array representing from $elements.
   *
   * @return array
   *   Render array representing from $elements.
   */
  public static function processOrderItem(array &$element) {
    $default_value = is_array($element['#default_value']) ? $element['#default_value'] : [];
    $order_id = $element['#order_id'] ?? NULL;
    /* @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = isset($element['#order_item']) ? $element['#order_item'] : NULL;
    $order_item_id = ($order_item) ? $order_item->id() : NULL;
    $order_item_key = "{$order_id}_{$order_item_id}";
    $action_key = "action_{$order_item_key}";
    $product = $element['#product'] ?? NULL;
    /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    $purchased_entity = $element['#purchased_entity'] ?? NULL;
    $purchased_entity_id = (!is_null($purchased_entity) && $purchased_entity instanceof ProductVariationInterface) ? $purchased_entity->id() : NULL;
    // Order Item Type.
    $order_item_type = $default_value['order_item_type'] ?? self::determineOrderItemType($purchased_entity);
    // Order Item Title.
    $title = !empty($element['#title']) ? $element['#title'] : self::getOrderItemTitle($product, $purchased_entity);
    // Current Sync Status.
    $sync_status = $default_value['sync_status'] ?? AmNetOrderInterface::ORDER_ITEM_NOT_SYNCHRONIZED;
    $is_sync_completed = ($sync_status == AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED);
    self::$orderItemSyncStatus[$order_item_key] = $sync_status;
    // Current Sync Log.
    $sync_log = $default_value['sync_log'] ?? self::getSyncStatusDesc($order_item_key);
    // String to uniquely identify DOM elements.
    $id = implode('-', $element['#parents']);
    $key = 'sync';
    $element['#tree'] = TRUE;
    $element[$key] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $title,
      '#attributes' => ['class' => ['form-am-net-order-item']],
      '#prefix' => '<div id="am-net-order-item-' . $id . '-wrapper">',
      '#suffix' => '</div>',
      '#element_root' => TRUE,
    ];
    // Hidden Fields.
    $element[$key]['order_id'] = [
      '#type' => 'hidden',
      '#value' => $order_id,
    ];
    $element[$key]['order_item_id'] = [
      '#type' => 'hidden',
      '#value' => $order_item->id(),
    ];
    $element[$key]['purchased_entity_id'] = [
      '#type' => 'hidden',
      '#value' => $purchased_entity_id,
    ];
    $element[$key]['order_item_type'] = [
      '#type' => 'hidden',
      '#value' => $order_item_type,
    ];
    if (!empty($record_id)) {
      $element[$key]['record_id'] = [
        '#type' => 'hidden',
        '#value' => $record_id,
      ];
    }
    $element[$key]['sync_status'] = [
      '#type' => 'hidden',
      '#value' => $sync_status,
    ];
    $element[$key]['sync_log'] = [
      '#type' => 'hidden',
      '#value' => $sync_log,
    ];
    // Open Tags: Start.
    $element[$key]['order_item_start'] = [
      '#markup' => '<div class="layout-order-item-form clearfix"><div class="col-left layout-region">',
    ];
    // Order item type.
    $element[$key]['order_item_type_info'] = [
      '#type' => 'item',
      '#markup' => '<h2 class="label inline">Order Item type:</h2> ' . self::getOrderItemType($order_item_type),
    ];
    // AM.net Record ID.
    if (!empty($record_id) && !self::isEventRegistration($order_item_key)) {
      $element[$key]['record_id'] = [
        '#type' => 'item',
        '#markup' => '<h2 class="label inline">AM.Net Record ID:</h2> ' . $record_id,
      ];
    }
    // Product Name.
    $product_name = ($product) ? $product->label() : FALSE;
    if (!empty($product_name)) {
      $element[$key]['product_name'] = [
        '#type' => 'item',
        '#markup' => '<h2 class="label inline">Product</h2> ' . $product_name,
      ];
    }
    // Variation Name.
    $variation_name = ($purchased_entity) ? $purchased_entity->label() : FALSE;
    if (!empty($variation_name) && ($variation_name != $product_name)) {
      $element[$key]['variation_name'] = [
        '#type' => 'item',
        '#markup' => '<h2 class="label inline">Variation:</h2> ' . $variation_name,
      ];
    }
    // Sync Status Desc.
    if ($order_item_type == 'UND') {
      $element[$key]['sync_status_desc'] = [
        '#type' => 'item',
        '#markup' => '<h2 class="label inline">Sync Status:</h2> Order Item Synchronized',
      ];
    }
    else {
      $sync_status_desc = self::getSyncStatusDesc($order_item_key);
      $element[$key]['sync_status_desc'] = [
        '#type' => 'item',
        '#markup' => '<h2 class="label inline">Sync Status:</h2> ' . $sync_status_desc,
      ];
    }
    // Add Sync Actions.
    $submit_item = '';
    $sync_callback = '';
    $namespace_callback = 'Drupal\vscpa_commerce\Element\AmNetOrderItem::';
    if (self::isDonation($order_item_type)) {
      $submit_item = t('Donation');
      $sync_callback = $namespace_callback . 'submitDonationRecordChanges';
    }
    elseif (self::isEventRegistration($order_item_type)) {
      $submit_item = t('Event registration');
      $sync_callback = $namespace_callback . 'submitEventRegistrationRecordChanges';
    }
    elseif (self::isMembership($order_item_type)) {
      $submit_item = t('Membership Payment');
      $sync_callback = $namespace_callback . 'submitMembershipPaymentRecordChanges';
    }
    elseif (self::isSelfStudyRegistration($order_item_type)) {
      $submit_item = t('Self Study Registration');
      $sync_callback = $namespace_callback . 'submitSelfStudyRegistrationRecordChanges';
    }
    elseif (self::isPeerReviewPayment($order_item_type)) {
      $submit_item = t('Peer Review Payment');
      $sync_callback = $namespace_callback . 'submitPeerReviewPaymentRecordChanges';
    }
    if (!empty($sync_callback) && !$is_sync_completed) {
      $show_submit_changes = TRUE;
      if ($show_submit_changes) {
        $element[$key]['submit_changes'] = [
          '#type' => 'button',
          '#value' => 'Sync ' . $submit_item,
          '#name' => $action_key . '_submit',
          '#ajax' => [
            'callback' => $sync_callback,
            'event' => 'click',
            'progress' => [
              'type' => 'throbber',
              'message' => "Submitting $submit_item...",
            ],
          ],
          '#attributes' => [
            'class' => ['button--small'],
          ],
        ];
        if (self::isMembership($order_item_type)) {
          $element[$key]['submit_changes']['disable_dues_account_creation'] = [
            '#type' => 'checkbox',
            '#title' => t('Do not create Dues Account?'),
          ];
        }
      }
      if (!$show_submit_changes) {
        unset($element[$key]['submit_changes']);
      }
    }
    // Open Tags: End.
    $element[$key]['order_item_left_end'] = [
      '#markup' => '</div>',
    ];
    if (!empty($sync_log) && ($sync_status != AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED) && ($order_item_type != 'UND')) {
      $right_key = 'sync_info';
      $element[$key][$right_key] = [
        '#type' => 'details',
        '#title' => t('Sync Info'),
        '#prefix' => '<div class="col-right layout-region">',
        '#suffix' => '</div>',
      ];
      // Info: last synced.
      $last_synced = $default_value['last_synced'] ?? '';
      if (!empty($last_synced)) {
        $element[$key][$right_key]['sync_status_desc_last_synced'] = [
          '#markup' => '<h2 class="label inline">' . t('Last Synced:') . '</h2> <br/> <pre>' . $last_synced . '</pre> ',
        ];
      }
      // Info: Response.
      $element[$key][$right_key]['sync_status_desc_response'] = [
        '#markup' => '<h2 class="label inline">' . t('Response:') . '</h2> <br/> <pre>' . $sync_log . '</pre> ',
      ];
      // Info: Request.
      $json_request = $default_value['messages']['request'] ?? '';
      if (!empty($json_request)) {
        $element[$key][$right_key]['sync_status_desc_request'] = [
          '#markup' => '<h2 class="label inline">' . t('Request:') . '</h2> <br/> <pre>' . $json_request . '</pre> ',
        ];
      }
    }
    // Open Tags: end.
    $element[$key]['order_item_end'] = [
      '#markup' => '</div>',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $order_item = [];
    // Find the current value of this field.
    if (is_array($input)) {
      $order_item = self::formatElement($input);
    }
    return $order_item;
  }

  /**
   * {@inheritdoc}
   */
  public static function formatElement($input) {
    $order_item = [];
    if ($input != FALSE) {
      $order_item = $input['sync'] ?? [];
    }
    return $order_item;
  }

}
