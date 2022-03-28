<?php

namespace Drupal\vscpa_commerce\Plugin\views\field;

use Drupal\commerce_cart\Plugin\views\field\RemoveButton as RemoveButtonBase;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form element for removing the order item.
 *
 * @ViewsField("vscpa_commerce_order_item_remove_button")
 */
class RemoveButton extends RemoveButtonBase {

  /**
   * Form constructor for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    $form['#cache']['max-age'] = 0;
    // The view is empty, abort.
    if (empty($this->view->result)) {
      unset($form['actions']);
      return;
    }

    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {
      /** @var \Drupal\commerce_order\Entity\Order $order */
      $order = $row->_entity ?? NULL;
      $value = [];
      if ($this->displayRemoveButton($order, $row_index)) {
        $value = [
          '#type' => 'submit',
          '#value' => t('Remove'),
          '#name' => 'delete-order-item-' . $row_index,
          '#remove_order_item' => TRUE,
          '#row_index' => $row_index,
          '#attributes' => ['class' => ['delete-order-item']],
        ];
      }
      $form[$this->options['id']][$row_index] = $value;
    }
  }

  /**
   * Check if the 'remove button' should be displayed.
   *
   * @param mixed $order
   *   The order entity.
   * @param mixed $row_index
   *   The row index.
   *
   * @return bool
   *   TRUE if the button should be displayed, otherwise FALSE.
   */
  public function displayRemoveButton($order = NULL, $row_index = NULL) {
    if (empty($order) || empty($row_index) || !is_numeric($row_index)) {
      return TRUE;
    }
    if (!($order instanceof OrderInterface)) {
      return TRUE;
    }
    $items = $order->getItems();
    if (empty($items)) {
      return TRUE;
    }
    $item = $items[$row_index] ?? NULL;
    if (empty($item)) {
      return TRUE;
    }
    if (!($item instanceof OrderItemInterface)) {
      return TRUE;
    }
    return ($item->bundle() != 'payment_plan_administrative_fee');
  }

}
