<?php

namespace Drupal\vscpa_sso\Form;

use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use CommerceGuys\Addressing\AddressFormat\AddressField;
use Drupal\amnet\Names\AmNetNameDataInterface;
use Drupal\am_net_user_profile\Entity\Person;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Form handler for the user register forms.
 */
class UserRegisterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gluu_user_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add attributes.
    $form['#attributes']['class'][] = 'user-form';
    $form['#attributes']['class'][] = 'gluu-user-register-form';
    $form['form_header'] = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => ['field-group-fieldset'],
      ],
    ];
    $form['form_header']['title'] = [
      '#markup' => '<h3>Create Account</h3>',
      '#allowed_tags' => ['h3'],
    ];
    $reset_password = Url::fromRoute('user.pass')
      ->setAbsolute(TRUE)
      ->toString();
    $form['form_header']['header'] = [
      '#type' => 'processed_text',
      '#text' => '<p>An account is required to purchase and access CPE as well as become a member of the VSCPA. If you had an active account on our old site, please click “<a href="/user/password" data-drupal-link-system-path="' . $reset_password . '">Reset Your Password</a>” vs. creating a new account; otherwise, complete the information below.</p>',
      '#format' => 'basic_html',
    ];
    // Add Personal Information.
    $form['personal_information'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personal Information'),
      '#attributes' => [
        'class' => ['field-group-fieldset'],
      ],
    ];
    $form['personal_information']['first_name'] = [
      '#type' => 'textfield',
      '#default_value' => '',
      '#title' => $this->t('First Name or Initial'),
      '#size' => 20,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('First Name'),
      ],
    ];
    $form['personal_information']['last_name'] = [
      '#type' => 'textfield',
      '#default_value' => '',
      '#title' => $this->t('Last Name'),
      '#size' => 20,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Last Name'),
      ],
    ];
    // Gender.
    $form['personal_information']['gender'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gender'),
      '#name' => 'gender',
      '#options' => [
        'F' => 'Female',
        'M' => 'Male',
        'U' => 'Unspecified',
      ],
      '#required' => TRUE,
      '#default_value' => 'U',
    ];
    $form['personal_information']['address'] = [
      '#type' => 'address',
      '#default_value' => [
        'country_code' => 'US',
        'langcode' => 'en',
      ],
      '#field_overrides' => [
        AddressField::ORGANIZATION => FieldOverride::HIDDEN,
        AddressField::POSTAL_CODE => FieldOverride::OPTIONAL,
        AddressField::GIVEN_NAME => FieldOverride::HIDDEN,
        AddressField::FAMILY_NAME => FieldOverride::HIDDEN,
      ],
      '#required' => TRUE,
    ];
    // Add Website Account info.
    $form['website_login'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Website Login'),
      '#attributes' => [
        'class' => ['field-group-fieldset'],
      ],
    ];
    $form['website_login']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#default_value' => NULL,
      '#required' => FALSE,
      '#description' => $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.'),
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];
    $form['website_login']['pass'] = [
      '#type' => 'password_confirm',
      '#title' => '',
      '#size' => 25,
      '#required' => FALSE,
      '#description' => $this->t('Provide a password for the new account in both fields.'),
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];
    // Term & conditions.
    $form['terms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("I agree with the website <a data-toggle='modal' data-target='#form-element--modal'>Terms & Conditions</a> of service"),
      '#wrapper_attributes' => ['class' => ['text-center', 'terms-condition-checkbox']],
      '#default_value' => FALSE,
      '#required' => TRUE,
    ];
    // Add modal related to term & conditions.
    $form['terms_modal'] = [
      '#theme' => 'modal_website_terms_conditions',
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['pull-right'],
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create new account'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Add validation on the field "Email".
    $email = $form_state->getValue('email');
    $email = trim($email);
    // 1. Check if the email address is already taken.
    $person = person_load_by_mail($email, $load_entity = FALSE);
    if (!empty($person)) {
      $form_state->setErrorByName('email', t("The email address <strong>@email</strong> is already in use by another account in our system. If it belongs to you, please <a href='/user/login' class='sign-in'>sign in</a>, or <a href='/user/password' class='sign-in'>reset your password</a>. Questions? Contact (800) 733-8272.", ['@email' => $email]));
      return;
    }
    // 2. Build the Name Data.
    $data = $this->buildNameData($form_state);
    // 3. Set the default error message.
    $error = t('The account could not be created. Please try again.');
    // 4. Create the name record.
    $encoded_data = Json::encode($data);
    $name = $this->getNameClient()->createNameRecord($encoded_data);
    $name_id = $name['NamesID'] ?? NULL;
    if (empty($name_id)) {
      $form_state->setError($form, $error);
      return;
    }
    // 5. Create the Gluu account.
    $password = $form_state->getValue('pass');
    $gluu_data = $this->nameDataToGluuData($data, $password, $name_id);
    $gluu_account = $this->getGluuClient()->createUserFromPersonData($gluu_data, TRUE);
    if (!$gluu_account) {
      $form_state->setError($form, $error);
      return;
    }
    $sso_id = $gluu_account->id ?? NULL;
    if (empty($sso_id)) {
      $form_state->setError($form, $error);
      return;
    }
    // 6. Create the account locally.
    $user_profile_manager = \Drupal::service('am_net_user_profile.manager');
    $user_data = $this->nameDataToUserData($data, $password, $name_id, $sso_id);
    // Create a new Account.
    $user = User::create($user_data);
    $user->enforceIsNew();
    $user->activate();
    $address = $form_state->getValue('address');
    $user->set('field_home_address', $address);
    // Disable syncing for this account.
    $user_profile_manager->lockUserSync($user);
    // Save the changes.
    $user->save();
    // Re-enable syncing for this account.
    $user_profile_manager->unlockUserSync($user);
    // 8. Authenticate the user.
    user_login_finalize($user);
    // 9. Account creation process completed successfully.
    return TRUE;
  }

  /**
   * Converts name data to User data.
   *
   * @param array $data
   *   The name data.
   * @param string $password
   *   The user password.
   * @param string $name_id
   *   The name ID.
   * @param string $sso_id
   *   The Single-sing on ID.
   *
   * @return array
   *   The user data.
   */
  public function nameDataToUserData(array $data = [], $password = NULL, $name_id = NULL, $sso_id = NULL) {
    $fields = [];
    // Set the SSO ID.
    if (!empty($sso_id)) {
      $fields['field_sso_id'] = $sso_id;
    }
    // Set the Password.
    if (empty($password)) {
      $password = user_password();
    }
    $fields['pass'] = $password;
    // Set the Email.
    $email = $data['Email'] ?? NULL;
    if (!empty($email)) {
      $fields['init'] = $email;
      $fields['mail'] = $email;
    }
    // Set language.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $fields['langcode'] = $language;
    $fields['preferred_langcode'] = $language;
    $fields['preferred_admin_langcode'] = $language;
    // Set the Name ID.
    if (!empty($name_id)) {
      $fields['name'] = $name_id;
      $fields['field_amnet_id'] = $name_id;
    }
    // Set Activate.
    $fields['status'] = 1;
    // Set First name.
    $first_name = $data['FirstName'] ?? NULL;
    if (!empty($first_name)) {
      $fields['field_givenname'] = $first_name;
    }
    // Set Last name.
    $first_name = $data['LastName'] ?? NULL;
    if (!empty($first_name)) {
      $fields['field_familyname'] = $first_name;
    }
    // Set the Gender Code.
    $gender_code = $data['GenderCode'] ?? NULL;
    if (!empty($gender_code)) {
      $gender = NULL;
      switch ($gender_code) {
        case 'F':
          $gender = Person::GENDER_FEMALE_TID;
          break;

        case 'M':
          $gender = Person::GENDER_MALE_TID;
          break;

        case 'U':
          $gender = Person::GENDER_UNSPECIFIED_TID;
          break;
      }
      $fields['field_gender'] = $gender;
    }
    // Return the fields.
    return $fields;
  }

  /**
   * Converts name data to Gluu data.
   *
   * @param array $data
   *   The name data.
   * @param string $password
   *   The user password.
   * @param string $name_id
   *   The name ID.
   *
   * @return array
   *   The gluu data.
   */
  public function nameDataToGluuData(array $data = [], $password = NULL, $name_id = NULL) {
    $fields = [
      'mail' => $data['Email'] ?? NULL,
      'pass' => $password,
      'username' => $name_id,
      'nickname' => $data['FirstName'] ?? NULL,
      'familyname' => $data['FirstName'] ?? NULL,
      'givenname' => $data['LastName'] ?? NULL,
      'external_id' => $name_id,
    ];
    return array_filter($fields);
  }

  /**
   * Get Gluu Client service.
   *
   * @return \Drupal\vscpa_sso\GluuClient
   *   The Gluu Client service.
   */
  public function getGluuClient() {
    if (!$this->gluuClient) {
      $this->gluuClient = \Drupal::service('gluu.client');
    }
    return $this->gluuClient;
  }

  /**
   * The Gluu Client.
   *
   * @var \Drupal\vscpa_sso\GluuClient
   */
  protected $gluuClient;

  /**
   * Get Name manager service.
   *
   * @return \Drupal\am_net\AssociationManagementClient
   *   The name manager service.
   */
  public function getNameClient() {
    if (!$this->nameClient) {
      $this->nameClient = \Drupal::service('am_net.client');
    }
    return $this->nameClient;
  }

  /**
   * The Name manager.
   *
   * @var \Drupal\am_net\AssociationManagementClient
   */
  protected $nameClient;

  /**
   * Build the Name Data.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The Name record data.
   */
  public function buildNameData(FormStateInterface $form_state = NULL) {
    if (!$form_state) {
      return [];
    }
    $data = [];
    // 1. Set 'FirstName'.
    $value = $form_state->getValue('first_name');
    if (!empty($value)) {
      $data['FirstName'] = trim($value);
    }
    // 2. Set 'LastName'.
    $value = $form_state->getValue('last_name');
    if (!empty($value)) {
      $data['LastName'] = trim($value);
    }
    // 3. Set 'Email'.
    $value = $form_state->getValue('email');
    if (!empty($value)) {
      $data['Email'] = trim($value);
    }
    // 4. Set 'GenderCode'.
    $value = $form_state->getValue('gender');
    if (!empty($value)) {
      $data['GenderCode'] = trim($value);
    }
    // 5. Set 'Address'.
    $address = $form_state->getValue('address');
    // 6. Set 'AddressLine1'.
    $value = $address['address_line1'] ?? NULL;
    if ($value) {
      $data['AddressLine1'] = $value;
    }
    // 7. Set 'AddressLine2'.
    $value = $address['address_line2'] ?? NULL;
    if ($value) {
      $data['AddressLine2'] = $value;
    }
    // 8. Set 'AddressCity'.
    $value = $address['locality'] ?? NULL;
    if ($value) {
      $data['AddressCity'] = $value;
    }
    // 9. Set 'AddressStreetZip'.
    $value = $address['administrative_area'] ?? NULL;
    if ($value) {
      $data['AddressStreetZip'] = $value;
    }
    // 10. Set 'AddressStreetZip'.
    $value = $address['postal_code'] ?? NULL;
    if ($value) {
      $data['AddressStreetZip'] = $value;
    }
    // 11. Set 'AddressStateCode'.
    $value = $address['administrative_area'] ?? NULL;
    if ($value) {
      $data['AddressStateCode'] = $value;
    }
    // 12. Set 'ForeignCountry'.
    $value = $address['country_code'] ?? NULL;
    if (!empty($value) && ($value != 'US')) {
      $data['ForeignCountry'] = $value;
      // Send Foreign State as ZZ.
      $data['AddressStateCode'] = 'ZZ';
    }
    // 13. Set 'MemberStatusCode'.
    $data['MemberStatusCode'] = 'N';
    // Return the data.
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus('You have successfully logged in!.');
    $form_state->setRedirect('user.page');
    return TRUE;
  }

}
