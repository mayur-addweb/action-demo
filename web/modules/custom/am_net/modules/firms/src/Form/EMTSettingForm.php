<?php

namespace Drupal\am_net_firms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Handle Employee Management Tool Setting.
 */
class EMTSettingForm extends FormBase {

  /**
   * Setting variable key.
   */
  const RENEWAL_INFORMATION_MESSAGE = 'am_net_firms.employee_management_tool.setting.renewal_information_message';

  /**
   * Setting variable key for: Manage my firm summary.
   */
  const MANAGE_MY_FIRM_SUMMARY = 'am_net_firms.employee_management_tool.setting.manage_my_firm_summary';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_firms.employee_management_tool.setting';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    // Employee Management Tool - summary.
    $form['emt_summary'] = [
      '#type' => 'text_format',
      '#title' => t('Employee Management Tool'),
      '#format' => 'full_html',
      '#default_value' => $state->get(self::MANAGE_MY_FIRM_SUMMARY, ''),
      '#required' => FALSE,
    ];
    // Renewal Information message.
    $form['message'] = [
      '#type' => 'text_format',
      '#title' => t('Renewal Information message'),
      '#format' => 'full_html',
      '#default_value' => $state->get(self::RENEWAL_INFORMATION_MESSAGE, ''),
      '#required' => FALSE,
    ];
    // Actions.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_firms',
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
    // Save EMT summary.
    $message = $form_state->getValue('emt_summary');
    $message = isset($message['value']) ? $message['value'] : '';
    $state->set(self::MANAGE_MY_FIRM_SUMMARY, $message);
    // Save  Renewal Information message.
    $message = $form_state->getValue('message');
    $message = isset($message['value']) ? $message['value'] : '';
    $state->set(self::RENEWAL_INFORMATION_MESSAGE, $message);
    drupal_set_message(t('The changes have been saved!.'));
  }

}
