<?php

namespace Drupal\am_net_user_profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for fetch and sync single user profile record by ID.
 */
class FetchAndSyncSingleUserProfileForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_single_user_profile_fetch';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $nid = \Drupal::request()->query->get('nid');
    $form['names_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AM.net Name Record ID'),
      '#size' => 20,
      '#maxlength' => 128,
      '#description' => $this->t('Please provide a valid AM.net Name Record ID'),
      '#required' => TRUE,
      '#default_value' => $nid,
    ];
    $form['bypass_suitable_validation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bypass suitable validation?'),
      '#description' => $this->t('Check this box if you want sync not suitable Name record(Ex. Terminated Names who do not have the active the flag "Populate website user profile").'),
      '#default_value' => FALSE,
    ];
    $form['verbose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose?'),
      '#description' => $this->t('Provides additional details as to what the sync is doing'),
      '#default_value' => TRUE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_user_profiles',
      '#value' => $this->t('Fetch and Sync User Profile'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
      '#submit' => [[$this, 'submitSyncUserProfile']],
    ];

    $commands = [];
    $commands[] = '<br><small>1. Pull by email: <mark>drush user-profile:pull user@email.com</mark></small>';
    $commands[] = '<small>2. Pull by Name Record ID: <mark>drush user-profile:pull 12345</mark></small>';
    $commands[] = '<small>3. Push by email: <mark>drush user-profile:push user@email.com</mark></small>';
    $commands[] = '<small>4. Push by Drupal User ID: <mark>drush user-profile:push 12345</mark></small>';
    $form['description'] = [
      '#type' => 'item',
      '#markup' => 'If you need to synchronize a user via drush you can use the following commands:' . implode('<br>', $commands),
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
  public function submitSyncUserProfile(array &$form, FormStateInterface $form_state) {
    $names_id = $form_state->getValue('names_id');
    $verbose = $form_state->getValue('verbose');
    $bypass_suitable_validation = $form_state->getValue('bypass_suitable_validation');
    $validate = !($bypass_suitable_validation == 1);
    $type = 'status';
    if (!empty($names_id)) {
      $names_id = trim($names_id);
      // User Profile Manager.
      $service_id = 'am_net_user_profile.manager';
      $info = \Drupal::service($service_id)->syncUserProfile($names_id, NULL, $validate, TRUE, $gluu_validation = TRUE);
      // Process the result.
      $result = $info['result'] ?? NULL;
      unset($info['result']);
      switch ($result) {
        case SAVED_NEW:
          $message = t('The User profile @id have successfully Added.', ['@id' => $names_id]);
          break;

        case SAVED_UPDATED:
          $message = t('The User profile @id have successfully Updated.', ['@id' => $names_id]);
          break;

        default:
          $type = 'warning';
          $message = t('@id — ID provided is not a valid NamesID, does not exist on AM.net, or is not a person suitable for sync.', ['@id' => $names_id]);

      }
      if ($verbose && is_array($info)) {
        // Show Result.
        $this->printMessages($info);
      }
    }
    else {
      $message = t('Please provide a valid AM.net Name Record ID');
    }
    drupal_set_message($message, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit is optional due batch_set operations.
  }

  /**
   * Prints array messages.
   *
   * @param array $messages
   *   An associative array containing the message list.
   */
  public function printMessages(array $messages = []) {
    if (empty($messages) && !function_exists('ksm')) {
      return;
    }
    // Format Table.
    $table = $this->formatMessages($messages);
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('devel')) {
      // Print Table.
      ksm($table);
    }
  }

  /**
   * Format array messages.
   *
   * @param array $messages
   *   An associative array containing the message list.
   *
   * @return array
   *   The processed array of messages.
   */
  public function formatMessages(array $messages = []) {
    $table = [];
    foreach ($messages as $msg) {
      $label = $msg[0] ?? NULL;
      $values = $msg[1] ?? NULL;
      if (is_array($values)) {
        foreach ($values as $delta => $value) {
          $table[] = [
            'Field Name' => $label . ' - ' . $delta,
            'Field Value' => $value,
          ];
        }
      }
      else {
        $table[] = ['Field Name' => $label, 'Field Value' => $values];
      }
    }
    return $table;
  }

}
