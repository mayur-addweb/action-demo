<?php

namespace Drupal\am_net_cpe\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\am_net_cpe\CpeProductManagerInterface;
use Drupal\am_net\AmNetRecordNotFoundException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Form for syncing CPE events from AM.net.
 */
class SyncCpeEventsFromAmNetForm extends FormBase {

  /**
   * The AM.net CPE product manager.
   *
   * @var \Drupal\am_net_cpe\CpeProductManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_cpe_events_sync_form';
  }

  /**
   * Constructs a new SyncEventsFromAmNetForm.
   *
   * @param \Drupal\am_net_cpe\CpeProductManagerInterface $product_manager
   *   The AM.net CPE product manager.
   */
  public function __construct(CpeProductManagerInterface $product_manager) {
    $this->manager = $product_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_cpe.product_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['sync_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Sync range'),
      '#options' => [
        'all' => $this->t('All'),
        'id' => $this->t('By event id'),
        'date_range' => $this->t('Since given date'),
      ],
      '#default_value' => 'id',
      '#required' => TRUE,
    ];

    $format = 'Y-m-d';
    $default_time = strtotime('-2 month');
    $default_value = date($format, $default_time);
    $select_by_date_condition = [
      'select[name="sync_option"]' => ['value' => 'date_range'],
    ];
    $form['from_date'] = [
      '#title' => $this->t('Since Date'),
      '#type' => 'date',
      '#attributes' => [
        'type' => 'date',
        'min' => '-2 years',
        'max' => '+12 months',
      ],
      '#date_date_format' => $format,
      '#description' => $this->t("Returns list of events for NEW events created since the given date."),
      '#default_value' => $default_value,
      '#states' => [
        'visible' => $select_by_date_condition,
        'required' => $select_by_date_condition,
      ],
    ];
    $select_by_id_condition = [
      'select[name="sync_option"]' => ['value' => 'id'],
    ];
    $form['event_id'] = [
      '#type' => 'amnet_event_id',
      '#title' => $this->t('Event ID'),
      '#description' => $this->t('Enter a text event code and a 2-digit year.'),
      '#default_value' => ['code' => '4-131', 'year' => '19'],
      '#states' => [
        'visible' => $select_by_id_condition,
        'required' => $select_by_id_condition,
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_events',
      '#value' => $this->t('Fetch and sync AM.net CPE events'),
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sync_option = $form_state->getValue('sync_option');
    $date_range = $form_state->getValue('from_date');
    $id = $form_state->getValue('event_id');
    switch ($sync_option) {
      case 'id':
        $events = [['EventCode' => $id['code'], 'EventYear' => $id['year']]];
        break;

      case 'date_range':
        try {
          $events = $this->manager->getAmNetEvents($date_range);
        }
        catch (AmNetRecordNotFoundException $e) {
          $this->logger('am_net_cpe')->error($e->getMessage());
        }
        break;

      case 'all':
        try {
          $events = $this->manager->getAmNetEvents();
        }
        catch (AmNetRecordNotFoundException $e) {
          $this->logger('am_net_cpe')->error($e->getMessage());
        }
        break;
    }
    $operations = [];
    if (!empty($events)) {
      foreach ($events as $key => $item) {
        $operations[] = [
          [$this, 'syncAmNetCpeEventProduct'],
          [$item['EventCode'], $item['EventYear']],
        ];
      }
    }
    if (!empty($operations)) {
      $batch = [
        'title' => t('Syncing CPE events from AM.net...'),
        'operations' => $operations,
        'finished' => [$this, 'syncFinishedCallback'],
      ];
      batch_set($batch);
    }
    else {
      $message = t('There are no pending CPE events to be processed!.');
      drupal_set_message($message, 'warning');
    }
  }

  /**
   * Syncs a CPE event product from AM.net.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year (two digits).
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function syncAmNetCpeEventProduct($event_code, $event_year, array &$context = []) {
    $message = '<h3>Processing Event: ' . $event_year . ' / ' . $event_code . '...</h3>';
    if (!empty($event_code)) {
      $event_code = trim($event_code);
      $event_year = trim($event_year);
      try {
        /** @var \Drupal\am_net_cpe\CpeProductManagerInterface $product_manager */
        $product_manager = \Drupal::service('am_net_cpe.product_manager');
        $event = $product_manager->syncAmNetCpeEventProduct($event_code, $event_year);
        if (!is_null($event)) {
          $context['results']['success'][] = [
            'code' => $event_code,
            'year' => $event_year,
            'link' => $event->toUrl()->toString(),
            'title' => $event->label(),
          ];
          // Save the ID on the state.
          $state = \Drupal::state();
          $key = "amnet.synchronized.events";
          $values = $state->get($key, []);
          $values[] = $event->id();
          $state->set($key, $values);
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('am_net_cpe')->debug($e->getMessage());
        $context['results']['excluded'][] = [
          'code' => $event_code,
          'year' => $event_year,
          'message' => $e->getMessage(),
        ];
      }
    }
    $context['message'] = $message;
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
    if (!empty($results['success'])) {
      foreach ($results['success'] as $succeeded) {
        drupal_set_message(t('Successfully synced <a href="@link">:title (:year/:code)</a>', [
          ':year' => $succeeded['year'],
          ':code' => $succeeded['code'],
          ':title' => $succeeded['title'],
          '@link' => $succeeded['link'],
        ]));
      }
    }

    if (!empty($results['excluded'])) {
      foreach ($results['excluded'] as $excluded) {
        drupal_set_message(t('The event :code/:year could not be synced - Syncing error: (:message)', [
          ':message' => $excluded['message'],
          ':year' => $excluded['year'],
          ':code' => $excluded['code'],
        ]), 'warning');
      }
    }
  }

}
