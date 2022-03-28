<?php

namespace Drupal\am_net_cpe\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\am_net_cpe\EventHelper;
use Drupal\Core\Form\FormBase;

/**
 * AM.net Multi-event acronym exclusions.
 */
class MultiEventConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_cpe_multi_event_settings';
  }

  /**
   * Detects changes in a given array.
   *
   * @param array $old_values
   *   The array with the old values.
   * @param array $new_values
   *   The array with the new values.
   *
   * @return array
   *   The array with the detailed difference.
   */
  public function detectsChanges(array $old_values = [], array $new_values = []) {
    $diff = [
      'has_changes' => FALSE,
      'added' => [],
      'deleted' => [],
    ];
    if (empty($old_values) && empty($new_values)) {
      return $diff;
    }
    // Get the acronyms.
    $old_values = array_column($old_values, 'acronym');
    $new_values = array_column($new_values, 'acronym');
    // Sort the acronyms.
    sort($old_values);
    sort($new_values);
    // Computer the diff.
    $diff['has_changes'] = !($old_values == $new_values);
    $diff['added'] = array_diff($new_values, $old_values);
    $diff['deleted'] = array_diff($old_values, $new_values);
    $diff['acronyms_changed'] = array_merge($diff['added'], $diff['deleted']);
    return $diff;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $default_value = \Drupal::state()->get('am_net_cpe.multi_event.excluded.acronyms');
    $form['excluded'] = [
      '#title' => $this->t('Multi-event acronym exclusions'),
      '#description' => $this->t('Please provide above the Acronyms that should be excluded from the multi-event format.'),
      '#type' => 'webform_multiple',
      '#required' => TRUE,
      '#add_more' => FALSE,
      '#add_more_items' => FALSE,
      '#attributes' => [
        'class' => [
          'acronyms-table',
        ],
      ],
      '#header' => [
        $this->t('Excluded Acronyms'),
      ],
      '#element' => [
        'acronym' => [
          '#type' => 'textfield',
          '#title' => $this->t('Acronym'),
          '#required' => TRUE,
        ],
      ],
      '#default_value' => $default_value,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
    ];
    $form['#attached']['library'][] = 'am_net_cpe/multi_event_config_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    $store_key = 'am_net_cpe.multi_event.excluded.acronyms';
    $old_values = $state->get($store_key);
    $new_values = $form_state->getValue(['excluded']);
    $diff = $this->detectsChanges($old_values, $new_values);
    // Save the new values.
    $state->set($store_key, $new_values);
    // Check if any changes was made.
    if (!$diff['has_changes']) {
      // Show a friendly confirmation message.
      $this->messenger()->addStatus('Changes successfully saved.');
      return;
    }
    // Get the changed acronyms.
    $acronyms = $diff['acronyms_changed'];
    // We need to re-sync all the event involved in this exclusion changes.
    $ids = EventHelper::getProductIdByAcronyms($acronyms);
    if (empty($ids)) {
      // Show a friendly confirmation message.
      $this->messenger()->addStatus('Changes successfully saved.');
      return;
    }
    // Define Sync callbacks.
    $namespace = '\Drupal\am_net_cpe\Form\MultiEventConfigForm';
    $sync_callback = "{$namespace}::applyChangesOnMultiEvents";
    $sync_finished_callback = "{$namespace}::syncFinishedCallback";
    // Build batch operations.
    $operations = [];
    // Loop over the record_ids and add them to the operation batch.
    foreach ($ids as $entity_id) {
      $operations[] = [
        $sync_callback,
        [$entity_id],
      ];
    }
    // Add the event in questions to the batch sync.
    $batch = [
      'title' => $this->t('Applying changes on Multi-events...'),
      'operations' => $operations,
      'finished' => $sync_finished_callback,
    ];
    batch_set($batch);
  }

  /**
   * Apply Changes On Multi-Events.
   *
   * @param string $product_id
   *   The product ID.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function applyChangesOnMultiEvents($product_id = NULL, array &$context = []) {
    if (empty($product_id)) {
      return;
    }
    $ids = EventHelper::getEventAmNetIds($product_id);
    if (!$ids) {
      return;
    }
    $event_code = $ids['code'] ?? NULL;
    $event_year = $ids['year'] ?? NULL;
    if (empty($event_code) || empty($event_year)) {
      return;
    }
    $message = '<h3>Applying changes to event: ' . $event_year . ' / ' . $event_code . ' - Drupal Product(' . $product_id . ')...</h3>';
    $context['message'] = $message;
    // Clean the event code.
    $event_code = trim($event_code);
    $event_year = trim($event_year);
    // Fetch Changes from AM.net.
    try {
      /** @var \Drupal\am_net_cpe\CpeProductManagerInterface $product_manager */
      $product_manager = \Drupal::service('am_net_cpe.product_manager');
      $event = $product_manager->syncAmNetCpeEventProduct($event_code, $event_year);
      $context['results']['success'][] = [
        'code' => $event_code,
        'year' => $event_year,
        'link' => $event->toUrl()->toString(),
        'title' => $event->label(),
      ];
    }
    catch (\Exception $e) {
      $context['results']['exceptions'][] = [
        'code' => $event_code,
        'year' => $event_year,
        'message' => $e->getMessage(),
      ];
      \Drupal::logger('am_net_cpe')->debug($e->getMessage());
    }
  }

  /**
   * Sync AM.net CPE events finished callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function syncFinishedCallback($success, array $results = [], array $operations = []) {
    $messenger = \Drupal::messenger();
    if (!empty($results['exceptions'])) {
      $messenger->addStatus(t('Changes saved successfully'));
      foreach ($results['exceptions'] as $excluded) {
        $t_arg = [
          ':message' => $excluded['message'],
          ':year' => $excluded['year'],
          ':code' => $excluded['code'],
        ];
        $warning = t('Could not sync :year/:code (:message)', $t_arg);
        $messenger->addWarning($warning);
      }
    }
    else {
      $messenger->addStatus(t('Changes saved successfully & Multi-Event exclusion changes applied.'));
    }
  }

}
