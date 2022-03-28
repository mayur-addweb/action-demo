<?php

namespace Drupal\am_net_firms\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for handle Modal Registration.
 *
 * @FormElement("modal_registration")
 */
class ModalRegistration extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'modal_registration',
      '#input' => TRUE,
      '#is_firm_admin' => FALSE,
      '#employees' => NULL,
      '#sessions' => NULL,
      '#default_value' => NULL,
      '#curren_user_id' => NULL,
      '#required' => FALSE,
      '#product' => NULL,
      '#selected_variation' => NULL,
      '#variations' => [],
      '#process' => [
        [$class, 'processRegistration'],
      ],
      '#attached' => [
        'library' => ['am_net_firms/modal_registration_widget'],
      ],
    ];
  }

  /**
   * Processes a 'Modal Registration' form element.
   *
   * @param array $element
   *   Render array representing from $elements.
   *
   * @return array
   *   Render array representing from $elements.
   */
  public static function processRegistration(array &$element) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $order_item = [];
    return $order_item;
  }

}
