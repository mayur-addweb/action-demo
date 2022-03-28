<?php

namespace Drupal\am_net_user_profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\user\UserInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\am_net_user_profile\LegislativeContactsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the Elected Officials Form.
 */
class ElectedOfficialsForm extends FormBase {

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The Legislative Contacts Manager.
   *
   * @var \Drupal\am_net_user_profile\LegislativeContactsManager
   */
  protected $legislativeContactsManager;

  /**
   * Constructs a Elected Officials Form.
   *
   * @param \Drupal\am_net_user_profile\LegislativeContactsManager $legislative_contacts_manager
   *   The legislative contacts manager.
   */
  public function __construct(LegislativeContactsManager $legislative_contacts_manager) {
    $this->legislativeContactsManager = $legislative_contacts_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_user_profile.legislative_contacts_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_user_profile.elected_officials_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    if (empty($user)) {
      return [];
    }
    $this->user = $user;
    $form['#id'] = 'elected-officials-form';
    $form['#attributes'] = ['class' => ['elected-officials-form']];
    $form['#attached']['library'][] = 'am_net_user_profile/elected_officials';
    $form['summary'] = [
      '#input' => FALSE,
      '#markup' => '<h3 class="accent-left purple">Elected Officials</h3><div class="section-description">The following legislator(s) represent your House, Senate and congressional district and/or are legislator(s) you know. To update your contact(s), follow these instructions:<br><br><ol> <li>Adjust your familiarity with each legislator as needed.</li> <li>If you know legislators other than those listed below, click the "Add" button.</li> <li>When finished click the <strong>Save</strong> button.</li></ol></div><br>',
    ];
    $field_name = 'field_party_affiliation';
    $options = $this->getFieldOptionsAllowedValues($field_name);
    $default_value = $user->get($field_name)->getString();
    $default_value = empty($default_value) ? 'OT' : $default_value;
    $form[$field_name] = [
      '#type' => 'radios',
      '#title' => $this->t('I am registered as a'),
      '#default_value' => $default_value,
      '#options' => $options,
      '#required' => TRUE,
    ];
    // Get familiarity options.
    $options = $this->getFamiliarityOptions();;
    $default_value = $this->legislativeContactsManager->getDefaultLegislativeValues($user, 'field_pol_senator_relates');
    if (!empty($default_value)) {
      // Add Section - My State Senator.
      $form['senator_legislator'] = [
        '#type' => 'amnet_legislator',
        '#title' => $this->t('My State Senator'),
        '#default_value' => $default_value,
        '#is_editable' => FALSE,
        '#options' => $options,
      ];
    }
    // Add Section - My State Delegate.
    $default_value = $this->legislativeContactsManager->getDefaultLegislativeValues($user, 'field_pol_delegate_relates');
    if (!empty($default_value)) {
      $form['delegate_legislator'] = [
        '#type' => 'amnet_legislator',
        '#title' => $this->t('My State Delegate'),
        '#default_value' => $default_value,
        '#is_editable' => FALSE,
        '#options' => $options,
      ];
    }
    // List Other Contact.
    $other_relates = $user->get('field_pol_other_relates')->getValue();
    if (!empty($other_relates)) {
      foreach ($other_relates as $key => $other_relate) {
        $paragraph_id = $other_relate['target_id'] ?? NULL;
        if (!empty($paragraph_id)) {
          $default_value = $this->legislativeContactsManager->extractLegislativeValues($paragraph_id);
          $form["other_legislator_{$paragraph_id}"] = [
            '#type' => 'amnet_legislator',
            '#title' => $this->t('Other legislators'),
            '#default_value' => $default_value,
            '#is_editable' => TRUE,
            '#options' => $options,
          ];
        }
      }
    }
    // Add Section - Add another key person.
    $legislators = $this->getLegislators();
    $form['add_another_person'] = [
      '#type' => 'amnet_legislator_key_person',
      '#title' => $this->t('Add Another Key Person'),
      '#options' => $options,
      '#legislators' => $legislators,
    ];
    // Actions.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 10,
      '#attributes' => ['class' => ['btn-purple']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state::hasAnyErrors()) {
      return;
    }
    $items = $form_state->getValues();
    // Update: party affiliation.
    $field_name = 'field_party_affiliation';
    $field_value = $items[$field_name] ?? NULL;
    $this->user->set($field_name, $field_value);
    // Update: Senator Legislator.
    $item = $items['senator_legislator']['details']['right'] ?? NULL;
    $person_id = $item['person_id'] ?? NULL;
    if (!empty($person_id)) {
      $familiarity = $this->extractFamiliarity($item);
      $field_name = 'field_pol_senator_relates';
      $this->legislativeContactsManager->updateDefaultLegislativeValue($this->user, $field_name, $person_id, $familiarity);
    }
    // Update: Delegate Legislator.
    $item = $items['delegate_legislator']['details']['right'] ?? NULL;
    $person_id = $item['person_id'] ?? NULL;
    if (!empty($person_id)) {
      $familiarity = $this->extractFamiliarity($item);
      $field_name = 'field_pol_delegate_relates';
      $this->legislativeContactsManager->updateDefaultLegislativeValue($this->user, $field_name, $person_id, $familiarity);
    }

    // Update: Other Legislator.
    $other_items = [];
    $person_id = $items['add_another_person']['details']['full']['legislator'] ?? NULL;
    $item = $items['add_another_person']['details']['right'] ?? NULL;
    $familiarity = $this->extractFamiliarity($item);
    if (!empty($person_id) && !empty($familiarity)) {
      $other_items[] = [
        'person_id' => $person_id,
        'familiarity' => $familiarity,
        'paragraph_id' => NULL,
        'op' => 'new',
      ];
    }
    foreach ($items as $field_name => $value) {
      if (strpos($field_name, 'other_legislator') !== FALSE) {
        $item = $items[$field_name]['details']['right'] ?? NULL;
        $person_id = $item['person_id'] ?? NULL;
        $paragraph_id = $item['paragraph_id'] ?? NULL;
        $familiarity = $this->extractFamiliarity($item);
        if (!empty($person_id)) {
          $other_items[] = [
            'person_id' => $person_id,
            'paragraph_id' => $paragraph_id,
            'familiarity' => $familiarity,
            'op' => 'update',
          ];
        }
      }
    }
    $inputs = $form_state->getUserInput();
    $triggerElement = $form_state->getTriggeringElement();
    $remove_value = $triggerElement['#value'] ?? NULL;
    $paragraph_id = $triggerElement['#paragraph_id'] ?? NULL;
    $operation = $triggerElement['#operation'] ?? NULL;
    $is_remove = (!isset($inputs['add_contact'])) && !empty($remove_value) && !empty($paragraph_id) && !empty($operation) && ($operation == 'remove');
    if ($is_remove) {
      $other_items[] = [
        'paragraph_id' => $paragraph_id,
        'person_id' => NULL,
        'familiarity' => NULL,
        'op' => 'remove',
      ];
    }
    // Update Other LegislativeValues.
    $this->legislativeContactsManager->updateOtherLegislativeValues($this->user, $other_items);
    // Save the changes.
    $this->user->save();
  }

