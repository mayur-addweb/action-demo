<?php

namespace Drupal\am_net_firms\MembershipQualification;

use Drupal\Core\Url;
use Drupal\am_net_firms\MembershipQualification\Validator\ValidatorRequired;
use Drupal\am_net_firms\MembershipQualification\Validator\ValidatorPastDate;
use Drupal\am_net_firms\MembershipQualification\Validator\ValidatorFutureDate;
use Drupal\am_net_firms\MembershipQualification\Validator\ValidatorMaybeRequired;

/**
 * Provides a trait for the Membership Qualification Steps Definition.
 */
trait StepsDefinitionTrait {

  /**
   * The Membership Selection Step Id.
   *
   * @var string
   */
  protected $membershipSelectionStepId = 'membership.qualification.selection';

  /**
   * The Membership General Information Step Id.
   *
   * @var string
   */
  protected $membershipGeneralInformationStepId = 'membership.qualification.general_information';

  /**
   * The Membership Employment Information Step Id.
   *
   * @var string
   */
  protected $membershipEmploymentInformationStepId = 'membership.qualification.employment_information';

  /**
   * The Membership Qualification Step Id.
   *
   * @var string
   */
  protected $membershipQualificationStepId = 'membership.qualification.application.qualification';

  /**
   * The Membership Qualification Status Step Id.
   *
   * @var string
   */
  protected $membershipQualificationStatusStepId = 'membership.qualification.application.status';

  /**
   * Get steps definition.
   *
   * @return array
   *   Return the defined steps.
   */
  public function getStepsDefinition() {
    $steps_definition = [];
    // Add the "Membership Selection" Step.
    $this->addMembershipSelectionDefinitionStep($steps_definition);
    // Add the "Membership General Information" Step.
    $this->addMembershipGeneralInformationDefinitionStep($steps_definition);
    // Add the "Membership Employment Information" Step.
    $this->addMembershipEmploymentInformationDefinitionStep($steps_definition);
    // Add the "Membership Qualification" Step.
    $this->addMembershipQualificationDefinitionStep($steps_definition);
    // Add the "Membership Qualification Status" Step.
    $this->addMembershipQualificationStatusDefinitionStep($steps_definition);
    // Return Steps Definition.
    return $steps_definition;
  }

  /**
   * Get step by Id.
   *
   * @param string $step_id
   *   The Step id.
   *
   * @return array
   *   The the Step array with its metadata.
   */
  public function getStepById($step_id = NULL) {
    $steps_definition = $this->getStepsDefinition();
    // Show the first Step by default.
    $step_id = $step_id ?? key($steps_definition);
    return $steps_definition[$step_id] ?? [];
  }

  /**
   * Get Step Action Buttons by id.
   *
   * @param string $step_id
   *   The Step id.
   *
   * @return array
   *   The action buttons of the given step.
   */
  public function getStepButtons($step_id = NULL) {
    $step = $this->getStepById($step_id);
    return $step['buttons'] ?? [];
  }

  /**
   * Get Step Field Names by id.
   *
   * @param string $step_id
   *   The Step id.
   *
   * @return array
   *   The field names of the given step.
   */
  public function getStepFieldNames($step_id = NULL) {
    $step = $this->getStepById($step_id);
    return $step['field_names'] ?? [];
  }

  /**
   * Get Step Fields Validators by id.
   *
   * @param string $step_id
   *   The Step id.
   *
   * @return array
   *   The field Validators of the given step.
   */
  public function getStepFieldsValidators($step_id = NULL) {
    $step = $this->getStepById($step_id);
    return $step['field_validators'] ?? [];
  }

  /**
   * Returns a render-able form array that defines a step.
   *
   * @param string $step_id
   *   The Step id.
   *
   * @return array
   *   The form with the Step fields.
   */
  public function buildStepFormElements($step_id = NULL) {
    $step = $this->getStepById($step_id);
    return $step['form_elements'] ?? [];
  }

