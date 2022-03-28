<?php

namespace Drupal\vscpa_content_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Handle additional content migrations for Articles and topics.
 */
class ContentMigrationForm extends FormBase {

  /**
   * The publication type Article.
   *
   * @var string
   */
  protected $publicationTypeArticle = '15312';

  /**
   * The publication type Topic.
   *
   * @var string
   */
  protected $publicationTypeTopic = '15311';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vscpa_content_migration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Web Experiences.
    $default_path = 'public://resources/web_experiences.csv';
    $form['web_experiences_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Web Experiences path'),
      '#default_value' => $default_path,
      '#description' => t('Please enter the full path to the CSV file.') . " <small>Default path: <mark>{$default_path}</mark></small>.",
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    // Properties.
    $default_path = 'public://resources/properties.csv';
    $form['properties_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Properties path'),
      '#default_value' => $default_path,
      '#description' => t('Please enter the full path to the CSV file.') . " <small>Default path: <mark>{$default_path}</mark></small>.",
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    // Publication Type.
    $form['publication_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Publication Type'),
      '#options' => [
        'all' => $this->t('All'),
        $this->publicationTypeArticle => $this->t('Article'),
        $this->publicationTypeTopic => $this->t('Topic'),
      ],
      '#default_value' => 'all',
      '#required' => TRUE,
    ];
    // Operation.
    $form['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#options' => [
        '-1' => $this->t('Select'),
        'migrate_web_experiences' => $this->t('Migrate Web Experiences'),
        'migrate_properties' => $this->t('Migrate Properties'),
        'populate_access_levels' => $this->t('Populate Access Levels'),
        'migrate_content_editor_files' => $this->t('Migrate Content Editor Files'),
      ],
      '#default_value' => '-1',
      '#required' => TRUE,
    ];
    // Limit.
    $form['limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Limit'),
      '#options' => [
        -1 => $this->t('All'),
        10 => $this->t('10'),
        30 => $this->t('30'),
        50 => $this->t('50'),
        100 => $this->t('100'),
        500 => $this->t('500'),
        1000 => $this->t('1000'),
      ],
      '#default_value' => -1,
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
      '#value' => $this->t('Run Migration'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
      '#submit' => [[$this, 'submitMigration']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $operation = $form_state->getValue('operation');
    if ($operation == '-1') {
      $form_state->setErrorByName('operation', t('Please select operation.'));
    }
    $web_experiences_path = $form_state->getValue('web_experiences_path');
    if (!file_exists($web_experiences_path)) {
      $form_state->setErrorByName('web_experiences_path', t('Web Experience CSV file does not exists.'));
    }
    $properties_path = $form_state->getValue('properties_path');
    if (!file_exists($properties_path)) {
      $form_state->setErrorByName('properties_path', t('Properties CSV file does not exists.'));
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
  public function submitMigration(array &$form, FormStateInterface $form_state) {
    $operation = $form_state->getValue('operation');
    $web_experiences_path = $form_state->getValue('web_experiences_path');
    $properties_path = $form_state->getValue('properties_path');
    $limit = $form_state->getValue('limit');
    $publication_type = $form_state->getValue('publication_type');
    $publication_types = ($publication_type == 'all') ? [$this->publicationTypeArticle, $this->publicationTypeTopic] : [$publication_type];
    $service_id = 'vscpa_content_migration.helper';
    /* @var $migrationHelper \Drupal\vscpa_content_migration\MigrationHelper */
    $migrationHelper = \Drupal::service($service_id);
    $title = '';
    $namespace = '\Drupal\vscpa_content_migration\Form\ContentMigrationForm::';
    $process_callback = $namespace;
    $finished_callback = $namespace;
    $operations = [];
    switch ($operation) {
      case 'migrate_web_experiences':
        $title = 'Migrating Web Experiences into publications...';
        $process_callback .= 'migrateWebExperiences';
        $finished_callback .= 'migrateWebExperiencesFinishedCallback';
        $operations = $migrationHelper->groupDataByPageId($web_experiences_path, $process_callback, 'web_experiences', $limit);
        break;

      case 'migrate_properties':
        $title = 'Migrating Properties into publications...';
        $process_callback .= 'migrateProperties';
        $finished_callback .= 'migratePropertiesFinishedCallback';
        $operations = $migrationHelper->groupDataByPageId($properties_path, $process_callback, 'properties', $limit);
        break;

      case 'migrate_content_editor_files':
        $title = 'Migrating Content Editor Files into publications...';
        $process_callback .= 'migrateContentEditorFiles';
        $finished_callback .= 'migrateContentEditorFilesFinishedCallback';
        $operations = $migrationHelper->getPublications($publication_types, $process_callback, $limit);
        break;

      case 'populate_access_levels':
        $title = 'Populate Access Levels on publications...';
        $process_callback .= 'populateAccessLevels';
        $finished_callback .= 'populateAccessLevelsFinishedCallback';
        $operations = $migrationHelper->getPublicationsWithoutAccessLevels($publication_types, $process_callback, $limit);
        break;

    }
    if (!empty($operations)) {
      $batch = [
        'title' => $title,
        'operations' => $operations,
        'finished' => $finished_callback,
      ];
      batch_set($batch);
    }
    else {
      $message = t('There are no pending records to be processed!.');
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
   * Migrate Web Experiences.
   *
   * @param string $page_id
   *   The old Page ID.
   * @param array $params
   *   The array of web experiences.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function migrateWebExperiences($page_id, array $params = [], array &$context = []) {
    $message = '<h3>Processing Page ID: <strong>' . $page_id . '</strong>...</h3>';
    if (!empty($page_id)) {
      $service_id = 'vscpa_content_migration.helper';
      \Drupal::service($service_id)->migrateWebExperiences($page_id, $params);
    }
    $context['message'] = $message;
    $results = $context['results'];
    $context['results'] += $results;
  }

  /**
   * Migrate Web Experiences Finished Callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function migrateWebExperiencesFinishedCallback($success, array $results = [], array $operations = []) {
    if ($success) {
      $message = t('The Web Experiences have been migrated successfully.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

  /**
   * Migrate Properties.
   *
   * @param string $page_id
   *   The old Page ID.
   * @param array $params
   *   The array of properties.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function migrateProperties($page_id, array $params = [], array &$context = []) {
    $message = '<h3>Processing Page ID: <strong>' . $page_id . '</strong>...</h3>';
    if (!empty($page_id)) {
      $service_id = 'vscpa_content_migration.helper';
      \Drupal::service($service_id)->migrateProperties($page_id, $params);
    }
    $context['message'] = $message;
    $results = $context['results'];
    $context['results'] += $results;
  }

  /**
   * Migrate Properties Finished Callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function migratePropertiesFinishedCallback($success, array $results = [], array $operations = []) {
    if ($success) {
      $message = t('The Properties have been migrated successfully.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

  /**
   * Migrate Content Editor Files.
   *
   * @param string $node_id
   *   The Publication Node ID.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function migrateContentEditorFiles($node_id, array &$context = []) {
    $message = '<h3>Processing Node ID: <strong>' . $node_id . '</strong>...</h3>';
    if (!empty($node_id)) {
      $service_id = 'vscpa_content_migration.helper';
      \Drupal::service($service_id)->migrateContentEditorFiles($node_id);
    }
    $context['message'] = $message;
    $results = $context['results'];
    $context['results'] += $results;
  }

  /**
   * Migrate Content Editor Files Finished Callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function migrateContentEditorFilesFinishedCallback($success, array $results = [], array $operations = []) {
    if ($success) {
      $message = t('The Content Editor Files have been migrated successfully.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

  /**
   * Populate Access Levels.
   *
   * @param string $node_id
   *   The Publication Node ID.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function populateAccessLevels($node_id, array &$context = []) {
    $message = '<h3>Processing Node ID: <strong>' . $node_id . '</strong>...</h3>';
    if (!empty($node_id)) {
      $service_id = 'vscpa_content_migration.helper';
      \Drupal::service($service_id)->populateAccessLevels($node_id);
    }
    $context['message'] = $message;
    $results = $context['results'];
    $context['results'] += $results;
  }

  /**
   * Populate Access Levels Finished Callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function populateAccessLevelsFinishedCallback($success, array $results = [], array $operations = []) {
    if ($success) {
      $message = t('The Access Levels have been populated successfully.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
