<?php

namespace Drupal\vscpa_commerce\Element;

use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form Action to Complete Order Sync with AMNet.
 *
 * @FormElement("am_net_complete_order_sync_action")
 */
class AmNetCompleteOrderSyncAction extends FormElement {

  use AmNetOrderItemSubmitHandlersTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#order_id' => NULL,
      '#process' => [
        [$class, 'formOrderSyncAction'],
      ],
      '#theme_wrappers' => ['form_element'],
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
  public static function formOrderSyncAction(array &$element) {
    $order_id = $element['#order_id'] ?? NULL;
    if (empty($order_id)) {
      return [];
    }
    // String to uniquely identify DOM elements.
    $id = implode('-', $element['#parents']);
    $key = 'order_sync_action';
    $element[$key] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Mark the order as synchronized?'),
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
    $delta = 'complete_order_sync';
    $value = t('Complete Order Syncing');
    $namespace_callback = 'Drupal\vscpa_commerce\Element\AmNetCompleteOrderSyncAction::';
    $sync_callback = $namespace_callback . 'submitSetOrderSyncAsCompleted';
    $element[$key]['submit_changes'] = [
      '#type' => 'button',
      '#value' => $value,
      '#name' => $delta . '_submit',
      '#ajax' => [
        'callback' => $sync_callback,
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => "Marking the order as synchronized...",
        ],
      ],
      '#attributes' => [
        'class' => ['button--small', 'form-item'],
      ],
    ];

    return $element;
  }

}
