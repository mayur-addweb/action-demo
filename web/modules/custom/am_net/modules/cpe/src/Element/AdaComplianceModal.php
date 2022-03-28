<?php

namespace Drupal\am_net_cpe\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for handle ADA compliance modal.
 *
 * @FormElement("ada_compliance_modal")
 */
class AdaComplianceModal extends FormElement {

  /**
   * Processes a 'ADA compliance' Modal form element.
   *
   * @param array $element
   *   Render array representing from $elements.
   *
   * @return array
   *   Render array representing from $elements.
   */
  public static function addSpecialNeeds(array &$element) {
    $needs = self::getSpecialNeeds();
    // Prepare default values.
    $default_value = [];
    $selected_items = $needs['dietary_restrictions']['#default_value'] ?? [];
    foreach ($selected_items as $delta => $tid) {
      $default_value[$tid] = TRUE;
    }
    $element['#checked'] = $default_value;
    return $element;
  }

  /**
   * Get the user default special needs.
   *
   * @param string $uid
   *   The current user uid.
   *
   * @return array
   *   Array with the list of user special needs.
   */
  public static function getDefaultSpecialNeeds($uid = NULL) {
    $database = \Drupal::database();
    $query = $database->select('user__field_special_needs', 'needs');
    $query = $query->condition('entity_id', $uid);
    $query = $query->condition('bundle', 'user');
    $query = $query->fields('needs', ['field_special_needs_target_id']);
    $result = $query->execute();
    return $result->fetchCol();
  }

  /**
   * Get the 'ADA compliance' form element.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   *
   * @return array
   *   Render array representing from form elements.
   */
  public static function getSpecialNeeds(AccountInterface $user = NULL) {
    if (!$user) {
      $user = \Drupal::currentUser();
    }
    $element = [
      '#title' => 'Special Assistance Needed?',
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['special_needs_preference_timeslot_groups'],
      ],
    ];
    $text = "<p>Inclusivity matters to us at the VSCPA. Please indicate any special assistance you may need so that we can follow up with you regarding your event experience.</p>";
    $text .= "<p>If indicated below, our logistics team will be in contact with you prior to the event for more information and to make special arrangements as needed.</p>";
    $element['header'] = [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => 'full_html',
    ];
    $default_value = self::getDefaultSpecialNeeds($user->id());
    // Assistance Requested:
    $element['assistance_requested'] = [
      '#type' => 'checkboxes',
      '#tree' => TRUE,
      '#options' => [
        '159' => t('Hearing'),
        '17772' => t('Visual'),
      ],
      '#title' => t('Assistance Requested:'),
      '#default_value' => $default_value,
    ];
    // Dietary Restrictions:
    $element['dietary_restrictions'] = [
      '#type' => 'checkboxes',
      '#tree' => TRUE,
      '#options' => [
        '157' => t('Diabetic'),
        '158' => t('Gluten Free'),
        '161' => t('Lactose Intolerance'),
        '166' => t('Peanut Allergy'),
        '162' => t('Seafood Allergy'),
        '170' => t('Vegetarian / Vegan'),
        '171' => t('Other'),
      ],
      '#title' => t('Dietary Restrictions:'),
      '#default_value' => $default_value,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $order_item = [];
    return $order_item;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'ada_compliance_modal',
      '#input' => TRUE,
      '#special_needs' => NULL,
      '#process' => [
        [$class, 'addSpecialNeeds'],
      ],
      '#attached' => [
        'library' => ['am_net_cpe/modal_ada_compliance_widget'],
      ],
    ];
  }

}