  /**
   * Returns a render-able form array that defines a Button.
   *
   * @param array $button
   *   The button array metadata.
   *
   * @return array
   *   The button array element.
   */
  public function buildButtonElement(array $button = []) {
    if (empty($button)) {
      return [];
    }
    $element = [];
    $button_type = $button['type'];
    $button_key = $button['key'];
    if ($button_type == 'button') {
      if ($button_key == 'next') {
        $label = t('Continue to next step <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>');
      }
      else {
        $label = t('<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Previous step');
      }
      $goto = $button['goto'];
      $element = [
        '#type' => 'submit',
        '#value' => $label,
        '#goto_step' => $goto,
      ];
      // Add Extra Class.
      if ($button_key == 'next') {
        $element['#attributes'] = [
          'class' => ['btn-purple'],
        ];
      }
      // Check Validation.
      $skip_validation = $button['skip_validation'];
      if ($skip_validation) {
        $element['#skip_validation'] = TRUE;
      }
      // Check Submit Handler.
      $submit_handler = $button['submit_handler'];
      if (!empty($submit_handler)) {
        $element['#submit_handler'] = $submit_handler;
      }
    }
    elseif ($button_type == 'link') {
      $class = ($button_key == 'next') ? ['btn', 'btn-purple'] : ['btn', 'btn-default'];
      $element = [
        '#title' => $button['label'],
        '#type' => 'link',
        '#url' => $button['url'],
        '#attributes' => [
          'class' => $class,
        ],
      ];
    }
    return $element;
  }

  /**
   * Format Button Metadata.
   *
   * @param array $params
   *   The array of parameters.
   *
   * @return array
   *   The button array Metadata.
   */
  public function formatButtonMetadata(array $params = []) {
    if (empty($params)) {
      return [];
    }
    $metadata = [];
    $items = [
      [
        'key' => 'type',
        'default_value' => 'button',
      ],
      [
        'key' => 'key',
        'default_value' => 'next',
      ],
      [
        'key' => 'goto',
        'default_value' => '',
      ],
      [
        'key' => 'skip_validation',
        'default_value' => FALSE,
      ],
      [
        'key' => 'submit_handler',
        'default_value' => NULL,
      ],
      [
        'key' => 'label',
        'default_value' => '',
      ],
      [
        'key' => 'url',
        'default_value' => '',
      ],
      [
        'key' => 'ajaxify',
        'default_value' => TRUE,
      ],
    ];
    foreach ($items as $delta => $item) {
      $key = $item['key'];
      $default_value = $item['default_value'];
      $metadata[$key] = $params[$key] ?? $default_value;
    }
    return $metadata;
  }

  /**
   * Format Steps Definition Metadata.
   *
   * @param array $params
   *   The array of parameters.
   *
   * @return array
   *   The Steps Definition array Metadata.
   */
  public function formatStepsDefinitionMetadata(array $params = []) {
    if (empty($params)) {
      return [];
    }
    $metadata = [];
    $items = [
      [
        'key' => 'field_names',
        'default_value' => [],
      ],
      [
        'key' => 'form_elements',
        'default_value' => [],
      ],
      [
        'key' => 'buttons',
        'default_value' => [],
      ],
      [
        'key' => 'field_validators',
        'default_value' => [],
      ],
    ];
    foreach ($items as $delta => $item) {
      $key = $item['key'];
      $default_value = $item['default_value'];
      $metadata[$key] = $params[$key] ?? $default_value;
    }
    return $metadata;
  }

  /**
   * Add Membership Selection Step to the Steps Definition.
   *
   * @param array $steps_definition
   *   The array of Steps Definition.
   */
  public function addMembershipSelectionDefinitionStep(array &$steps_definition = []) {
    // Field Names.
    $field_names = [
      'field_member_select',
    ];
    // Form Elements.
    $form_elements = [];
    // Section: Application for Membership.
    $section_title = t('Membership Qualification');
    $description = "<p>Please complete the employee's membership qualification by updating employee's information.</p>";
    $form_elements['header'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
      'description' => [
        '#type' => 'item',
        '#markup' => '<div class="group-field-description">' . $description . '</div>',
      ],
    ];
    // Application for Membership.
    $field_name = 'field_member_select';
    $form_elements[$field_name] = [
      '#description' => $this->getFieldDescription($field_name),
      '#type' => 'radios',
      '#default_value' => $this->getFieldValue($field_name),
      '#options' => $this->getFieldOptionsAllowedValues($field_name),
      '#attributes' => [
        'class' => [
          'full-width',
          'member-select',
          'description-required',
        ],
      ],
    ];
    // Buttons.
    $buttons = [];
    $params = [
      'key' => 'next',
      'goto' => $this->membershipGeneralInformationStepId,
      'ajaxify' => TRUE,
    ];
    $buttons[] = $this->formatButtonMetadata($params);
    // Step ID.
    $step_id = $this->membershipSelectionStepId;
    // Add Definition to the array of steps.
    $steps_definition[$step_id] = [
      'field_names' => $field_names,
      'form_elements' => $form_elements,
      'buttons' => $buttons,
    ];
  }

