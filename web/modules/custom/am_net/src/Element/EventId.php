<?php

namespace Drupal\am_net\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an event id form element.
 *
 * Usage example:
 * @code
 * $form['event_id'] = [
 *   '#type' => 'amnet_event_id',
 *   '#title' => $this->t('Event ID'),
 *   '#description' => $this->t('Enter a text event code and a 2-digit year.'),
 *   '#default_value' => ['code' => '4-111', 'year' => '18'],
 *   '#required' => TRUE,
 * ];
 * @endcode
 *
 * @FormElement("amnet_event_id")
 */
class EventId extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#size' => 10,
      '#default_value' => NULL,
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
   * Builds the amnet_event_id form element.
   *
   * @param array $element
   *   The initial amnet_event_id form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The built amnet_event_id form element.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #default_value is not a proper array.
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    $default_value = $element['#default_value'];
    if (isset($default_value) && !self::validateDefaultValue($default_value)) {
      throw new \InvalidArgumentException('The #default_value for a amnet_event_id element must be an array with "code" and "year" keys.');
    }

    $element['#tree'] = TRUE;
    $element['#attributes']['class'][] = 'form-type-amnet-event-id';

    $element['code'] = [
      '#type' => 'textfield',
      '#title' => t('Code'),
      '#default_value' => $default_value ? $default_value['code'] : NULL,
      '#required' => $element['#required'],
      '#size' => 11,
      '#maxlength' => 9,
    ];
    $element['year'] = [
      '#type' => 'number',
      '#title' => t('Year'),
      '#default_value' => $default_value ? $default_value['year'] : NULL,
      '#required' => $element['#required'],
      '#size' => 2,
    ];

    // Add the help text if specified.
    if (!empty($element['#description'])) {
      $element['year'] += ['#field_suffix' => ''];
      $element['year']['#field_suffix'] .= '<div class="description">' . $element['#description'] . '</div>';
    }

    return $element;
  }

  /**
   * Validates the default value.
   *
   * @param mixed $default_value
   *   The default value.
   *
   * @return bool
   *   TRUE if the default value is valid, FALSE otherwise.
   */
  public static function validateDefaultValue($default_value) {
    if (!is_array($default_value)) {
      return FALSE;
    }
    if (!array_key_exists('code', $default_value) || !array_key_exists('year', $default_value)) {
      return FALSE;
    }
    return TRUE;
  }

}
