<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Implements the Application/Membership Employment Information form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipMultiStepFormBase
 */
class MembershipEmploymentInformationForm extends MembershipMultiStepFormBase {

  /**
   * The List of fields keys used on this step.
   *
   * @var array
   */
  protected $stepFields = [
    // - Step 3. Membership Employment Information form.
    // Section: Your place of employment.
    'field_firm' => TRUE,
    'field_firm_other' => FALSE,
    'field_work_address' => FALSE,
    // Section: Position.
    'field_job_title' => TRUE,
    'field_job_position' => TRUE,
    // Section: Employment Status.
    'field_job_status' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.application.employment_information';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($form instanceof RedirectResponse) {
      return $form;
    }
    if (!$this->isVisible()) {
      // Return to previous form step.
      return $this->redirect('am_net_membership.application.general_information');
    }
    $form_account_fields = [];
    // Section: Your place of employment.
    $fields = [
      'field_firm',
      'field_firm_other',
      'field_work_address',
    ];
    $form_account_fields = array_merge($form_account_fields, $fields);
    $section_title = $this->t('Your Place of Employment');
    $description = $this->t('Enter the first few letters of your Employer\'s name to begin your search. From the list that appears, continue to narrow your search by typing a space followed by US, then another space followed by the state abbreviation, i.e. KPMG US VA... Locations will then be sorted by city and street address. Choose one to select as your employer, or, if you cannot locate your employer, type in "Other" to select and fill out the address information in the fields provided.');
    $this->addFormGroup($fields, $form, '1', $section_title, $description);
    // Change the entity selector on Firm auto-complete.
    $field_name = 'field_firm';
    $form[$field_name][$field_name]['#selection_handler'] = 'default:firm_by_address';
    // Alter Fields.
    // Condition Fields Logic.
    $field_name = 'field_work_address';
    $visible_condition = [
      'visible' => [
        [
          ':input[name="field_firm_other"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $form[$field_name]['#states'] = $visible_condition;
    // Section: Position.
    $fields = [
      'field_job_title',
      'field_job_position',
    ];
    $form_account_fields = array_merge($form_account_fields, $fields);
    $section_title = $this->t('Position');
    $description = $this->t('Please enter your job title and a general position. For example, if you are a "Director of Finance" you would enter that in the field below and select "Director" for the general position.');
    $this->addFormGroup($fields, $form, '2', $section_title, $description);
    // Add required Field.
    $field_name = 'field_job_position';
    $form[$field_name][$field_name]['#required'] = FALSE;
    $is_unlicensed_professional = $this->membershipChecker->isUnlicensedProfessional($this->currentUser);
    $student_position_tid = '90';
    if ($is_unlicensed_professional && isset($form[$field_name][$field_name]['#options'][$student_position_tid])) {
      unset($form[$field_name][$field_name]['#options'][$student_position_tid]);
    }
    // Section: Employment Status.
    $fields = [
      'field_job_status',
    ];
    $form_account_fields = array_merge($form_account_fields, $fields);
    $section_title = $this->t('Employment Status');
    $description = $this->t('Please enter your employment status.');
    $this->addFormGroup($fields, $form, '3', $section_title, $description);
    $field_name = 'field_job_status';
    $form[$field_name][$field_name]['#required'] = TRUE;
    $college_student_job_status_tid = '154';
    if ($is_unlicensed_professional && isset($form[$field_name][$field_name]['#options'][$college_student_job_status_tid])) {
      unset($form[$field_name][$field_name]['#options'][$college_student_job_status_tid]);
    }
    // Reset Weight.
    $this->resetWeight($form);
    // Set Default Values.
    $this->setDefaultValues($form_account_fields, $form);
    // Alter Work Address default values.
    $field_name = 'field_work_address';
    $form[$field_name][$field_name]['#default_value']['country_code'] = NULL;
    // Actions.
    $form['actions']['go_back'] = [
      '#title' => $this->t('Go back'),
      '#type' => 'link',
      '#url' => Url::fromRoute('am_net_membership.application.general_information'),
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
    // Conditional logic validation.
    $field_firm = $form_state->getValue('field_firm');
    $field_firm_other = $form_state->getValue('field_firm_other');
    $field_work_address = $form_state->getValue('field_work_address');
    $field_firm_tid = NULL;
    if (!empty($field_firm)) {
      $field_firm = is_array($field_firm) ? current($field_firm) : NULL;
      $field_firm_tid = isset($field_firm['target_id']) ? $field_firm['target_id'] : NULL;
    }
    // Firm other.
    if ($field_firm_tid == AM_NET_MEMBERSHIP_FIRM_OTHER) {
      // Form the user the provide a firm name.
      if (empty($field_firm_other)) {
        $form_state->setErrorByName('field_firm_other', t('Please enter the name of your firm.'));
      }
      // Force the user to enter work addrees.
      $country_code = isset($field_work_address['country_code']) ? $field_work_address['country_code'] : NULL;
      $locality = isset($field_work_address['locality']) ? $field_work_address['locality'] : NULL;
      $administrative_area = isset($field_work_address['administrative_area']) ? $field_work_address['administrative_area'] : NULL;
      $is_work_address_empty = empty($country_code) || empty($locality) || empty($administrative_area);
      if ($is_work_address_empty) {
        $form_state->setErrorByName('field_work_address', t('Please provide your work address.'));
      }
    }

    // Job Status.
    $job_status = $form_state->getValue('field_job_status');
    if (empty($job_status) || ($job_status == '_none')) {
      $form_state->setErrorByName('field_job_status', t('Please enter your employment status.'));
    }
    else {
      if (in_array($job_status, ['94', '96', '98'])) {
        // Firm name or Work address is require on job status:
        // Full-time, Part-time & Seasonal.
        if (empty($field_firm)) {
          $form_state->setErrorByName('field_firm', t('Please select a firm before continuing.'));
        }
      }
    }

    // Job Position.
    $job_position = $form_state->getValue('field_job_position');
    // Job Position should be required on Job Status: Full-time or
    // College student.
    if ((empty($job_position) || ($job_position == '_none')) && in_array($job_status, ['154', '94'])) {
      $form_state->setErrorByName('field_job_position', t('Please enter your general position.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the Changes.
    $values = $form_state->getValues();
    $this->setFieldsValues($this->stepFields, $values);
    // Go to Next Step.
    $form_state->setRedirect('am_net_membership.application.membership_qualification');
  }

  /**
   * Determines whether the FormStep is visible.
   *
   * @return bool
   *   TRUE if the pane is visible, FALSE otherwise.
   */
  public function isVisible() {
    // This form Step is not visible for College Students.
    return !($this->membershipChecker->isCollegeStudent($this->currentUser));
  }

}