  /**
   * Extract Familiarity from Array.
   *
   * @param array $item
   *   The base array.
   *
   * @return array
   *   The familiarity listing.
   */
  public function extractFamiliarity(array $item = []) {
    $elements = $item['familiarity'] ?? [];
    if (empty($elements)) {
      return [];
    }
    $familiarity = [];
    foreach ($elements as $delta => $value) {
      if (!empty($value)) {
        $familiarity[] = $value;
      }
    }
    return $familiarity;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::messenger()->addMessage("Your changes has been saved.");
  }

  /**
   * Get user field options allowed values.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   array The array of allowed values.
   */
  public function getFieldOptionsAllowedValues($field_name = '') {
    if (!$this->user->hasField($field_name)) {
      return [];
    }
    $field_definition = $this->user->get($field_name)->getFieldDefinition();
    $field_storage_definition = $field_definition->getFieldStorageDefinition();
    return options_allowed_values($field_storage_definition);
  }

  /**
   * Get Familiarity field options allowed values.
   *
   * @return array
   *   array The array of allowed values.
   */
  public function getFamiliarityOptions() {
    $vid = 'political_relate';
    /* @var \Drupal\taxonomy\TermStorage $term_storage */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $term_storage->loadTree($vid, 0, NULL, TRUE);
    if (empty($terms)) {
      return [];
    }
    $options = [];
    /* @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $label = $term->getName();
      $id = $term->id();
      if (!empty($label) && !empty($id)) {
        $options[$id] = $label;
      }
    }
    return $options;
  }

  /**
   * Get the list of Legislators.
   *
   * @return array
   *   The array list of Legislators.
   */
  public function getLegislators() {
    return $this->legislativeContactsManager->getLegislators();
  }

}
