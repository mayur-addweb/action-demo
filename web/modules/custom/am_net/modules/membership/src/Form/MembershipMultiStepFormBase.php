<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\am_net_membership\MembershipCheckerInterface;
use Drupal\am_net_membership\AccountFormDisplayHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanManagerInterface;

/**
 * Implements the Membership Multi Step Form Base.
 */
abstract class MembershipMultiStepFormBase extends FormBase {

  use MembershipFormTrait;

  /**
   * Account form Display helper.
   *
   * @var \Drupal\am_net_membership\AccountFormDisplayHelper|null
   */
  protected $accountFormDisplayHelper = NULL;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The List of fields keys.
   *
   * @var array
   */
  protected $fieldsKeys = [
    // - Step 1. Membership Selection form.
    'field_member_select' => TRUE,
    // - Step 2. Membership General Information form.
    // Section: General Information.
    'field_givenname' => TRUE,
    'field_additionalname' => FALSE,
    'field_familyname' => TRUE,
    'field_name_suffix' => FALSE,
    'field_nickname' => FALSE,
    'field_name_creds' => FALSE,
    'field_dob' => TRUE,
    'field_gender' => TRUE,
    'field_ethnic_origin' => FALSE,
    // - Section: Home Information.
    'field_home_address' => TRUE,
    // Section: Contact Information.
    'field_home_phone' => FALSE,
    'field_work_phone' => FALSE,
    'field_mobile_phone' => FALSE,
    'field_fax' => FALSE,
    'field_contact_pref' => TRUE,
    'field_email' => TRUE,
    'field_secondary_emails' => FALSE,
    // - Step 3. Membership Employment Information form.
    // Section: Your place of employment.
    'field_firm' => FALSE,
    'field_firm_other' => FALSE,
    'field_work_address' => FALSE,
    // Section: Position.
    'field_job_title' => FALSE,
    'field_job_position' => FALSE,
    // Section: Employment Status.
    'field_job_status' => FALSE,
    // - Step 4. Membership Qualification form.
    // Section: Membership Qualification.
    'field_membership_qualify' => FALSE,
    // Section: Felony Conviction.
    'field_convicted_felon' => TRUE,
    // Section: Terms and Conditions.
    'field_term_conditions' => TRUE,
    // Section: Fields of Interest.
    'field_fields_of_interest' => FALSE,
  ];

