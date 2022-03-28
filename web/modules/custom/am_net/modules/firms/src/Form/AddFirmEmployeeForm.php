<?php

namespace Drupal\am_net_firms\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\taxonomy\TermInterface;

/**
 * Implements the 'Create New Employee' Form.
 */
class AddFirmEmployeeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_firms.employee_management_tool.add_firm_employee';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL, TermInterface $firm = NULL) {
    $form['#id'] = 'add-firm-employees-form';
    $form['#attributes'] = ['class' => ['add-firm-employees-form']];
    // Panel Personal information.
    $form['personal_information'] = [
      '#type' => 'container',
      '#title' => $this->t('Personal Information'),
      '#attributes' => [
        'class' => [
          'personal_information',
          'panel',
          'panel-default',
          'no-padding',
        ],
      ],
    ];
    // First name & Last name.
    $form['personal_information']['field_givenname'] = [
      '#type' => 'textfield',
      '#default_value' => '',
      '#title' => '&nbsp;',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('First Name'),
      ],
      '#prefix' => '<div class="panel-heading">' . $this->t('Personal Information') . '</div><div class="row"><div class="col-lg-6 empty-label">',
      '#suffix' => '</div>',
    ];
    $form['personal_information']['field_familyname'] = [
      '#type' => 'textfield',
      '#default_value' => '',
      '#title' => '&nbsp;',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Last Name'),
      ],
      '#prefix' => '<div class="col-lg-6 empty-label">',
      '#suffix' => '</div></div>',
    ];
    // Email & Email Confirm.
    $form['personal_information']['email'] = [
      '#type' => 'email',
      '#default_value' => '',
      '#title' => '&nbsp;',
      '#description' => $this->t('Please enter your email. Your email can is used as your VSCPA login. Additionally, this email will be used for all email communication to you from the VSCPA.'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Email')],
      '#prefix' => '<div class="row"><div class="col-lg-6 empty-label">',
      '#suffix' => '</div>',
    ];
    $form['personal_information']['email_confirm'] = [
      '#type' => 'email',
      '#default_value' => '',
      '#title' => '&nbsp;',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Confirm Email')],
      '#prefix' => '<div class="col-lg-6 empty-label">',
      '#suffix' => '</div></div>',
    ];
    // Gender.
    $form['personal_information']['field_gender'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gender'),
      '#options' => [
        4 => $this->t('Female'),
        5 => $this->t('Male'),
        16071 => $this->t('Unspecified'),
      ],
      '#prefix' => '<div class="row"><div class="col-lg-6">',
      '#suffix' => '</div></div>',
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          'field_gender',
          'label-font-weight',
        ],
      ],
    ];
    // License in.
    $prefix = '<div class="row"><div class="col-md-12">';
    $suffix = '</div></div>';
    $entityManager = \Drupal::service('entity_field.manager');
    $fields = $entityManager->getFieldStorageDefinitions('user');
    $options = [
      'no-licensed' => $this->t('Not Licensed'),
    ];
    $options_allowed_values = options_allowed_values($fields['field_licensed_in']);
    $options = array_merge($options, $options_allowed_values);
    $form['personal_information']['field_licensed_in'] = [
      '#type' => 'radios',
      '#title' => $this->t('Is the employee a licensed CPA of any state or territory of the United States or the District of Columbia?'),
      '#default_value' => 'no-licensed',
      '#description' => NULL,
      '#options' => $options,
      '#attributes' => [
        'class' => [
          'field_licensed_in',
          'label-font-weight',
        ],
      ],
      '#prefix' => $prefix,
      '#suffix' => $suffix,
    ];
    // State condition: In-State.
    $condition = [
      [
        ':input[name="field_licensed_in"]' => [
          'value' => 'in_state_only',
        ],
      ],
      'or',
      [
        ':input[name="field_licensed_in"]' => [
          'value' => 'in_and_out_of_state',
        ],
      ],
    ];
    $state_visible_on_state = [
      'visible' => $condition,
      'required' => $condition,
    ];
    // State condition: Out-Of-State.
    $condition = [
      [
        ':input[name="field_licensed_in"]' => [
          'value' => 'out_of_stat_only',
        ],
      ],
      'or',
      [
        ':input[name="field_licensed_in"]' => [
          'value' => 'in_and_out_of_state',
        ],
      ],
    ];
    $state_visible_out_state = [
      'visible' => $condition,
      'required' => $condition,
    ];
    // Virginia Certification #.
    $form['personal_information']['field_cert_va_no'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Virginia Certification #'),
      '#default_value' => NULL,
      '#description' => $this->t('Certification Number must be 1 to 6 digits.'),
      '#attributes' => [
        'class' => [],
      ],
      '#states' => $state_visible_on_state,
      '#prefix' => $prefix,
      '#suffix' => $suffix,
    ];
    // Original Date of Virginia Certification.
    $form['personal_information']['field_cert_va_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Original Date of Virginia Certification'),
      '#states' => $state_visible_on_state,
      '#prefix' => $prefix,
      '#suffix' => $suffix,
    ];
    // State of Original Certification (if other than Virginia).
    $options = $this->getUsStates();
    $form['personal_information']['field_cert_other'] = [
      '#type' => 'select',
      '#title' => $this->t('State of Original Certification (if other than Virginia)'),
      '#default_value' => NULL,
      '#description' => NULL,
      '#options' => $options,
      '#states' => $state_visible_out_state,
      '#prefix' => $prefix,
      '#suffix' => $suffix,
    ];
    // Out-of-State Certification #.
    $form['personal_information']['field_cert_other_no'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Out-of-State Certification #'),
      '#default_value' => NULL,
      '#attributes' => [
        'class' => [],
      ],
      '#states' => $state_visible_out_state,
      '#prefix' => $prefix,
      '#suffix' => $suffix,
    ];
    // Original Date of Out-of-State Certification.
    $form['personal_information']['field_cert_other_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Original Date of Out-of-State Certification'),
      '#states' => $state_visible_out_state,
      '#prefix' => $prefix,
      '#suffix' => $suffix,
    ];
    // Panel Firm Info.
    $form['firm_information'] = [
      '#type' => 'container',
      '#title' => $this->t('Firm'),
      '#attributes' => [
        'class' => [
          'firm_information',
          'panel',
          'panel-default',
          'no-padding',
        ],
      ],
    ];
    $form['firm_information']['firm_summary'] = [
      '#type' => 'item',
      '#input' => FALSE,
      '#markup' => \Drupal::service('am_net_firms.employee_management_tool')->getFirmDescription($firm),
      '#prefix' => '<div class="panel-heading">' . $this->t('Firm Information') . '</div><div class="panel-body"><div class="col-lg-12">',
      '#suffix' => '</div></div>',
    ];
    hide($form['firm_information']);
    // Actions.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsStates() {
    try {
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree('us_state');
    }
    catch (InvalidPluginDefinitionException $e) {
      return [];
    }
    catch (PluginNotFoundException $e) {
      return [];
    }
    $items = [];
    $items['_none'] = "- None -";
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $items[$term->tid] = $term->name;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $firm = $this->getRouteMatch()->getParameter('firm');
    $firm_admin_user = $this->getRouteMatch()->getParameter('user');
    // Validate that the email is not already in use.
    $email = $form_state->getValue('email');
    if ($employee = user_load_by_mail($email)) {
      $employee_url = Url::fromRoute('entity.user.canonical', ['user' => $employee->id()], [])->toString();
      $find_employee_url = Url::fromRoute('am_net_firms.employee_management_tool.find_employees', ['user' => $firm_admin_user->id(), 'firm' => $firm->id()], [])->toString();
      $params = [
        '@email' => $email,
        '@find-employee' => $find_employee_url,
        '@employee' => $employee_url,
      ];
      $error_msq = t('A user with the email <a href="@employee">@email</a> already exist Use <a href="@find-employee">Find employee</a> page to confirm.', $params);
      $form_state->setErrorByName('email', $error_msq);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $firm = $this->getRouteMatch()->getParameter('firm');
    $firm_admin_user = \Drupal::routeMatch()->getParameter('user');
    if (!($firm && $firm_admin_user)) {
      // Show warning message.
      $this->messenger()->addWarning($this->t("A problem has occurred while adding the employee to the firm, please try again later."));
    }
    $user = User::create();
    // Mandatory settings.
    $user->enforceIsNew();
    // Generate a Password.
    $user->setPassword(user_password());
    $email = $form_state->getValue('email');
    $user->setEmail($email);
    // Username must be unique and accept only a-Z,0-9, - _ @ .
    $user->setUsername($email);
    // Optional settings.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $user->set('init', 'email');
    $user->set('langcode', $language);
    $user->set('preferred_langcode', $language);
    $user->set('preferred_admin_langcode', $language);
    $user->activate();
    // Set custom fields.
    $fields = $form_state->getValues();
    // Custom fields.
    foreach ($fields as $field_name => $field_value) {
      if ((strpos($field_name, 'field_') !== FALSE) && $user->hasField($field_name)) {
        $user->set($field_name, $field_value);
      }
    }
    // Handle Field: Licensed.
    $field_licensed_in = $fields['field_licensed_in'] ?? NULL;
    $licensed = ($field_licensed_in == 'in_and_out_of_state' || $field_licensed_in == 'in_state_only');
    $licensed = $licensed ? 'Y' : 'N';
    $user->set('field_licensed', $licensed);
    // Have you ever been convicted of a felony?.
    $user->set('field_convicted_felon', 'N');
    // Term conditions.
    $user->set('field_term_conditions', TRUE);
    // Link user to the firm.
    $user->set('field_firm', $firm->id());
    // Save User.
    try {
      $user->save();
      // Show confirm message.
      $this->messenger()->addMessage("New firm employee with email " . $email . " saved!\n");
    }
    catch (EntityStorageException $e) {
      // Show warning message.
      $this->messenger()->addWarning($this->t("A problem has occurred while adding the employee to the firm, please try again later."));
    }
    // Redirect.
    $form_state->setRedirectUrl(Url::fromRoute('am_net_firms.employee_management_tool.manage_employees', ['user' => $firm_admin_user->id(), 'firm' => $firm->id()], []));
  }

}
