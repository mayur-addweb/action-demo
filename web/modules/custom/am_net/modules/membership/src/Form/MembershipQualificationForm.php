<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements the Application/Membership Qualification form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipMultiStepFormBase
 */
class MembershipQualificationForm extends MembershipMultiStepFormBase {

  /**
   * The List of fields keys used on this step.
   *
   * @var array
   */
  protected $stepFields = [
    // - Step 4. Membership Qualification form.
    // Section: Membership Qualification.
    'field_membership_qualify' => TRUE,
    // Section: Peer Review Information.
    'field_peer_review_information' => FALSE,
    // Section: Ethics.
    'field_revoked_license' => FALSE,
    // Section: Felony Conviction.
    'field_convicted_felon' => TRUE,
    // Section: Terms and Conditions.
    'field_term_conditions' => TRUE,
    // Section: Fields of Interest.
    'field_fields_of_interest' => FALSE,
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.application.membership_qualification';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($form instanceof RedirectResponse) {
      return $form;
    }
    $is_college_student = $this->membershipChecker->isCollegeStudent($this->currentUser);
    $is_unlicensed_professional = $this->membershipChecker->isUnlicensedProfessional($this->currentUser);
    $is_no_active_working = $this->membershipChecker->isNoActiveWorking($this->currentUser);
    $form_account_fields = [];
    if ($is_unlicensed_professional && !$this->membershipChecker->isEducator($this->currentUser) && !$is_no_active_working) {
      // Section: Membership Qualification.
      $field_name = 'field_membership_qualify';
      $fields = [$field_name];
      $form_account_fields = array_merge($form_account_fields, $fields);
      $section_title = $this->t('Membership Qualification');
      $description = $this->t('Which of the following best describes you? (Select one.)');
      $this->addFormGroup($fields, $form, '1', $section_title, $description);
      // Add Extra Classes.
      $form[$field_name]['#attributes']['class'][] = 'membership_qualify_information';
      $form[$field_name]['#attributes']['class'][] = 'field--hide-legend';
      $form[$field_name][$field_name]['#attributes']['class'][] = 'radios_format_full';
      $form[$field_name][$field_name]['#required'] = TRUE;
      unset($form[$field_name][$field_name]['#options']['_none']);
    }
    if ($this->membershipChecker->isCertifiedPublicAccountant($this->currentUser)) {
      // Section: Peer Review Information.
      $field_name = 'field_peer_review_information';
      $fields = [$field_name];
      $form_account_fields = array_merge($form_account_fields, $fields);
      $section_title = $this->t('Peer Review Information');
      $this->addFormGroup($fields, $form, '1', $section_title);
      // Add Extra Classes.
      $form[$field_name]['#attributes']['class'][] = 'field--hide-legend';
      unset($form[$field_name][$field_name]['#options']['_none']);
      $form[$field_name][$field_name]['#required'] = TRUE;
      // Section: Ethics.
      $fields = [
        'field_revoked_license',
        'field_convicted_felon',
      ];
      $form_account_fields = array_merge($form_account_fields, $fields);
      $section_title = $this->t('Ethics');
      $this->addFormGroup($fields, $form, '2', $section_title);
      $field_name = 'field_revoked_license';
      unset($form[$field_name][$field_name]['#options']['_none']);
      $form[$field_name][$field_name]['#required'] = TRUE;
      // Add Extra Classes.
      $field_name = 'field_convicted_felon';
      $form[$field_name]['note'] = [
        '#type' => 'item',
        '#markup' => '<div class="group-field-description">' . $this->t('NOTE: Selecting yes to this question requires the VSCPA Professional Ethics Committee to review your application pursuant to Bylaw Section 2.2.8.') . '</div>',
      ];
    }
    elseif (!$is_college_student) {
      // Section: Felony Conviction.
      $field_name = 'field_convicted_felon';
      $fields = [$field_name];
      $form_account_fields = array_merge($form_account_fields, $fields);
      $section_title = $this->t('Felony Conviction');
      $description = $this->t('Have you ever been convicted of a felony?');
      $this->addFormGroup($fields, $form, '2', $section_title, $description);
      // Add Extra Classes.
      $form[$field_name]['#attributes']['class'][] = 'field--hide-legend';
      $form[$field_name]['note'] = [
        '#type' => 'item',
        '#markup' => '<div class="group-field-description">' . $this->t('NOTE: Selecting yes to this question requires the VSCPA Professional Ethics Committee to review your application pursuant to Bylaw Section 2.2.8.') . '</div>',
      ];
    }
    // Section: Terms and Conditions.
    $field_name = 'field_term_conditions';
    $fields = [$field_name];
    $form_account_fields = array_merge($form_account_fields, $fields);
    $section_title = $this->t('Terms and Conditions');
    $this->addFormGroup($fields, $form, '3', $section_title);
    // Section: Fields of Interest.
    $field_name = 'field_fields_of_interest';
    $fields = [$field_name];
    $form_account_fields = array_merge($form_account_fields, $fields);
    $section_title = $this->t('Fields of Interest');
    $description = $this->t('Help us customize the communications you receive from the VSCPA by selecting up to five fields of interest below.');
    $this->addFormGroup($fields, $form, '4', $section_title, $description);
    // Add Extra Classes.
    $form[$field_name]['#attributes']['class'][] = 'field--hide-legend';
    // Reset Weight.
    $this->resetWeight($form);
    // Set Default Values.
    $this->setDefaultValues($form_account_fields, $form);
    // Actions.
    $go_back_route_name = 'am_net_membership.application.employment_information';
    if ($is_college_student) {
      $go_back_route_name = 'am_net_membership.application.general_information';
    }
    $form['actions']['go_back'] = [
      '#title' => $this->t('Go back'),
      '#type' => 'link',
      '#url' => Url::fromRoute($go_back_route_name),
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
    $required_fields_error = $this->t('Please complete all the required fields.');
    $errors = $form_state->getErrors();
    if (!empty($errors)) {
      $form_state->setErrorByName('field_term_conditions', $required_fields_error);
    }
    else {
      $is_no_active_working = $this->membershipChecker->isNoActiveWorking($this->currentUser);
      if ($is_no_active_working) {
        $form_state->setValue('field_membership_qualify', 'NULL');
      }
      // Set Values programmatically if applies.
      if ($this->membershipChecker->isUnlicensedProfessional($this->currentUser) && $this->membershipChecker->isEducator($this->currentUser)) {
        // Set Assoc. Qualifications: Accounting Educator.
        $form_state->setValue('field_membership_qualify', 'AE');
      }
      if ($this->membershipChecker->isCollegeStudent($this->currentUser)) {
        // AM.net: Position code should be STU: Student.
        $form_state->setValue('field_job_position', '90');
        $this->stepFields['field_job_position'] = FALSE;
      }
      // Remove the user sync lock.
      $this->membershipChecker->unlockUserSync($this->currentUser);
      $values = $form_state->getValues();
      // Save the Changes.
      $message = $this->setFieldsValues($this->stepFields, $values, $inject_membership_dues = TRUE);
      if (!empty($message) && is_string($message)) {
        $message = t("<p>Oops! This is awkward. Something went wrong!</p><p>If performing a transaction involving a credit card, please contact us immediately at (800) 733-8272 or vscpa@vscpa.com for assistance.</p><p>Otherwise, please try again later, and -- if you continue to experience difficulty -- contact us via the information above.</p><p>Thank you!</p>");
        $form_state->setError($form, $message);
      }
      // Verify that the user has completed all the required fields.
      if (!$this->userCompletedAllRequiredFields()) {
        $form_state->setError($form, $required_fields_error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Check Felony Conviction.
    $value = $this->getFieldValue('field_convicted_felon');
    if ($value == 'Y') {
      // Block user due felony conviction status.
      $form_state->setRedirect('am_net_membership.application.felony_conviction');
    }
    else {
      // Go to Next Step.
      $form_state->setRedirect('am_net_membership.application.membership_dues');
    }
  }

}
