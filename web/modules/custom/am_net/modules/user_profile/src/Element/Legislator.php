<?php

namespace Drupal\am_net_user_profile\Element;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an Legislator form element.
 *
 * Usage example:
 * @code
 * $form['legislator'] = [
 *   '#type' => 'amnet_legislator',
 *   '#title' => $this->t('My State Senator'),
 *   '#default_value' => ['familiarities' => ['1'], 'person_id' => '1', 'paragraph_id' => '1'],
 *   '#required' => TRUE,
 *   '#is_editable' => TRUE,
 *   '#options' => [],
 * ];
 * @endcode
 *
 * @FormElement("amnet_legislator")
 */
class Legislator extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#size' => 10,
      '#element_validate' => [
        [$class, 'validateLegislator'],
      ],
      '#options' => [],
      '#is_editable' => FALSE,
      '#default_value' => ['familiarities' => [], 'person_id' => NULL],
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
      throw new \InvalidArgumentException('The #default_value for a amnet_event_id element must be an array with "familiarities" and "name_id" keys.');
    }
    $element['#tree'] = TRUE;
    $element['#attributes']['class'][] = 'form-type-legislator';
    $element['details'] = [
      '#type' => 'details',
      '#open' => TRUE,
    ];
    // Left Side.
    $element['details']['left'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-sm-3'],
      ],
    ];
    // Set the section Title.
    $title = $element['#title'] ?? NULL;
    if (!empty($title)) {
      $element['details']['left']['title'] = [
        '#type' => 'item',
        '#input' => FALSE,
        '#markup' => "<h4>{$title}</h4>",
      ];
    }
    // Get the Contact Name.
    $person_id = $default_value['person_id'] ?? NULL;
    $person = self::getPersonNode($person_id);
    $first_name_line = "";
    if ($person) {
      $summary = self::getPersonSummary($person);
      $first_name_line = self::getPersonFirstName($person);
      $element['details']['left']['summary'] = [
        '#type' => 'item',
        '#input' => FALSE,
        '#markup' => "<h5>{$summary}</h5>",
      ];
    }
    // Right Side.
    $element['details']['right'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-sm-9'],
      ],
    ];
    // Set the options.
    $options = $element['#options'] ?? NULL;
    $familiarities = $default_value['familiarities'] ?? NULL;
    if (!empty($options)) {
      $element['details']['right']['familiarity'] = [
        '#type' => 'checkboxes',
        '#options' => $options,
        '#required' => TRUE,
        '#default_value' => $familiarities,
      ];
    }
    $element['details']['right']['person_id'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
      '#default_value' => $person_id,
    ];
    // Set paragraph ID.
    $paragraph_id = $default_value['paragraph_id'] ?? NULL;
    if (!empty($paragraph_id)) {
      $element['details']['right']['paragraph_id'] = [
        '#type' => 'hidden',
        '#required' => TRUE,
        '#default_value' => $paragraph_id,
      ];
    }
    $is_editable = $element['#is_editable'] ?? FALSE;
    if ($is_editable) {
      // Actions.
      $element['details']['left']['actions']['#type'] = 'actions';
      $delta = "other_legislator_{$paragraph_id}";
      $is_admin = self::isCurrentUserAdmin();
      if ($is_admin) {
        $remove_label = "Remove — {$first_name_line}";
      }
      else {
        $remove_label = "Remove <span class='hide hidden' aria-hidden='true'>{$paragraph_id}</span>";
      }
      $element['details']['left']['actions'][$delta] = [
        '#value' => $remove_label,
        '#paragraph_id' => $paragraph_id,
        '#button_type' => 'primary',
        '#operation' => 'remove',
        '#type' => 'submit',
        '#weight' => 10,
        '#attributes' => [
          'data-paragraph-id' => $paragraph_id,
        ],
      ];
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
    if (!array_key_exists('familiarities', $default_value) || !array_key_exists('person_id', $default_value)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get Person Node by person_id.
   *
   * @param string $person_id
   *   The person ID.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The Person node, NULL otherwise.
   */
  public static function getPersonNode($person_id = NULL) {
    if (empty($person_id)) {
      return NULL;
    }
    $node = Node::load($person_id);
    if (!$node) {
      return NULL;
    }
    return $node;
  }

  /**
   * Get Person Summary.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The person ID.
   *
   * @return string
   *   The Person Summary, NULL otherwise.
   */
  public static function getPersonSummary(Node $node = NULL) {
    if (!$node) {
      return NULL;
    }
    $first_name = $node->get('field_givenname')->getString();
    $last_name = $node->get('field_familyname')->getString();
    $alt_title = $node->get('field_alt_title')->getString();
    $distric_code = $node->get('field_pol_district')->getString();
    // First Line.
    $first_line = "{$last_name}, {$first_name} {$alt_title}";
    $first_line = trim($first_line);
    $first_line = rtrim($first_line, '.') . '.';
    $names = [
      $first_line,
    ];
    // Add the second Line.
    if (!empty($distric_code)) {
      $names[] = "District {$distric_code}";
    }
    $name = implode("<br>", $names);
    return $name;
  }

  /**
   * Get Person Summary.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The person ID.
   *
   * @return string
   *   The Person Summary, NULL otherwise.
   */
  public static function getPersonFirstName(Node $node = NULL) {
    if (!$node) {
      return NULL;
    }
    $first_name = $node->get('field_givenname')->getString();
    $last_name = $node->get('field_familyname')->getString();
    $alt_title = $node->get('field_alt_title')->getString();
    // First Line.
    $first_line = "{$last_name}, {$first_name} {$alt_title}";
    $first_line = trim($first_line);
    $first_line = rtrim($first_line, '.') . '.';
    return $first_line;
  }

  /**
   * Check if the current user is admin.
   *
   * @return bool
   *   TRUE if the current user admin, FALSE otherwise.
   */
  public static function isCurrentUserAdmin() {
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    if (empty($roles)) {
      return FALSE;
    }
    return in_array('administrator', $roles) || in_array('vscpa_administrator', $roles) || in_array('content_manager', $roles);
  }

  /**
   * Render API callback: Validates the Legislator element.
   *
   * @param array $element
   *   The initial legislator_key_person form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateLegislator(array &$element, FormStateInterface $form_state, array &$complete_form = []) {

  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return [];
  }

}
