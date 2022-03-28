<?php

namespace Drupal\am_net_firms\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationTitleWidget;

/**
 * Plugin implementation of the 'employee selector variation title' widget.
 *
 * @FieldWidget(
 *   id = "employee_selector_commerce_product_variation_title",
 *   label = @Translation("Employee Selector - Product variation title"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EmployeeSelectorVariationTitleWidget extends ProductVariationTitleWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    if (in_array('firm_administrator', $roles)) {
      return [];
    }
    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

}
