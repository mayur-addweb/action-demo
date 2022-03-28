<?php

namespace Drupal\vscpa_sso\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;

/**
 * Form for Update login Information between Drupal and Gluu IDP.
 */
class UpdateLoginInformationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vscpa_sso_update_login_information_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {

    if (!$user) {
      return [];
    }

    $form['#attributes']['class'][] = 'user-form';
    $form['#attributes']['class'][] = 'user-update-profile-website-account-form';

    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#default_value' => $user->getEmail(),
      '#required' => TRUE,
      '#description' => $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.'),
    ];

    $form['pass'] = [
      '#type' => 'password_confirm',
      '#title' => '',
      '#size' => 25,
      '#required' => FALSE,
      '#description' => $this->t('To change the current user password, enter the new password in both fields.'),
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];

    $form['entity_id'] = [
      '#type' => 'hidden',
      '#value' => $user->id(),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'vscpa_bootstrap/passwords';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check Target UID.
    $entity_id = trim($form_state->getValue('entity_id'));
    if (empty($entity_id)) {
      $form_state->setErrorByName('mail', $this->t('You are not authorized to do this change.'));
      // Stop Here.
      return;
    }
    $current_user = \Drupal::currentUser();
    // Check if the user is authenticate.
    if ($current_user->isAnonymous()) {
      $form_state->setErrorByName('mail', $this->t('Please login for change your profile information.'));
      // Stop Here.
      return;
    }
    // Check request Consistency.
    $roles = $current_user->getRoles();
    $is_admin = in_array('administrator', $roles) || in_array('vscpa_administrator', $roles);
    if (($entity_id != $current_user->id()) && !$is_admin) {
      // One non-admin user is trying to change other user password.
      $form_state->setErrorByName('mail', $this->t('You are not authorized to do this change.'));
    }
    // Check Email.
    $new_email = trim($form_state->getValue('mail'));
    if (empty($new_email)) {
      $form_state->setErrorByName('mail', $this->t('Email address is required.'));
      // Stop Here.
      return;
    }
    $user = User::load($entity_id);
    if (!$user) {
      $form_state->setErrorByName('mail', $this->t('Please login for change your profile information.'));
      // Stop Here.
      return;
    }
    $field_amnet_id = $user->get('field_amnet_id')->getString();
    $field_amnet_id = trim($field_amnet_id);
    $changed = FALSE;
    $gluuClient = \Drupal::service('gluu.client');
    $old_email = $user->getEmail();
    // Check changes on the Email address.
    $email_changed = ($old_email != $new_email);
    if ($email_changed) {
      // Check that the email is not in already in user - Locally on Drupal.
      $existing_user = user_load_by_mail($new_email);
      if ($existing_user != FALSE) {
        $form_state->setErrorByName('mail', $this->t('The email address(%email) is already taken by another user.', ['%email' => $new_email]));
        // Stop Here.
        return;
      }
      else {
        // Check that the email is not in already in user - Gluu IDP.
        $existing_gluu_user = $gluuClient->getByMail($new_email);
        if ($existing_gluu_user != FALSE) {
          $form_state->setErrorByName('mail', $this->t('The email address(%email) is already taken by another user.', ['%email' => $new_email]));
          // Stop Here.
          return;
        }
      }
      $changed = TRUE;
    }
    // Check changes on the Password.
    $pass = trim($form_state->getValue('pass'));
    if (!empty($pass)) {
      $changed = TRUE;
    }
    // Perform the changes if apply.
    if ($changed) {
      // Get the Username.
      $username = $field_amnet_id;
      // Get the Gluu account.
      $gluu_account = $gluuClient->tryGetGluuAccount($user, $old_email, $field_amnet_id);
      $is_new = (!$gluu_account);
      if ($is_new) {
        // Create the New account on Gluu.
        $data = [
          'mail' => $new_email,
          'pass' => $pass,
          'username' => $username,
          'nickname' => $user->get('field_givenname')->getString(),
          'familyname' => $user->get('field_givenname')->getString(),
          'givenname' => $user->get('field_familyname')->getString(),
          'external_id' => $field_amnet_id,
        ];
        $result = $gluuClient->createUserFromPersonData($data);
      }
      else {
        // Make Sure that the user have an externalId setted.
        $externalId = $gluu_account->externalId ?? NULL;
        if (!empty($externalId)) {
          $gluu_account->userName = $externalId;
        }
        // Make sure of the External ID match with the AMNet ID.
        if (valid_email_address($externalId) && !empty($field_amnet_id)) {
          $gluu_account->externalId = $field_amnet_id;
          $gluu_account->userName = $field_amnet_id;
        }
        // Try to update first on Gluu IDP.
        $result = $gluuClient->changeLoginInfo($new_email, $pass, $gluu_account);
      }
      if (!$result) {
        $form_state->setErrorByName('mail', $this->t('It is not possible to update your information at this time, please try again later.'));
        // Stop Here.
        return;
      }
      $user_profile_manager = \Drupal::service('am_net_user_profile.manager');
      if ($email_changed) {
        // Un-lock User Sync to push email change to AM.net.
        $user_profile_manager->unlockUserSync($user);
      }
      else {
        // Temporarily lock the sync for not push pwd changes to AM.net.
        $user_profile_manager->lockUserSync($user);
      }
      // Send update notification by email.
      $user_profile_manager->setSendUpdateConfirmationEmail($user, TRUE);
      // Update the user locally on Drupal.
      $user->setEmail($new_email);
      $user->setUsername($username);
      $user->setPassword($pass);
      // Save the Changes.
      $user->save();
      if (!$email_changed) {
        // UnLock the sync for this Drupal Account.
        $user_profile_manager->unlockUserSync($user);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Your Login Information has been successfully changed!.'), 'status');
  }

}
