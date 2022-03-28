<?php

namespace Drupal\am_net_user_profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Handle User Profile Setting.
 */
class UserProfileSettingForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_user_profile.setting';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    $key = 'am_net_user_profile_setting_send_become_firm_admin_email_notification';
    $form[$key] = [
      '#type' => 'checkbox',
      '#title' => 'Send email notification related to requests on become firm admin?.',
      '#description' => 'Send email notification - become firm admin.',
      '#default_value' => $state->get($key),
    ];
    $key = 'am_net_user_profile_setting_become_firm_admin_email_to';
    $form[$key] = [
      '#type' => 'email',
      '#title' => t('Become a firm admin - Email To address'),
      '#default_value' => $state->get($key, 'membership@vscpa.com'),
      '#required' => FALSE,
    ];
    // Actions.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    // Send become firm admin email notification.
    $keys = [
      'am_net_user_profile_setting_send_become_firm_admin_email_notification',
      'am_net_user_profile_setting_become_firm_admin_email_to',
    ];
    foreach ($keys as $delta => $key) {
      $val = $form_state->getValue($key);
      $state->set($key, $val);
    }
    // Show friendly message.
    drupal_set_message(t('The changes have been saved!.'));
  }

}
