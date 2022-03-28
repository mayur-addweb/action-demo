<?php

namespace Drupal\am_net_membership;

use Drupal\user\Entity\User;

/**
 * The Account Form Display Helper implementation.
 */
class AccountFormDisplayHelper {

  /**
   * The user profile base form.
   *
   * @var array
   */
  protected $baseForm = [];

  /**
   * Get user Account Form.
   *
   * @return array
   *   The Form array.
   */
  public function getForm() {
    $form = $this->baseForm;
    if (empty($form)) {
      $form = \Drupal::service('entity.form_builder')->getForm(User::create(), 'default', $form_state_additions = []);
      $this->baseForm = $form;
    }
    return $form;
  }

  /**
   * Get user Account Form Fields.
   *
   * @param array $included_fields
   *   The array of included fields.
   *
   * @return array
   *   The Form fields array.
   */
  public function getFormFields(array $included_fields = []) {
    if (empty($included_fields)) {
      return [];
    }
    $form_fields = [];
    $form = $this->getForm();
    foreach ($included_fields as $key => $field_name) {
      if (isset($form[$field_name])) {
        $field = $form[$field_name];
        $this->formatField($field);
        $delta = $field_name;
        $form_fields[$delta] = $field;
      }
    }
    return $form_fields;
  }

  /**
   * Get user Account Form.
   *
   * @param array $field
   *   The array of field.
   */
  public function formatField(array &$field = []) {
    // Pre-process fields.
    $delta = 0;
    if (isset($field['widget'][$delta])) {
      $value_type = 'value';
      if (isset($field['widget'][$delta]['value'])) {
        $value_type = 'value';
      }
      elseif (isset($field['widget'][$delta]['address'])) {
        $value_type = 'address';
      }
      elseif (isset($field['widget'][$delta]['target_id'])) {
        $value_type = 'target_id';
      }
      // Remove Deep levels of Cardinality.
      while (isset($field['widget'][$delta][$value_type])) {
        $item = $field['widget'][$delta][$value_type];
        $name = $item['#name'];
        $name = str_replace("[{$delta}][{$value_type}]", '', $name);
        $item['#name'] = $name;
        $field[$name] = $item;
        $this->resetFieldBuild($field[$name]);
        $delta += 1;
      }
    }
    else {
      // Check if is a entity reference field.
      $item = NULL;
      $value_type = '';
      if (isset($field['widget']['target_id'])) {
        $item = $field['widget']['target_id'];
        $value_type = 'target_id';
      }
      elseif (isset($field['widget']['value'])) {
        $item = $field['widget']['value'];
        $value_type = 'value';
      }
      if (!empty($item)) {
        $name = $item['#name'];
        $name = str_replace("[{$value_type}]", '', $name);
        $item['#name'] = $name;
        $field[$name] = $item;
        $this->resetFieldBuild($field[$name]);
      }
      else {
        // Remove Container.
        $name = isset($field['widget']['#name']) ? $field['widget']['#name'] : '';
        if (empty($name)) {
          $name = $field['widget']['#field_name'];
        }
        if (!empty($name)) {
          $field[$name] = $field['widget'];
          $this->resetFieldBuild($field[$name]);
        }
      }
    }
    // Unset Widget.
    unset($field['widget']);
    $this->resetFieldBuild($field);
  }

  /**
   * Reset Field Build.
   *
   * @param array $field
   *   The array of field.
   */
  public function resetFieldBuild(array &$field = []) {
    // Reset properties.
    $properties = [
      '#title',
      '#description',
      '#required',
      '#delta',
      '#weight',
      '#type',
      '#default_value',
      '#options',
      '#name',
      'widget',
      '#attributes',
      '#title_display',
      '#description_display',
      '#id',
      '#ajax',
      '#autocomplete_route_name',
      '#input',
      '#theme_wrappers',
      '#field_name',
      '#tree',
      '#cardinality',
      '#cardinality_multiple',
      '#max_delta',
      '#field_parents',
      'value',
      'address',
      '#key_column',
      '#used_fields',
      '#available_countries',
      'target_id',
      '#target_type',
      '#selection_handler',
      '#selection_settings',
      '#validate_reference',
      '#maxlength',
      '#size',
      '#placeholder',
      '#tags',
      '#autocreate'
    ];
    $properties = array_flip($properties);
    foreach ($field as $key => $property) {
      if (!isset($properties[$key]) && !(strpos($key, 'field') !== FALSE)) {
        unset($field[$key]);
      }
    }
  }

}