  /**
   * Check if user is Membership Selection is CPA.
   *
   * @return bool
   *   TRUE if the Membership Selection is CPA, otherwise FALSE.
   */
  public function isCertifiedPublicAccountant() {
    return $this->getFieldValue('field_member_select') == 'MF';
  }

  /**
   * Check if user is a college student.
   *
   * @return bool
   *   TRUE if user is a college student, otherwise FALSE.
   */
  public function isCollegeStudent() {
    return $this->getFieldValue('field_member_select') == 'MC';
  }

  /**
   * Check if user is a unlicensed Professional.
   *
   * @return bool
   *   TRUE if user is a unlicensed Professional, otherwise FALSE.
   */
  public function isUnlicensedProfessional() {
    return $this->getFieldValue('field_member_select') == 'MA';
  }

  /**
   * Add Membership Selection Step to the Steps Definition.
   *
   * @param array $steps_definition
   *   The array of Steps Definition.
   */
  public function addMembershipGeneralInformationDefinitionStep(array &$steps_definition = []) {
    // Field Names.
    $field_names = [
      'field_givenname',
      'field_additionalname',
      'field_familyname',
      'field_name_suffix',
      'field_name_creds',
      'field_dob',
      'field_gender',
      'field_licensed_in',
      'field_cert_va_no',
      'field_cert_va_date',
      'field_cert_other',
      'field_cert_other_no',
      'field_cert_other_date',
      'field_undergrad_loc',
      'field_other_undergraduate',
      'field_undergrad_date',
      'field_graduate_loc',
      'field_other_graduate',
      'field_grad_date',
    ];
    $is_college_student = $this->isCollegeStudent();
    $is_unlicensed_professional = $this->isUnlicensedProfessional();
    $is_certified_public_accountant = $this->isCertifiedPublicAccountant();
    // Form Elements.
    $form_elements = [];
    // Section: Application for Membership.
    $section_title = t('General Information');
    $form_elements['header'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
    ];
    // First Name or Initial.
    $field_name = 'field_givenname';
    $form_elements[$field_name] = $this->addTextField($field_name, $class = 'label-required');
    // Middle Name or Initial.
    $field_name = 'field_additionalname';
    $form_elements[$field_name] = $this->addTextField($field_name);
    // Last name.
    $field_name = 'field_familyname';
    $form_elements[$field_name] = $this->addTextField($field_name, $class = 'label-required');
    // Suffix.
    $field_name = 'field_name_suffix';
    $form_elements[$field_name] = $this->addTextField($field_name);
    // Other Credentials.
    $field_name = 'field_name_creds';
    $form_elements[$field_name] = $this->addTextField($field_name);
    // Date of birth.
    $field_name = 'field_dob';
    $form_elements[$field_name] = $this->addDateField($field_name);
    // Gender.
    $field_name = 'field_gender';
    $options = $this->getEntityReferenceOptionsAllowedValues($entity_type = 'taxonomy_term', $vid = 'gender');
    $form_elements[$field_name] = $this->addRadiosField($field_name, $options, $class = 'inline-radios fieldset-legend-required');
    if ($is_certified_public_accountant) {
      // Section: Certifications.
      $section_title = t('Certifications');
      $form_elements['certifications'] = [
        '#type' => 'item',
        '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
      ];
      // Licensed in Field.
      $field_name = 'field_licensed_in';
      $options = $this->getFieldOptionsAllowedValues($field_name);
      $form_elements[$field_name] = $this->addRadiosField($field_name, $options);
      // Virginia Certification #.
      $field_name = 'field_cert_va_no';
      $form_elements[$field_name] = $this->addTextField($field_name);
      $form_elements[$field_name]['#size'] = 6;
      $form_elements[$field_name]['#maxlength'] = 6;
      // Original Date of Virginia Certification.
      $field_name = 'field_cert_va_date';
      $form_elements[$field_name] = $this->addDateField($field_name);
      // State of Original Certification (if other than Virginia).
      $field_name = 'field_cert_other';
      $options = $this->getEntityReferenceOptionsAllowedValues($entity_type = 'taxonomy_term', $vid = 'us_state');
      $form_elements[$field_name] = $this->addSelectField($field_name, $options);
      // Out-of-State Certification #.
      $field_name = 'field_cert_other_no';
      $form_elements[$field_name] = $this->addTextField($field_name);
      $form_elements[$field_name]['#size'] = 12;
      $form_elements[$field_name]['#maxlength'] = 12;
      // Original Date of Out-of-State Certification.
      $field_name = 'field_cert_other_date';
      $form_elements[$field_name] = $this->addDateField($field_name);
    }
    else {
      // Section: Education.
      $section_title = t('Education');
      $form_elements['education'] = [
        '#type' => 'item',
        '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
      ];
      // Undergraduate College or University.
      $field_name = 'field_undergrad_loc';
      // Filter options by type: Educational Facility.
      $view_rows = views_get_view_result('college_or_university', $display_id = 'entity_reference');
      $options = $this->viewResultToOptions($view_rows);
      $form_elements[$field_name] = $this->addSelectField($field_name, $options);
      if ($is_college_student || $is_unlicensed_professional) {
        // Conditional logic: Undergraduate College or University.
        unset($form_elements[$field_name]['#options']['_none']);
      }
      // Other, please list.
      $field_name = 'field_other_undergraduate';
      $form_elements[$field_name] = $this->addTextField($field_name);
      // Date of Graduation.
      $field_name = 'field_undergrad_date';
      $form_elements[$field_name] = $this->addDateField($field_name);
      // Graduate College or University.
      $field_name = 'field_graduate_loc';
      $form_elements[$field_name] = $this->addSelectField($field_name, $options);
      // Other, please list.
      $field_name = 'field_other_graduate';
      $form_elements[$field_name] = $this->addTextField($field_name);
      // Date of Graduation.
      $field_name = 'field_grad_date';
      $form_elements[$field_name] = $this->addDateField($field_name);

    }
    // Step ID.
    $step_id = $this->membershipGeneralInformationStepId;
    // Buttons.
    $buttons = [];
    // Add Previous Button.
    $params = [
      'key' => 'previous',
      'goto' => $this->membershipSelectionStepId,
      'skip_validation' => TRUE,
    ];
    $buttons[] = $this->formatButtonMetadata($params);
    // Add Next Button.
    if ($is_college_student) {
      $params = [
        'key' => 'next',
        'goto' => $this->membershipQualificationStatusStepId,
        'submit_handler' => 'submitValues',
      ];
    }
    else {
      $params = [
        'key' => 'next',
        'goto' => $this->membershipEmploymentInformationStepId,
      ];
    }
    $buttons[] = $this->formatButtonMetadata($params);
    // Fields validators.
    $validators = [];
    // Global fields.
    // First Name or Initial.
    $validators['field_givenname'] = [
      new ValidatorRequired('Please provide employee First Name.'),
    ];
    // Last name.
    $validators['field_familyname'] = [
      new ValidatorRequired('Please provide employee Last name.'),
    ];
    // Date of birth.
    $validators['field_dob'] = [
      new ValidatorRequired('Please provide employee Date of birth.'),
    ];
    // Gender.
    $validators['field_gender'] = [
      new ValidatorRequired('Please select the employee gender.'),
    ];
    // Conditional validators.
    if ($is_certified_public_accountant) {
      // Certifications Validators.
      // Licensed in Field.
      $validators['field_licensed_in'] = [
        new ValidatorRequired('Certification Status is required.'),
      ];
      // Virginia Certification #.
      $validators['field_cert_va_no'] = [
        new ValidatorMaybeRequired('Please enter a valid Certification Number.', 'field_licensed_in', ['in_and_out_of_state', 'in_state_only']),
      ];
      // Original Date of Virginia Certification.
      $validators['field_cert_va_date'] = [
        new ValidatorMaybeRequired('Please enter a valid Certification Date.', 'field_licensed_in', ['in_and_out_of_state', 'in_state_only']),
        new ValidatorPastDate('The original date of Virginia certification must be in the past to qualify for a licensed CPA membership.'),
      ];
      // Certification (if other than Virginia).
      $validators['field_cert_other'] = [
        new ValidatorMaybeRequired('Please enter a valid State of Original Certification.', 'field_licensed_in', ['in_and_out_of_state', 'out_of_stat_only'], ['_none', '100']),
      ];
      // Out-of-State Certification #.
      $validators['field_cert_other_no'] = [
        new ValidatorMaybeRequired('Please enter a valid Certification Number for out of state.', 'field_licensed_in', ['in_and_out_of_state', 'out_of_stat_only']),
      ];
      // Original Date of Out-of-State Certification.
      $validators['field_cert_other_date'] = [
        new ValidatorMaybeRequired('Please enter a valid Certification Date for out of state.', 'field_licensed_in', ['in_and_out_of_state', 'out_of_stat_only']),
        new ValidatorPastDate('The original date of Out-of-State Certification must be in the past to qualify for a licensed CPA membership.'),
      ];
    }
    else {
      // Education Validators.
      // Undergraduate College or University.
      $validators['field_undergrad_loc'] = [
        new ValidatorRequired('Please select Undergraduate College or University.'),
      ];
      // Date of Graduation.
      // 1. Required.
      $validators['field_undergrad_date'] = [
        new ValidatorRequired('Please select employee date of graduation.'),
      ];
      // Other Under-Graduate.
      $validators['field_other_undergraduate'] = [
        new ValidatorMaybeRequired('Please enter the name of your undergraduate institution.', 'field_undergrad_loc', self::OTHER_COLLEGE_ID),
      ];
      // Other Graduate.
      $validators['field_other_graduate'] = [
        new ValidatorMaybeRequired('Please enter the name of your graduate institution.', 'field_graduate_loc', self::OTHER_COLLEGE_ID),
      ];
      // Date of Graduation.
      // 1. Required.
      $validators['field_grad_date'] = [
        new ValidatorFutureDate('The employee graduate date must be in the future to qualify for student membership.'),
      ];
      // Conditional logic for college student.
      if ($is_college_student) {
        // Un-degrade Date.
        // 1. your undergraduate date must be in the future to qualify
        // for student membership.
        $validators['field_undergrad_date'][] = new ValidatorFutureDate('The employee undergraduate date must be in the future to qualify for student membership.');
        // Date of Graduation.
        // 2. your graduate date must be in the future to qualify
        // for student membership.
        $validators['field_grad_date'][] = new ValidatorFutureDate('The employee graduate date must be in the future to qualify for student membership.');
      }
    }
    // Add Definition to the array of steps.
    $params = [
      'field_names' => $field_names,
      'form_elements' => $form_elements,
      'buttons' => $buttons,
      'field_validators' => $validators,
    ];
    $steps_definition[$step_id] = $this->formatStepsDefinitionMetadata($params);
  }

