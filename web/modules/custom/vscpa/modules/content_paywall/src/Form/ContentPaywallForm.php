<?php

namespace Drupal\content_paywall\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Handle Content Paywall Setting.
 */
class ContentPaywallForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_paywall.setting';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Restricted-content message.
    $form['message'] = [
      '#type' => 'text_format',
      '#title' => t('Restricted-content message'),
      '#format' => 'full_html',
      '#default_value' => \Drupal::state()->get('content_paywall.restricted_content_message', ''),
      '#required' => TRUE,
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
    $message = $form_state->getValue('message');
    $message = isset($message['value']) ? $message['value'] : '';
    \Drupal::state()->set('content_paywall.restricted_content_message', $message);
    drupal_set_message(t('The changes have been saved!.'));
  }

}
