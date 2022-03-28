<?php

namespace Drupal\am_net_user_profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for fetch and sync all Person content records.
 */
class FetchAndSyncAllLegislativeContactsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_person_content_fetch';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['fetches_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Person Contact - Fetches Options'),
      '#options' => [
        'all' => $this->t('All'),
        'since_date' => $this->t('Since given date'),
        'existing_in_drupal' => $this->t('Existing in Drupal'),
      ],
      '#default_value' => 'all',
      '#required' => TRUE,
    ];

    $format = 'Y-m-d';
    $default_time = strtotime('-2 month');
    $default_value = date($format, $default_time);
    $select_by_date_condition = [
      'select[name="fetches_option"]' => ['value' => 'since_date'],
    ];
    $form['since_date'] = [
      '#title' => $this->t('Since Date'),
      '#type' => 'date',
      '#attributes' => [
        'type' => 'date',
        'min' => '-2 years',
        'max' => '+12 months',
      ],
      '#date_date_format' => $format,
      '#description' => $this->t("Returns list of Person Contacts for NEW records created since the given date."),
      '#default_value' => $default_value,
      '#states' => [
        'visible' => $select_by_date_condition,
        'required' => $select_by_date_condition,
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_person_contents',
      '#value' => $this->t('Fetch and Sync Person Contacts'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
      '#submit' => [[$this, 'submitSyncPersonContacts']],
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
  public function submitSyncPersonContacts(array &$form, FormStateInterface $form_state) {
    $limit = -1;
    $namespace = get_called_class();
    $fetches_option = $form_state->getValue('fetches_option');
    $since_date = $form_state->getValue('since_date');
    $service_id = 'am_net_user_profile.manager';
    $personContactManager = \Drupal::service($service_id);
    $user_id_list = [];
    switch ($fetches_option) {
      case 'all':
        // Get all list of Person Contacts.
        $user_id_list = $personContactManager->getAllUserProfiles();
        break;

      case 'since_date':
        // Get all list of Person Contacts for NEW records created
        // since the given date from AM.net system.
        $user_id_list = $personContactManager->getAllUserProfilesByDate($since_date);
        break;

      case 'existing_in_drupal':
        // Get all list of Person Contacts.
        $user_id_list = $personContactManager->getAllDrupalContentPersons();
        break;

    }
    $operations = [];
    if (!empty($user_id_list)) {
      // Loop over the Person Contact list.
      foreach ($user_id_list as $key => $item) {
        $namesId = NULL;
        $changeDate = NULL;
        if (($fetches_option == 'all') || ($fetches_option == 'existing_in_drupal')) {
          $namesId = isset($item['NamesID']) ? $item['NamesID'] : NULL;
        }
        else {
          $namesId = isset($item['Person']['NamesID']) ? $item['Person']['NamesID'] : NULL;
          $changeDate = isset($item['ChangeDate']) ? $item['ChangeDate'] : NULL;
        }
        if (!is_null($namesId)) {
          $operations[] = [
            $namespace . '::syncPersonContact',
            [$namesId, $changeDate],
          ];
        }
      }
    }
    if (!empty($operations)) {
      if ($limit > 0) {
        $operations = array_slice($operations, 0, $limit);
      }
      $batch = [
        'title' => t('Syncing Person Contacts...'),
        'operations' => $operations,
        'finished' => $namespace . '::syncFinishedCallback',
      ];
      batch_set($batch);
    }
    else {
      $message = t('There are no pending Person Contact records to be processed!.');
      drupal_set_message($message, 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit is optional due batch_set operations.
  }

  /**
   * Sync Person Contact record.
   *
   * @param string $names_id
   *   Required param, The Person Contact Code ID.
   * @param string $changeDate
   *   Optional param, The change date.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function syncPersonContact($names_id, $changeDate, array &$context = []) {
    $message = '<h3>Processing Person Contact: <strong>#' . $names_id . '</strong>...</h3>';
    if (!empty($names_id)) {
      $names_id = trim($names_id);
      try {
        $service_id = 'am_net_user_profile.legislative_contacts_manager';
        $result = \Drupal::service($service_id)->pullContentPerson($names_id, $verbose = FALSE);
      }
      catch (\Exception $e) {
        $message = "Sync exception: names_id {$names_id} - \n" . $e->getMessage();
        \Drupal::logger('am_net_user_profile')->debug($message);
      }
    }
    $context['message'] = $message;
    $results = $context['results'];
    $context['results'] += $results;
  }

  /**
   * Sync Person Contact records Finished Callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function syncPersonContactFinishedCallback($success, array $results = [], array $operations = []) {
    if ($success) {
      $message = t('The Person Contacts have successfully synchronized.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