  /**
   * Add Membership Employment Step to the Steps Definition.
   *
   * @param array $steps_definition
   *   The array of Steps Definition.
   */
  public function addMembershipEmploymentInformationDefinitionStep(array &$steps_definition = []) {
    $is_unlicensed_professional = $this->isUnlicensedProfessional();
    $student_position_tid = '90';
    $college_student_job_status_tid = '154';
    // Field Names.
    $field_names = [
      'field_job_title',
      'field_job_position',
      'field_job_status',
    ];
    // Form Elements.
    $form_elements = [];
    // Section: Application for Membership.
    $section_title = t('Position');
    $description = t('<p>Please enter your job title and a general position. For example, if you are a "Director of Finance" you would enter that in the field below and select "Director" for the general position.</p>');
    $form_elements['header'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
      'description' => [
        '#type' => 'item',
        '#markup' => '<div class="group-field-description">' . $description . '</div>',
      ],
    ];
    // Job Title.
    $field_name = 'field_job_title';
    $form_elements[$field_name] = $this->addTextField($field_name, $class = 'label-required');
    // General Position.
    $field_name = 'field_job_position';
    $options = $this->getEntityReferenceOptionsAllowedValues($entity_type = 'taxonomy_term', $vid = 'job_position');
    $form_elements[$field_name] = $this->addSelectField($field_name, $options, $class = 'label-required');
    if ($is_unlicensed_professional && isset($form_elements[$field_name]['#options'][$student_position_tid])) {
      unset($form_elements[$field_name]['#options'][$student_position_tid]);
    }
    // Employment Status.
    $field_name = 'field_job_status';
    $options = $this->getEntityReferenceOptionsAllowedValues($entity_type = 'taxonomy_term', $vid = 'job_status');
    $form_elements[$field_name] = $this->addSelectField($field_name, $options, $class = 'label-required');
    if ($is_unlicensed_professional && isset($form_elements[$field_name]['#options'][$college_student_job_status_tid])) {
      unset($form_elements[$field_name]['#options'][$college_student_job_status_tid]);
    }
    // Step ID.
    $step_id = $this->membershipEmploymentInformationStepId;
    // Buttons.
    $buttons = [];
    // Add Previous Button.
    $params = [
      'key' => 'previous',
      'goto' => $this->membershipGeneralInformationStepId,
      'skip_validation' => TRUE,
    ];
    $buttons[] = $this->formatButtonMetadata($params);
    // Add Next Button.
    $params = [
      'key' => 'next',
      'goto' => $this->membershipQualificationStepId,
    ];
    $buttons[] = $this->formatButtonMetadata($params);
    // Field Validators.
    $validators = [];
    // Job Title.
    $validators['field_job_title'] = [
      new ValidatorRequired('Please provide employee Job Title.'),
    ];
    // General Position.
    $validators['field_job_position'] = [
      new ValidatorRequired('Please provide employee General Position.'),
    ];
    // Employment Status.
    $validators['field_job_status'] = [
      new ValidatorRequired('Please provide employee Employment Status.'),
    ];
    // Add Definition to the array of steps.
    $params = [
      'field_names' => $field_names,
      'form_elements' => $form_elements,
      'buttons' => $buttons,
      'field_validators' => $validators,
    ];
    $steps_definition[$step_id] = $this->formatStepsDefinitionMetadata($params);
  }

