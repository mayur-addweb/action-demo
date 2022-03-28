<?php

namespace Drupal\am_net_user_profile\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an LegislatorKeyPerson form element.
 *
 * Usage example:
 * @code
 * $form['legislator_key_person'] = [
 *   '#type' => 'amnet_legislator_key_person',
 *   '#title' => $this->t('Add another key person'),
 *   '#options' => [],
 *   '#legislators' => [],
 * ];
 * @endcode
 *
 * @FormElement("amnet_legislator_key_person")
 */
class LegislatorKeyPerson extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#size' => 10,
      '#options' => [],
      '#legislators' => [],
      '#element_validate' => [
        [$class, 'validateLegislatorKeyPerson'],
      ],
      '#process' => [
        [$class, 'processElement'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#input' => TRUE,
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the legislator_key_person form element.
   *
   * @param array $element
   *   The initial legislator_key_person form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The built legislator_key_person form element.
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form = []) {
    $element['#tree'] = TRUE;
    $element['#attributes']['class'][] = 'form-type-legislator_key_person';
    $element['details'] = [
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $title = $element['#title'] ?? NULL;
    if (!empty($title)) {
      $element['details']['#title'] = $title;
    }
    // Left Side.
    $element['details']['left'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-sm-3'],
      ],
    ];
    // Set the section Title.
    // Get the Familiarity.
    $element['details']['left']['summary'] = [
      '#type' => 'item',
      '#input' => FALSE,
      '#markup' => "Familiarity",
    ];
    // Right Side.
    $element['details']['right'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-sm-9'],
      ],
    ];
    // Set familiarities options.
    $options = $element['#options'] ?? NULL;
    $familiarities = $element['#familiarities'] ?? [];
    if (!empty($options)) {
      $element['details']['right']['familiarity'] = [
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => $familiarities,
      ];
    }
    $element['details']['full'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-md-12'],
      ],
    ];
    // Set legislator options.
    $legislators = $element['#legislators'] ?? [];
    $element['details']['full']['legislator'] = [
      '#type' => 'select',
      '#title' => t('More Legislators'),
      '#options' => $legislators,
    ];
    // Actions.
    $element['details']['full']['actions']['#type'] = 'actions';
    $element['details']['full']['actions']['add_contact'] = [
      '#type' => 'submit',
      '#value' => t('<span class="glyphicon glyphicon-plus"></span> Add'),
      '#button_type' => 'primary',
      '#name' => 'add_contact',
      '#weight' => 10,
    ];
    return $element;
  }

  /**
   * Validate Legislator Key Person element.
   *
   * @param array $element
   *   The initial legislator_key_person form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateLegislatorKeyPerson(array &$element, FormStateInterface $form_state, array &$complete_form = []) {
    $items = $form_state->getValues();
    $inputs = $form_state->getUserInput();
    if (isset($inputs['add_contact'])) {
      // Check the number of familiarities selected.
      $familiarities = $items['add_another_person']['details']['right']['familiarity'] ?? [];
      $checked_items = 0;
      foreach ($familiarities as $delta => $value) {
        if (!empty($value)) {
          $checked_items += 1;
        }
      }
      if (empty($checked_items)) {
        $form_state->setError($element, t('Please select at least one familiarity option to add another key person'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return [];
  }

}
