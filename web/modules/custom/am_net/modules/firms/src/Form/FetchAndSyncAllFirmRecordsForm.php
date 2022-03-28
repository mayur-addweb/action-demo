<?php

namespace Drupal\am_net_firms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for fetch and sync all firm records.
 */
class FetchAndSyncAllFirmRecordsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_firms_firms';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['fetches_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Firm Codes - Fetches Options'),
      '#options' => [
        'all' => $this->t('All'),
        'since_date' => $this->t('Since given date'),
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
      '#description' => $this->t("Returns list of firm codes for NEW firm records created since the given date."),
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
      '#name' => 'sync_firms',
      '#value' => $this->t('Sync Firms'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
      '#submit' => [[$this, 'submitSyncFirms']],
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['update_firm_parents'] = [
      '#type' => 'submit',
      '#name' => 'update_firm_parents',
      '#value' => $this->t('Update Firm Parents'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
      '#submit' => [[$this, 'submitUpdateFirmParents']],
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
  public function submitSyncFirms(array &$form, FormStateInterface $form_state) {
    $fetches_option = $form_state->getValue('fetches_option');
    $since_date = $form_state->getValue('since_date');
    $service_id = 'am_net_firms.firm_manager';
    $firmManager = \Drupal::service($service_id);
    $firm_code_list = [];
    switch ($fetches_option) {
      case 'all':
        // Get all list of firm codes.
        $firm_code_list = $firmManager->getAllFirmCodes();
        break;

      case 'since_date':
        // Get all list of firm codes for NEW firm records created
        // since the given date from AM.net system.
        $firm_code_list = $firmManager->getAllFirmByDate($since_date);
        break;

    }
    $operations = [];
    // Loop over the firm codes.
    foreach ($firm_code_list as $key => $item) {
      $firmCode = NULL;
      $changeDate = NULL;
      if ($fetches_option == 'all') {
        $firmCode = isset($item['FirmCode']) ? $item['FirmCode'] : NULL;
      }
      else {
        $firmCode = isset($item['Firm']['FirmCode']) ? $item['Firm']['FirmCode'] : NULL;
        $changeDate = isset($item['ChangeDate']) ? $item['ChangeDate'] : NULL;
      }
      if (!is_null($firmCode)) {
        $operations[] = [
          '\Drupal\am_net_firms\Form\FetchAndSyncAllFirmRecordsForm::syncFirmRecord',
          [$firmCode, $changeDate],
        ];
      }
    }
    if (!empty($operations)) {
      $batch = [
        'title' => t('Syncing Firm Records...'),
        'operations' => $operations,
        'finished' => '\Drupal\am_net_firms\Form\FetchAndSyncAllFirmRecordsForm::syncFirmRecordFinishedCallback',
      ];
      batch_set($batch);
    }
    else {
      $message = t('There are no pending firm records to be processed!.');
      drupal_set_message($message, 'warning');
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitUpdateFirmParents(array &$form, FormStateInterface $form_state) {
    $service_id = 'am_net_firms.firm_manager';
    $firmManager = \Drupal::service($service_id);
    // 1. Reset Firm Weights.
    /* @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $term_storage->resetWeights($vid = 'firm');
    $tids = $firmManager->getFirmTermsList();
    $term_storage->deleteTermHierarchy($tids);

    // 2. get firm terms list with Branch Offices.
    $terms = $firmManager->getFirmTermsListWithBranchOffices();
    if (!empty($terms)) {
      $operations = [];
      foreach ($terms as $key => $term_id) {
        $operations[] = [
          '\Drupal\am_net_firms\Form\FetchAndSyncAllFirmRecordsForm::updateFirmParents',
          [$term_id],
        ];
      }
      $batch = [
        'title' => t('Updating the parents of the Firm Records...'),
        'operations' => $operations,
        'finished' => '\Drupal\am_net_firms\Form\FetchAndSyncAllFirmRecordsForm::updateFirmParentsFinishedCallback',
      ];
      batch_set($batch);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit is optional due batch_set operations.
  }

  /**
   * Sync Firm record.
   *
   * @param string $firm_code
   *   Required param, The Firm Code ID.
   * @param string $changeDate
   *   Optional param, The change date.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function syncFirmRecord($firm_code, $changeDate, array &$context = []) {
    $message = '<h3>Processing Firm: <strong>' . $firm_code . '</strong>...</h3>';
    if (!empty($firm_code)) {
      $service_id = 'am_net_firms.firm_manager';
      $result = \Drupal::service($service_id)->syncFirmRecord($firm_code, $changeDate);
    }
    $context['message'] = $message;
    $results = $context['results'];
    $context['results'] += $results;
  }

  /**
   * Update Firm Parents.
   *
   * @param int $term_id
   *   Required param, The term ID.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function updateFirmParents($term_id, array &$context = []) {
    $message = '<h3>Updating the parents of the Firm: <strong>' . $term_id . '</strong>...</h3>';
    if (!empty($term_id)) {
      $result = \Drupal::service('am_net_firms.firm_manager')->updateFirmParents($term_id);
    }
    $context['message'] = $message;
    $results = $context['results'];
    $context['results'] += $results;
  }

  /**
   * Sync Firm records Finished Callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function syncFirmRecordFinishedCallback($success, array $results = [], array $operations = []) {
    if ($success) {
      $message = t('The Firm Records have been synchronized successfully.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

  /**
   * Update firm parents Finished Callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function updateFirmParentsFinishedCallback($success, array $results = [], array $operations = []) {
    if ($success) {
      $message = t('The parents of the Firm Records has been updated successfully.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