  /**
   * Add Membership Qualification Step to the Steps Definition.
   *
   * @param array $steps_definition
   *   The array of Steps Definition.
   */
  public function addMembershipQualificationDefinitionStep(array &$steps_definition = []) {
    $is_college_student = $this->isCollegeStudent();
    $is_unlicensed_professional = $this->isUnlicensedProfessional();
    $is_certified_public_accountant = $this->isCertifiedPublicAccountant();
    // Field Names.
    $field_names = [
      // - Step 4. Membership Qualification form.
      // Section: Membership Qualification.
      'field_membership_qualify',
      // Section: Peer Review Information.
      'field_peer_review_information',
      // Section: Ethics.
      'field_revoked_license',
      // Section: Felony Conviction.
      'field_convicted_felon',
      // Section: Terms and Conditions.
      'field_term_conditions',
      // Section: Fields of Interest.
      'field_fields_of_interest',
    ];
    // Form Elements.
    $form_elements = [];
    // Section: Membership Qualification.
    // Un-licensed Professional.
    if ($is_unlicensed_professional) {
      $form_elements['header'] = [
        '#type' => 'item',
        '#markup' => '<h3 class="accent-left purple">' . t('Membership Qualification') . '</h3>',
      ];
      $field_name = 'field_membership_qualify';
      $options = $this->getFieldOptionsAllowedValues($field_name);
      // @required.
      $form_elements[$field_name] = $this->addRadiosField($field_name, $options, $class = 'full-width field--hide-legend');
      $description = t("Which of the following best describes you? (Select one.)");
      $form_elements[$field_name]['#title'] = $description;
      unset($form_elements[$field_name]['#options']['_none']);
    }
    // Certified Public Accountant.
    if ($is_certified_public_accountant) {
      // Section: Peer Review Information.
      $section_title = t('Peer Review Information');
      $form_elements['peer_review_information'] = [
        '#type' => 'item',
        '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
      ];
      $field_name = 'field_peer_review_information';
      $options = $this->getFieldOptionsAllowedValues($field_name);
      $form_elements[$field_name] = $this->addRadiosField($field_name, $options, $class = 'full-width field--hide-legend inline-radios');
      unset($form_elements[$field_name]['#options']['_none']);
      unset($form_elements[$field_name]['#title']);
      // Section: Ethics.
      $section_title = t('Ethics');
      $form_elements['ethics'] = [
        '#type' => 'item',
        '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
      ];
      $field_name = 'field_revoked_license';
      $options = $this->getFieldOptionsAllowedValues($field_name);
      $form_elements[$field_name] = $this->addRadiosField($field_name, $options, $class = 'full-width field--hide-legend inline-radios');
      unset($form_elements[$field_name]['#options']['_none']);
      // Field convicted felon.
      $field_name = 'field_convicted_felon';
      $options = $this->getFieldOptionsAllowedValues($field_name);
      // @required.
      $form_elements[$field_name] = $this->addRadiosField($field_name, $options, $class = 'full-width field--hide-legend inline-radios');
      unset($form_elements[$field_name]['#options']['_none']);
      // Add Extra None.
      $form_elements['note'] = [
        '#type' => 'item',
        '#markup' => '<div class="group-field-description">' . t('NOTE: Selecting yes to this question requires the VSCPA Professional Ethics Committee to review your application pursuant to Bylaw Section 2.2.8.') . '</div>',
      ];
    }
    elseif (!$is_college_student) {
      // Section: Felony Conviction.
      $section_title = t('Felony Conviction');
      $form_elements['convicted_felon'] = [
        '#type' => 'item',
        '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
      ];
      $field_name = 'field_convicted_felon';
      $options = $this->getFieldOptionsAllowedValues($field_name);
      $form_elements[$field_name] = $this->addRadiosField($field_name, $options, $class = 'full-width fieldset-legend-required inline-radios');
      unset($form_elements[$field_name]['#options']['_none']);
      unset($form_elements[$field_name]['#description']);
      // Add Extra None.
      $description = t('NOTE: Selecting yes to this question requires the VSCPA Professional Ethics Committee to review your application pursuant to Bylaw Section 2.2.8.');
      $form_elements['note'] = [
        '#type' => 'item',
        '#markup' => '<div class="group-field-description">' . $description . '</div>',
      ];
    }
    // Step ID.
    $step_id = $this->membershipQualificationStepId;
    // Buttons.
    $buttons = [];
    // Add Previous Button.
    $goto = ($this->getFieldValue('field_member_select') == 'MC') ? $this->membershipGeneralInformationStepId : $this->membershipEmploymentInformationStepId;
    $params = [
      'key' => 'previous',
      'goto' => $goto,
      'skip_validation' => TRUE,
    ];
    $buttons[] = $this->formatButtonMetadata($params);
    // Add Next Button.
    $params = [
      'key' => 'next',
      'goto' => $this->membershipQualificationStatusStepId,
      'submit_handler' => 'submitValues',
    ];
    $buttons[] = $this->formatButtonMetadata($params);
    // Field Validators.
    $validators = [];
    // Felony Conviction Field.
    $validators['field_convicted_felon'] = [
      new ValidatorRequired('Please select a Felony Conviction option.'),
    ];
    if ($this->getFieldValue('field_member_select') == 'MA') {
      // Membership qualify.
      $validators['field_membership_qualify'] = [
        new ValidatorRequired('Please select a Membership Qualification option.'),
      ];
    }
    // Certified Public Accountant.
    if ($is_certified_public_accountant) {
      // Peer Review Information.
      $validators['field_peer_review_information'] = [
        new ValidatorRequired('Peer Review Information Field is required.'),
      ];
      // Ethics.
      $validators['field_revoked_license'] = [
        new ValidatorRequired('Ethics Field is required.'),
      ];
    }
    // Add Definition to the array of steps.
    $params = [
      'field_names' => $field_names,
      'form_elements' => $form_elements,
      'buttons' => $buttons,
      'field_validators' => $validators,
    ];
    $steps_definition[$step_id] = $this->formatStepsDefinitionMetadata($params);
  }

