<?php

namespace Drupal\vscpa_commerce\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for Stores Order Items products.
 *
 * @FormElement("order_item_products")
 */
class OrderItemProducts extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#target_type' => NULL,
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processOrderItemProducts'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Processes a Order Items Products form element.
   *
   * @param array $element
   *   Render array representing from $elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State.
   *
   * @return array
   *   Render array representing from $elements.
   */
  public static function processOrderItemProducts(array &$element, FormStateInterface $form_state) {
    $product_ids = is_array($element['#default_value']) ? $element['#default_value'] : [];
    $products = [];
    if (!empty($product_ids)) {
      $productStorage = \Drupal::entityTypeManager()->getStorage('commerce_product');
      $products = $productStorage->loadMultiple($product_ids);
      $products = array_values($products);
    }
    $target_type = $element['#target_type'] ?? 'commerce_product';
    $element['#tree'] = TRUE;
    // Gather the number of names in the form already.
    $num_products = $form_state->get('num_products');
    // By default if the number of items that comes in the default value.
    if ($num_products === NULL) {
      $num_products = count($products);
      $form_state->set('num_products', $num_products);
    }
    // We have to ensure that there is at least one name field.
    if (empty($num_products)) {
      $num_products = 1;
      $form_state->set('num_products', $num_products);
    }
    $element['#tree'] = TRUE;
    // String to uniquely identify DOM elements.
    $id = implode('-', $element['#parents']);
    $dom_id = "order-item-products-{$id}-wrapper";
    $key = 'order_item_products';
    $element[$key] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Products'),
      '#attributes' => ['class' => ['form-am-net-order-item']],
      '#prefix' => "<div id='{$dom_id}'>",
      '#suffix' => '</div>',
      '#element_order_item_products_root' => TRUE,
    ];
    for ($i = 0; $i < $num_products; $i++) {
      $default_value = $products[$i] ?? NULL;
      $element[$key]['product'][$i] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Select Product'),
        '#default_value' => $default_value,
        '#target_type' => $target_type,
      ];
    }
    $element[$key]['actions'] = [
      '#type' => 'actions',
    ];
    $namespace_callback = 'Drupal\vscpa_commerce\Element\OrderItemProducts::';
    $element[$key]['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#limit_validation_errors' => [],
      '#submit' => [$namespace_callback . 'addOne'],
      '#ajax' => [
        'callback' => $namespace_callback . 'addMoreCallback',
        'wrapper' => $dom_id,
      ],
    ];
    // If there is more than one name, add the remove button.
    if ($num_products > 1) {
      $element[$key]['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#limit_validation_errors' => [],
        '#submit' => [$namespace_callback . 'removeCallback'],
        '#ajax' => [
          'callback' => $namespace_callback . 'addMoreCallback',
          'wrapper' => $dom_id,
        ],
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $products = [];
    $items = $input['order_item_products']['product'] ?? [];
    if (!empty($items)) {
      foreach ($items as $delta => $item) {
        $match = static::extractEntityIdFromAutocompleteInput($item);
        if (is_numeric($match)) {
          $products[] = $match;
        }
      }
    }
    if (!empty($products)) {
      // Return default values.
      $products = is_array($element['#default_value']) ? $element['#default_value'] : [];
    }
    return $products;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public static function addMoreCallback(array &$form, FormStateInterface $form_state) {
    $element = self::getTriggeringElement($form, $form_state);
    return $element;
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_products');
    $add_button = $name_field + 1;
    $form_state->set('num_products', $add_button);
    // Since our buildForm() method relies on the value of 'num_products' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_products');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_products', $remove_button);
    }
    // Since our buildForm() method relies on the value of 'num_products' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Gets the form element that triggered submission.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|null
   *   The form element that triggered submission, of NULL if there is none.
   */
  public static function getTriggeringElement(array $form, FormStateInterface $form_state) {
    $element = [];
    $triggering_element = $form_state->getTriggeringElement();
    // Remove the action and the actions container.
    $array_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    while (!isset($element['#element_order_item_products_root'])) {
      $element = NestedArray::getValue($form, $array_parents);
      array_pop($array_parents);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function extractEntityIdFromAutocompleteInput($input) {
    $match = NULL;
    // Take "label (entity id)', match the ID from inside the parentheses.
    if (preg_match("/.+\\s\\(([^\\)]+)\\)/", $input, $matches)) {
      $match = $matches[1];
    }
    return $match;
  }

}
