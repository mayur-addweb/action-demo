<?php

namespace Drupal\vscpa_sso\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\am_net_triggers\QueueItem\NameSyncQueueItem;
use Drupal\Core\Form\FormBase;

/**
 * Form for fetch and sync all accounts between AM.net and Gluu.
 */
class FetchAndSyncAllAccountsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vscpa_sso_sync_accounts';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['fetches_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Accounts - Fetches Options'),
      '#options' => [
        'all' => $this->t('All'),
        'since_date' => $this->t('Since given date'),
        'drupal_accounts' => $this->t('Only Drupal Accounts'),
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
      '#description' => $this->t("Returns list of AM.net accounts for NEW records created since the given date."),
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
      '#name' => 'sync_user_profiles',
      '#value' => $this->t('Fetch and Sync Accounts'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
      '#submit' => [[$this, 'submitaddAccountToQueues']],
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
  public function submitaddAccountToQueues(array &$form, FormStateInterface $form_state) {
    $limit = -1;
    $namespace = get_called_class();
    $fetches_option = $form_state->getValue('fetches_option');
    $since_date = $form_state->getValue('since_date');
    $service_id = 'am_net_user_profile.manager';
    $userProfileManager = \Drupal::service($service_id);
    $user_id_list = [];
    switch ($fetches_option) {
      case 'all':
        // Get all list of user profiles.
        $user_id_list = $userProfileManager->getAllUserProfiles();
        break;

      case 'since_date':
        // Get all list of user profiles for NEW records created
        // since the given date from AM.net system.
        $user_id_list = $userProfileManager->getAllUserProfilesByDate($since_date);
        break;

      case 'drupal_accounts':
        // Get all list of user profiles for the current Drupal
        // Acoounts.
        $user_id_list = $userProfileManager->getAllDrupalNamesIds();
        break;

    }
    $operations = [];
    if (!empty($user_id_list)) {
      // Loop over the user profile list.
      foreach ($user_id_list as $key => $item) {
        $namesId = NULL;
        $changeDate = NULL;
        if (($fetches_option == 'all') || ($fetches_option == 'drupal_accounts')) {
          $namesId = isset($item['NamesID']) ? $item['NamesID'] : NULL;
        }
        else {
          $namesId = isset($item['Person']['NamesID']) ? $item['Person']['NamesID'] : NULL;
          $changeDate = isset($item['ChangeDate']) ? $item['ChangeDate'] : NULL;
        }
        if (!is_null($namesId)) {
          $operations[] = [
            $namespace . '::addAccountToQueue',
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
        'title' => t('Adding Accounts to the Queue...'),
        'operations' => $operations,
        'finished' => $namespace . '::syncFinishedCallback',
      ];
      batch_set($batch);
    }
    else {
      $message = t('There are no pending Accounts records to be processed!.');
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
   * Sync User Profile record.
   *
   * @param string $names_id
   *   Required param, The User Profile Code ID.
   * @param string $changeDate
   *   Optional param, The change date.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function addAccountToQueue($names_id = '', $changeDate = '', array &$context = []) {
    if (empty($names_id)) {
      return;
    }
    $message = '<h3>Adding Account: <strong>#' . $names_id . '</strong> to Queue...</h3>';
    $names_id = trim($names_id);
    try {
      $item = new NameSyncQueueItem($names_id, $changeDate);
      \Drupal::queue('vscpa_sso_sync_accounts')->createItem($item);
    }
    catch (\Exception $e) {
      $message = "Sync exception: names_id {$names_id} - \n" . $e->getMessage();
      \Drupal::logger('vscpa_sso')->debug($message);
    }
    $context['message'] = $message;
    $results = $context['results'];
    $context['results'] += $results;
  }

  /**
   * Sync User Profile records Finished Callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function addAccountToQueueFinishedCallback($success, array $results = [], array $operations = []) {
    if ($success) {
      $message = t('The Accounts have successfully added to the Queue.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