  /**
   * Add Membership Qualification Status Step to the Steps Definition.
   *
   * @param array $steps_definition
   *   The array of Steps Definition.
   */
  public function addMembershipQualificationStatusDefinitionStep(array &$steps_definition = []) {
    // Get Membership Checker instance.
    /* @var \Drupal\am_net_membership\MembershipCheckerInterface $membership_checker */
    $membership_checker = $this->employeeManagementTool->getMembershipChecker();
    // Check if the user has billing code defined in this point.
    $billingClassCode = $membership_checker->getBillingClassCode($this->employee);
    // User has appropriate billing code.
    $user_has_appropriate_billing_code = !empty($billingClassCode) && ($billingClassCode > 0);
    // Section: Membership Qualification Status.
    // Check Felony Conviction.
    $value = $this->getFieldValue('field_convicted_felon');
    if ($value == 'Y') {
      $message = '<div class="alert alert-info"><strong>Info!</strong> ' . t("We're sorry, but we cannot process your employee application based upon your answer to the ethics question.<br>Please contact VSCPA Member Services at (800) 733-8272.") . '</div>';
    }
    else {
      if ($user_has_appropriate_billing_code) {
        // Membership price.
        $membership_price = $membership_checker->getMembershipPrice($this->employee);
        $price = number_format(floatval($membership_price), 2, '.', ',');
        // Membership license by default expires 6/9 of next year.
        $membership_expiration_date = $membership_checker->getMembershipLicenseExpirationDate('F j, Y');
        $message = "<div class='alert alert-success'><strong>Well Done!</strong> You successfully completed the membership qualification form.<br>As of today's date, your employees dues for this membership year are <strong>$ {$price}, covering your membership through {$membership_expiration_date}.</strong></div>";
      }
      else {
        $message = '<div class="alert alert-warning"><strong>Info!</strong> ' . t('Based on your information, please contact VSCPA at (800) 733-8272 to determine your membership eligibility.') . '</div>';
      }
    }
    $section_title = t('Membership Qualification Status');
    $description = "<p>" . $message . "</p>";
    // Form Elements.
    $form_elements['membership_status'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
      'description' => [
        '#type' => 'item',
        '#markup' => '<div class="group-field-description">' . $description . '</div>',
      ],
    ];
    // Step ID.
    $step_id = $this->membershipQualificationStatusStepId;
    // Buttons.
    $buttons = [];
    if (!$user_has_appropriate_billing_code) {
      // Add Previous Button.
      $params = [
        'key' => 'previous',
        'goto' => $this->membershipQualificationStepId,
        'skip_validation' => TRUE,
      ];
      $buttons[] = $this->formatButtonMetadata($params);
    }
    else {
      $pay_employee_dues_url = Url::fromRoute('am_net_firms.employee_management_tool.manage_dues', ['user' => $this->firmAdmin->id(), 'firm' => $this->firm->id()], []);
      $params = [
        'label' => t('<span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> Pay Employee Dues'),
        'type' => 'link',
        'key' => 'previous',
        'url' => $pay_employee_dues_url,
        'ajaxify' => FALSE,
      ];
      $buttons[] = $this->formatButtonMetadata($params);
    }
    // Add Next Button.
    $manage_my_firm_url = Url::fromRoute('am_net_firms.employee_management_tool.manage_employees', ['user' => $this->firmAdmin->id(), 'firm' => $this->firm->id()], []);
    $params = [
      'label' => t('<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span> Go to Manage Employees/Pay Dues'),
      'type' => 'link',
      'key' => 'next',
      'url' => $manage_my_firm_url,
      'ajaxify' => FALSE,
    ];
    $buttons[] = $this->formatButtonMetadata($params);
    // Add Definition to the array of steps.
    $params = [
      'form_elements' => $form_elements,
      'buttons' => $buttons,
    ];
    $steps_definition[$step_id] = $this->formatStepsDefinitionMetadata($params);
  }

}
