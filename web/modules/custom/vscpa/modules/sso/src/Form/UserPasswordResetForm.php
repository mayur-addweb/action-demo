<?php

namespace Drupal\vscpa_sso\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Form controller for the user password forms.
 *
 * @internal
 */
class UserPasswordResetForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vscpa_sso_user_pass_reset';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   User requesting reset.
   * @param string $expiration_date
   *   Formatted expiration date for the login link, or NULL if the link does
   *   not expire.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL, $expiration_date = NULL, $timestamp = NULL, $hash = NULL) {
    if (!$user) {
      return [];
    }
    if (!$hash) {
      return [];
    }
    $message = $this->t('<p>Please change your password:</p>');
    $form['message'] = ['#markup' => $message];
    if ($expiration_date) {
      $form['#title'] = $this->t('Reset password');
    }
    else {
      // No expiration for first time login.
      $form['#title'] = $this->t('Set password');
    }
    $form['#attributes']['class'][] = 'user-form';
    $form['#attributes']['class'][] = 'user-update-profile-website-account-form';

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

    $form['timestamp'] = [
      '#type' => 'hidden',
      '#value' => $timestamp,
    ];

    $form['hash'] = [
      '#type' => 'hidden',
      '#value' => $hash,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Set password'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'vscpa_bootstrap/passwords';

    $form['#prefix'] = '<div class="row"><div class="col-md-9 margin-bottom-20">';
    $form['#suffix'] = '</div></div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $pass = $form_state->getValue('pass');
    if (empty($pass)) {
      $form_state->setErrorByName('pass', $this->t('Please enter and confirm your new password below.'));
    }
    // Check Target UID.
    $entity_id = trim($form_state->getValue('entity_id'));
    if (empty($entity_id)) {
      $form_state->setErrorByName('pass', $this->t('You are not authorized to do this change.'));
      // Stop Here.
      return;
    }
    $user = User::load($entity_id);
    if (!$user) {
      $form_state->setErrorByName('pass', $this->t('It is not possible to update your information at this time, please try again later.'));
      // Stop Here.
      return;
    }
    $gluuClient = \Drupal::service('gluu.client');
    $mail = $user->getEmail();
    $field_amnet_id = $user->get('field_amnet_id')->getString();
    $field_amnet_id = trim($field_amnet_id);
    $gluu_account = $gluuClient->tryGetGluuAccount($user, $mail, $field_amnet_id);
    if (!$gluu_account) {
      $form_state->setErrorByName('pass', $this->t('It is not possible to update your information at this time, please try again later.'));
      // Stop Here.
      return;
    }
    $pass = trim($form_state->getValue('pass'));
    // Try to update first on Gluu IDP.
    $result = $gluuClient->changeLoginInfo(NULL, $pass, $gluu_account);
    if (!$result) {
      $form_state->setErrorByName('pass', $this->t('It is not possible to update your information at this time, please try again later.'));
      // Stop Here.
      return;
    }
    // Un-lock User Sync.
    $user_profile_manager = \Drupal::service('am_net_user_profile.manager');
    // Temporarily lock the sync for not push pwd changes into AM.net.
    $user_profile_manager->lockUserSync($user);
    // Send update notification by email.
    $user_profile_manager->setSendUpdateConfirmationEmail($user, TRUE);
    // Update the user locally on Drupal.
    $user->setPassword($pass);
    // Save the Changes.
    $user->save();
    // UnLock the sync for this Drupal Account.
    $user_profile_manager->unlockUserSync($user);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $request = $this->getRequest();
    $session = $request->getSession();
    // As soon as the session variables are used they are removed to prevent the
    // hash and timestamp from being leaked unexpectedly. This could occur if
    // the user does not click on the log in button on the form.
    $session->remove('pass_reset_timeout');
    $session->remove('pass_reset_hash');
    // Redirect to Login.
    $url = Url::fromRoute('simplesamlphp_auth.saml_login');
    $form_state->setRedirectUrl($url);
  }

}
