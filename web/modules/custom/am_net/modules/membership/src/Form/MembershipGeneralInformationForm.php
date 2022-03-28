<?php

namespace Drupal\am_net_membership\Form;

use Drupal\am_net\PhoneNumberHelper;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Implements the Application/Membership General Information form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipMultiStepFormBase
 */
class MembershipGeneralInformationForm extends MembershipMultiStepFormBase {

  /**
   * The List of fields keys used on this step.
   *
   * @var array
   */
  protected $stepFields = [
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
    'field_ethnic_origin' => TRUE,
    // - Section: Home Information.
    'field_home_address' => TRUE,
    // Section: Contact Information.
    'field_home_phone' => TRUE,
    'field_work_phone' => TRUE,
    'field_mobile_phone' => TRUE,
    'field_contact_pref' => TRUE,
    'field_email' => TRUE,
    'field_secondary_emails' => FALSE,
    // Section: Certifications.
    'field_licensed_in' => FALSE,
    'field_cert_va_no' => FALSE,
    'field_cert_va_date' => FALSE,
    'field_cert_other' => FALSE,
    'field_cert_other_no' => FALSE,
    'field_cert_other_date' => FALSE,
    // Section: Education.
    'field_undergrad_loc' => FALSE,
    'field_other_undergraduate' => FALSE,
    'field_undergrad_date' => FALSE,
    'field_graduate_loc' => FALSE,
    'field_other_graduate' => FALSE,
    'field_grad_date' => FALSE,

  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.application.general_information';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($form instanceof RedirectResponse) {
      return $form;
    }
    $form_account_fields = [];
    // Section: General Information.
    $fields = [
      'field_givenname',
      'field_additionalname',
      'field_familyname',
      'field_name_suffix',
      'field_nickname',
      'field_name_creds',
      'field_dob',
      'field_gender',
      'field_ethnic_origin',
    ];
    $form_account_fields = array_merge($form_account_fields, $fields);
    $this->addFormGroup($fields, $form, '1', $this->t('General Information'));
    // Alter field: Ethnic Origin.
    $field_name = 'field_ethnic_origin';
    $form[$field_name]['#required'] = TRUE;
    $form[$field_name][$field_name]['#required'] = TRUE;
    $form[$field_name][$field_name]['#description'] = t("<small>Why are we asking for personal information? We're committed to ensuring our organization represents the strong, evolving and diverse members of the CPA profession. Please take a moment to review your profile, make any necessary updates, then click 'Save.' Need help? Contact us at membership@vscpa.com or (800) 733-8272.</small>");
    // Alter Field: Gender.
    $field_name = 'field_gender';
    $form[$field_name]['#required'] = TRUE;
    $form[$field_name][$field_name]['#required'] = TRUE;
    // Alter Field: Date of birth.
    $this->setFieldTypeDate($field_name = 'field_dob', $form);
    // Make the field Required.
    $form[$field_name]['#required'] = TRUE;
    $form[$field_name][$field_name]['#required'] = TRUE;
    // Section: Home Information.
    $fields = [
      'field_home_address',
    ];
    $form_account_fields = array_merge($form_account_fields, $fields);
    $this->addFormGroup($fields, $form, '2', $this->t('Home Information'));
    // Section: Contact Information.
    $fields = [
      'field_home_phone',
      'field_work_phone',
      'field_mobile_phone',
      'field_contact_pref',
    ];
    $form_account_fields = array_merge($form_account_fields, $fields);
    $this->addFormGroup($fields, $form, '3', $this->t('Contact Information'));
    $field_contact_pref = [];
    if (isset($form['field_contact_pref'])) {
      $field_contact_pref = $form['field_contact_pref'];
      unset($form['field_contact_pref']);
    }
    // Add manually fields.
    $form['field_email'] = [
      '#type' => 'container',
      'field_email' => [
        '#type' => 'email',
        '#title' => $this->t('Primary email'),
        '#required' => TRUE,
        '#description' => $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.'),
      ],
    ];
    $form['field_secondary_emails'] = [
      '#type' => 'container',
      'field_secondary_emails' => [
        '#type' => 'email',
        '#title' => $this->t('Secondary email'),
        '#required' => FALSE,
      ],
    ];
    $fields = [
      'field_email',
      'field_confirm_email',
      'field_secondary_emails',
    ];
    $form_account_fields = array_merge($form_account_fields, $fields);
    // Re add field_contact_pref.
    $field_name = 'field_contact_pref';
    $form[$field_name] = $field_contact_pref;
    if (isset($form[$field_name][$field_name]['#title'])) {
      $form[$field_name][$field_name]['#title'] = $this->t('Send all mail to my');
    }
    if (isset($form[$field_name][$field_name]['#options'])) {
      unset($form[$field_name][$field_name]['#options']['NULL']);
      unset($form[$field_name][$field_name]['#options']['_none']);
    }
    if ($this->membershipChecker->isCertifiedPublicAccountant($this->currentUser)) {
      // Section: Certifications.
      $fields = [
        'field_licensed_in',
        'field_cert_va_no',
        'field_cert_va_date',
        'field_cert_other',
        'field_cert_other_no',
        'field_cert_other_date',
      ];
      $form_account_fields = array_merge($form_account_fields, $fields);
      $this->addFormGroup($fields, $form, '4', $this->t('Certifications'));
      // Alter Field: field_licensed_in to make it required.
      $field_name = 'field_licensed_in';
      unset($form[$field_name][$field_name]['#options']['_none']);
      $form[$field_name][$field_name]['#required'] = TRUE;
      $form[$field_name][$field_name]['#default_value'] = 'in_and_out_of_state';
      // Conditional logic: In state.
      $in_state_invisible_condition = [
        'invisible' => [
          ':input[name="field_licensed_in"]' => ['value' => 'out_of_stat_only'],
        ],
      ];
      $field_conditions = [
        'field_cert_va_no',
        'field_cert_va_date',
      ];
      foreach ($field_conditions as $key => $field_name) {
        $this->setFieldStates($field_name, $form, $in_state_invisible_condition, $parent = TRUE);
      }
      // Conditional logic: Out of State.
      $out_of_state_invisible_condition = [
        'invisible' => [
          ':input[name="field_licensed_in"]' => ['value' => 'in_state_only'],
        ],
      ];
      $field_conditions = [
        'field_cert_other',
        'field_cert_other_no',
        'field_cert_other_date',
      ];
      foreach ($field_conditions as $key => $field_name) {
        $this->setFieldStates($field_name, $form, $out_of_state_invisible_condition, $parent = TRUE);
      }
      // Alter Field: Original Date of Virginia Certification.
      $this->setFieldTypeDate('field_cert_va_date', $form);
      // Alter Field: Original Date of Out-of-State Certification.
      $this->setFieldTypeDate('field_cert_other_date', $form);
    }
    else {
      $is_college_student = $this->membershipChecker->isCollegeStudent($this->currentUser);
      $is_unlicensed_professional = $this->membershipChecker->isUnlicensedProfessional($this->currentUser);
      // Section: Education.
      $fields = [
        'field_undergrad_loc',
        'field_other_undergraduate',
        'field_undergrad_date',
        'field_graduate_loc',
        'field_other_graduate',
        'field_grad_date',
      ];
      $form_account_fields = array_merge($form_account_fields, $fields);
      $this->addFormGroup($fields, $form, '5', $this->t('Education'));
      // Alter Field: Undergraduate Date of graduation.
      $this->setFieldTypeDate($field_name = 'field_undergrad_date', $form);
      // Alter Field: Graduate Date of graduation.
      $this->setFieldTypeDate($field_name = 'field_grad_date', $form);
      // Undergraduate info should NOT be required for associates/fellows.
      if ($is_college_student || $is_unlicensed_professional) {
        // Conditional logic: Undergraduate College or University.
        $field_name = 'field_undergrad_loc';
        unset($form[$field_name][$field_name]['#options']['_none']);
        $form[$field_name][$field_name]['#required'] = TRUE;
        $field_name = 'field_undergrad_date';
        $form[$field_name][$field_name]['#required'] = TRUE;
      }
    }
    // Reset Weight.
    $this->resetWeight($form);
    // Set Default Values.
    $this->setDefaultValues($form_account_fields, $form);
    // Actions.
    $form['actions']['go_back'] = [
      '#title' => $this->t('Go back'),
      '#type' => 'link',
      '#url' => Url::fromRoute('am_net_membership.application'),
      '#weight' => 1,
      '#attributes' => [
        'class' => ['btn btn-white'],
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Continue to next step'),
      '#weight' => 2,
      '#attributes' => [
        'class' => ['btn-purple'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate that the Primary email and the Secondary email are different.
    $field_email = $form_state->getValue('field_email');
    $field_secondary_emails = $form_state->getValue('field_secondary_emails');
    if (!empty($field_secondary_emails) && ($field_email == $field_secondary_emails)) {
      $form_state->setErrorByName('field_secondary_emails', t('Please enter a Secondary email other than the Primary email.'));
    }
    // Conditional logic validation.
    if ($this->membershipChecker->isCertifiedPublicAccountant($this->currentUser)) {
      // Validate Certifications fields.
      $licensed_in = $form_state->getValue('field_licensed_in');
      if (empty($licensed_in)) {
        $form_state->setErrorByName('field_licensed_in', t('Certification Status is required.'));
      }
      else {
        if (in_array($licensed_in, ['in_and_out_of_state', 'in_state_only'])) {
          $cert_va_no = $form_state->getValue('field_cert_va_no');
          if (empty($cert_va_no)) {
            $form_state->setErrorByName('field_cert_va_no', t('Please enter a valid Certification Number.'));
          }
          $cert_va_date = $form_state->getValue('field_cert_va_date');
          if (empty($cert_va_date)) {
            $form_state->setErrorByName('field_cert_va_date', t('Please enter a valid Certification Date.'));
          }
        }
        if (in_array($licensed_in, ['in_and_out_of_state', 'out_of_stat_only'])) {
          $cert_other = $form_state->getValue('field_cert_other');
          if (empty($cert_other) || in_array($cert_other, ['_none', '100'])) {
            $form_state->setErrorByName('field_cert_other', t('Please enter a valid State of Original Certification.'));
          }
          $cert_other_no = $form_state->getValue('field_cert_other_no');
          if (empty($cert_other_no)) {
            $form_state->setErrorByName('field_cert_other_no', t('Please enter a valid Certification Number for out of state.'));
          }
          $cert_other_date = $form_state->getValue('field_cert_other_date');
          if (empty($cert_other_date)) {
            $form_state->setErrorByName('field_cert_other_date', t('Please enter a valid Certification Date for out of state.'));
          }
        }
        // Reset Other values.
        if (in_array($licensed_in, ['in_state_only'])) {
          $form_state->setValue('field_cert_other', NULL);
          $form_state->setValue('field_cert_other_no', NULL);
          $form_state->setValue('field_cert_other_date', NULL);
        }
        elseif (in_array($licensed_in, ['out_of_stat_only'])) {
          $form_state->setValue('field_cert_va_no', NULL);
          $form_state->setValue('field_cert_va_date', NULL);
        }
      }
    }
    else {
      // Your undergraduate date must be in the future to qualify
      // for student membership.
      // Validate Educations fields.
      // Conditional logic: Undergraduate College or University.
      $undergrad_loc = $form_state->getValue('field_undergrad_loc');
      if ($undergrad_loc == '234') {
        $other_undergraduate = $form_state->getValue('field_other_undergraduate');
        if (empty($other_undergraduate)) {
          $form_state->setErrorByName('field_other_undergraduate', t('Please enter the name of your undergraduate institution.'));
        }
      }

      // Conditional logic: Graduate College or University.
      $graduate_loc = $form_state->getValue('field_graduate_loc');
      if ($graduate_loc == '234') {
        $other_graduate = $form_state->getValue('field_other_graduate');
        if (empty($other_graduate)) {
          $form_state->setErrorByName('field_other_graduate', t('Please enter the name of your graduate institution.'));
        }
      }
      // Conditional logic for college student.
      if ($this->membershipChecker->isCollegeStudent($this->currentUser)) {
        $undergrad_date = $form_state->getValue('field_undergrad_date');
        if (empty($undergrad_date) || (strtotime($undergrad_date) < strtotime('now'))) {
          $form_state->setErrorByName('field_undergrad_date', t('Undergraduate date must be in the future to qualify for student membership.'));
        }
        $grad_date = $form_state->getValue('field_grad_date');
        if (!empty($grad_date)) {
          if (strtotime($grad_date) < strtotime('now')) {
            $form_state->setErrorByName('field_grad_date', t('Your graduate date must be in the future to qualify for student membership.'));
          }
        }
        else {
          if (!empty($graduate_loc) && ($graduate_loc != '_none')) {
            $form_state->setErrorByName('field_grad_date', t('Please enter a valid graduate date.'));
          }
        }
      }
    }
    // TODO: Refactor this to be a service instead?
    $helper = new PhoneNumberHelper();
    // Ensure Phones field format: XXX-XXX-XXXX.
    $phones_fields = [
      'field_home_phone' => 'Home Phone',
      'field_work_phone' => 'Work Phone',
      'field_mobile_phone' => 'Mobile Phone',
    ];
    foreach ($phones_fields as $field_name => $field_label) {
      $phone = $form_state->getValue($field_name);
      if (empty($phone)) {
        continue;
      }
      $formatted = $helper->validateAndFormatPhoneNumber($phone);
      if ($formatted === NULL) {
        $form_state->setErrorByName($field_name, t('Please enter @field_label with the format: XXX-XXX-XXXX.', ['@field_label' => $field_label]));
      }
      else {
        $form_state->setValue($field_name, $formatted);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the Changes.
    $values = $form_state->getValues();
    $this->setFieldsValues($this->stepFields, $values);
    $route_name = 'am_net_membership.application.employment_information';
    if ($this->membershipChecker->isCollegeStudent($this->currentUser)) {
      $route_name = 'am_net_membership.application.membership_qualification';
    }
    // Go to Next Step.
    $form_state->setRedirect($route_name);
  }

}