  /**
   * Constructs a Membership Multi Step Form Base object.
   *
   * @param \Drupal\am_net_membership\MembershipCheckerInterface $membership_checker
   *   The Membership Checker.
   * @param \Drupal\am_net_membership\AccountFormDisplayHelper $account_form_display_helper
   *   The account form display helper.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanManagerInterface $dues_payment_plan_manager
   *   The dues payment manager.
   */
  public function __construct(MembershipCheckerInterface $membership_checker, AccountFormDisplayHelper $account_form_display_helper, EventDispatcherInterface $event_dispatcher, DuesPaymentPlanManagerInterface $dues_payment_plan_manager) {
    $this->membershipChecker = $membership_checker;
    $this->accountFormDisplayHelper = $account_form_display_helper;
    $this->userIsAnonymous = $this->membershipChecker->userIsAnonymous();
    $this->currentUser = $this->membershipChecker->getCurrentUser();
    $this->eventDispatcher = $event_dispatcher;
    $this->duesPaymentPlanManager = $dues_payment_plan_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_membership.checker'),
      $container->get('am_net_membership.account_form_display_helper'),
      $container->get('event_dispatcher'),
      $container->get('am_net_membership.payment_plans.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->userIsAnonymous) {
      // Redirect to Create Account page.
      return $this->redirect('user.register', [], ['query' => ['destination' => '/membership/application']]);
    }
    if (!$this->membershipChecker->userCanCompleteMembershipApplicationProcess($this->currentUser)) {
      // Redirect to the jon-renew-today page.
      return $this->redirect('am_net_membership.join_or_renew', []);
    }
    $form = [];
    $form['#id'] = "user-register-form";
    $form['#attributes'] = ['class' => ['user-register-form']];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * Set fields values.
   *
   * @param array $fields
   *   The array the field names.
   * @param array $values
   *   The array the field values.
   * @param bool $inject_membership_dues
   *   Flag that determine whether the Membership dues
   *   need to be injected or not.
   *
   * @return bool|string|null
   *   TRUE if the save operation was completed, otherwise the exception string.
   */
  public function setFieldsValues(array $fields = [], array $values = [], $inject_membership_dues = FALSE) {
    if (empty($fields) && empty($values)) {
      return NULL;
    }
    $save_user_changes = FALSE;
    foreach ($values as $field_name => $value) {
      if (isset($fields[$field_name])) {
        if ($this->currentUser->hasField($field_name)) {
          $this->currentUser->set($field_name, $value);
          $save_user_changes = TRUE;
        }
      }
    }
    // Inject Membership dues.
    if ($inject_membership_dues) {
      $this->membershipChecker->setMembershipDues($this->currentUser, $reset = TRUE);
    }
    // Save Changes.
    if ($save_user_changes) {
      $this->currentUser->save();
      $id = $this->currentUser->get('field_amnet_id')->getString();
      $id = trim($id);
      $key = 'entity.' . $id;
      $message = am_net_entity_get_message($key);
      if (!empty($message)) {
        return $message;
      }
    }
    return TRUE;
  }

  /**
   * Check if the current user completed all required fields.
   *
   * @return bool
   *   TRUE user has completed all the required fields, otherwise FALSE.
   */
  public function userCompletedAllRequiredFields() {
    $fields = $this->getRequiredFields();
    // Loop over all the fields and check that required fields
    // values are not empty.
    foreach ($fields as $delta => $field_name) {
      if (!in_array($field_name, ['field_email'])) {
        $val = $this->getFieldValue($field_name);
        if (empty($val) && ($field_name != 'field_convicted_felon')) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Return the list of all required fields.
   *
   * @return array
   *   The list of required fields.
   */
  public function getRequiredFields() {
    if (empty($this->fieldsKeys)) {
      return [];
    }
    $fields = [];
    foreach ($this->fieldsKeys as $field_name => $is_required) {
      if ($is_required) {
        $fields[] = $field_name;
      }
    }
    return $fields;
  }

  /**
   * Reset weight form fields.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function resetWeight(array &$form) {
    $weight = 1;
    foreach ($form as $field_name => $field) {
      if ((strpos($field_name, 'field') !== FALSE) || strpos($field_name, 'group') !== FALSE) {
        $form[$field_name]['#weight'] = $weight;
      }
      $weight += 1;
    }
  }

  /**
   * Set form default values.
   *
   * @param array $fields
   *   An associative array containing the field names.
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function setDefaultValues(array $fields, array &$form) {
    if (empty($fields) || empty($form)) {
      return;
    }
    foreach ($fields as $key => $field_name) {
      if (isset($form[$field_name][$field_name])) {
        $value = NULL;
        if ($field_name == 'field_email') {
          $value = $this->currentUser->getEmail();
        }
        elseif ($field_name == 'field_firm') {
          $default_value = $this->getFieldValue($field_name);
          if (!empty($default_value)) {
            $value = $this->getReferencedEntities([$default_value], 'taxonomy_term');
          }
        }
        else {
          $value = $this->getFieldValue($field_name);
        }

        if (!empty($value)) {
          // Check valid date.
          if (strpos($field_name, '_date') !== FALSE) {
            if (strtotime("-110 year") > strtotime($value)) {
              $value = NULL;
            }
          }
          // Check by field type.
          if (isset($form[$field_name][$field_name]['#type']) && ($form[$field_name][$field_name]['#type'] == 'checkboxes') && !is_array($value)) {
            $value = [$value];
          }
          $form[$field_name][$field_name]['#default_value'] = $value;
        }
      }
    }
  }

  /**
   * Gets a list of entities referenced.
   *
   * @param array $entities
   *   The array of entities ids.
   * @param string $entity_type
   *   The entity type name.
   *
   * @return array
   *   The list of entities referenced.
   */
  protected function getReferencedEntities(array $entities = [], $entity_type = '') {
    if (empty($entities) || empty($entity_type)) {
      return [];
    }
    $referenced_entities = [];
    foreach ($entities as $delta => $target_id) {
      $entity = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->load($target_id);
      if ($entity instanceof EntityInterface) {
        $referenced_entities[] = $entity;
      }
    }
    return $referenced_entities;
  }

  /**
   * Add form Group.
   *
   * @param array $fields
   *   An associative array containing the field names.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param string $section_delta
   *   The section delta.
   * @param string $section_title
   *   The section title.
   * @param string $description
   *   The section description.
   */
  protected function addFormGroup(array $fields, array &$form, $section_delta = '', $section_title = '', $description = '') {
    $section_key = "group_{$section_delta}";
    $form[$section_key] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
    ];
    if (!empty($description)) {
      $form[$section_key]['description'] = [
        '#type' => 'item',
        '#markup' => '<div class="group-field-description">' . $description . '</div>',
      ];
    }
    $form_fields = $this->accountFormDisplayHelper->getFormFields($fields);
    $form += $form_fields;
  }

  /**
   * Set field type date.
   *
   * @param string $field_name
   *   The field name.
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function setFieldTypeDate($field_name = '', array &$form = []) {
    if (empty($field_name) || empty($form) || !isset($form[$field_name][$field_name])) {
      return;
    }
    $form[$field_name][$field_name]['#type'] = 'date';
    $form[$field_name][$field_name]['#default_value'] = [];
    unset($form[$field_name][$field_name]['#attributes']);
  }

  /**
   * Set field states conditions.
   *
   * @param string $field_name
   *   The field name.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $condition
   *   The form state conditions.
   * @param bool $parent
   *   Flag that determine whether the condition need to be added to the
   *   parent container or the child field.
   */
  protected function setFieldStates($field_name = '', array &$form = [], array $condition = [], $parent = TRUE) {
    if (empty($field_name) || empty($form) || empty($condition) || !isset($form[$field_name][$field_name])) {
      return;
    }
    if ($parent) {
      $form[$field_name]['#states'] = $condition;
    }
    else {
      $form[$field_name][$field_name]['#states'] = $condition;
    }
  }

}
