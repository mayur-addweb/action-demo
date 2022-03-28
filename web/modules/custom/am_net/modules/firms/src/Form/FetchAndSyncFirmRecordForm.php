<?php

namespace Drupal\am_net_firms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for fetch and sync single firm record by Code.
 */
class FetchAndSyncFirmRecordForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_single_firm_fetch';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $nid = \Drupal::request()->query->get('nid');
    $form['names_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AM.net Firm Code'),
      '#size' => 20,
      '#maxlength' => 128,
      '#description' => $this->t('Please provide a valid AM.net Firm Code'),
      '#required' => TRUE,
      '#default_value' => $nid,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_firms',
      '#value' => $this->t('Fetch and Sync Firm Record'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
      '#submit' => [[$this, 'submitSyncFirmRecord']],
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitSyncFirmRecord(array &$form, FormStateInterface $form_state) {
    $names_id = $form_state->getValue('names_id');
    $type = 'status';
    if (!empty($names_id)) {
      $names_id = trim($names_id);
      // Firm Record Manager.
      $result = \Drupal::service('am_net_firms.firm_manager')->syncFirmRecord($names_id);
      // Process the result.
      switch ($result) {
        case SAVED_NEW:
          $message = t('The Firm Record @id have successfully Added.', ['@id' => $names_id]);
          break;

        case SAVED_UPDATED:
          $message = t('The Firm Record @id have successfully Updated.', ['@id' => $names_id]);
          break;

        default:
          $type = 'warning';
          $message = t('@id — ID provided is not a valid Form Code, does not exist on AM.net.', ['@id' => $names_id]);

      }
    }
    else {
      $message = t('Please provide a valid AM.net Firm Code');
    }
    drupal_set_message($message, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit is optional due batch_set operations.
  }

}
