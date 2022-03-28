<?php

namespace Drupal\am_net_cpe\Form;

use Drupal\am_net_cpe\CpeRegistrationManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for syncing CPE registrations from AM.net.
 */
class SyncCpeRegistrationsFromAmNetForm extends FormBase {

  /**
   * The AM.net CPE registration manager.
   *
   * @var \Drupal\am_net_cpe\CpeRegistrationManagerInterface
   */
  protected $registrationManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_cpe_registrations_sync_form';
  }

  /**
   * Constructs a new SyncCpeRegistrationsFromAmNetForm.
   *
   * @param \Drupal\am_net_cpe\CpeRegistrationManagerInterface $registration_manager
   *   The AM.net CPE registration manager.
   */
  public function __construct(CpeRegistrationManagerInterface $registration_manager) {
    $this->registrationManager = $registration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_cpe.registration_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo Support CPE Self-study (AM.net product purchases).
    $form['sync_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Sync option'),
      '#options' => [
        'member' => $this->t('By member id'),
        'event' => $this->t('By event id'),
        'member_event' => $this->t('By member id and event id'),
        'member_date' => $this->t('By member id since date'),
      ],
      '#default_value' => 'member',
      '#required' => TRUE,
    ];

    $format = 'Y-m-d';
    $default_time = strtotime('-2 month');
    $default_value = date($format, $default_time);
    $select_by_member_id_condition = [
      'select[name="sync_option"]' => [
        ['value' => 'member'],
        ['value' => 'member_event'],
        ['value' => 'member_date'],
      ],
    ];
    $form['member'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Member ID'),
      '#description' => $this->t('Enter a member ID number.'),
      '#states' => [
        'visible' => $select_by_member_id_condition,
        'required' => $select_by_member_id_condition,
      ],
    ];
    $select_by_event_id_condition = [
      'select[name="sync_option"]' => [
        ['value' => 'event'],
        ['value' => 'member_event'],
      ],
    ];
    $form['event'] = [
      '#type' => 'amnet_event_id',
      '#title' => $this->t('Event ID'),
      '#description' => $this->t('Enter an event ID number.'),
      '#states' => [
        'visible' => $select_by_event_id_condition,
        'required' => $select_by_event_id_condition,
      ],
    ];
    $select_by_member_since_date_condition = [
      'select[name="sync_option"]' => ['value' => 'member_date'],
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
      '#description' => $this->t("Syncs event registration orders by member for orders created since the given date."),
      '#default_value' => $default_value,
      '#states' => [
        'visible' => $select_by_member_since_date_condition,
        'required' => $select_by_member_since_date_condition,
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_registrations',
      '#value' => $this->t('Fetch and sync AM.net registrations'),
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
    $limit = 0;
    $sync_option = $form_state->getValue('sync_option');
    $since_date = $form_state->getValue('from_date');
    $event = $form_state->getValue('event');
    $member = $form_state->getValue('member');
    switch ($sync_option) {
      case 'member':
        $items = $this->registrationManager->getAmNetEventRegistrations($member);
        break;

      case 'event':
        $items = $this->registrationManager->getAmNetEventRegistrations(NULL, $event['year'], $event['code']);
        break;

      case 'member_event':
        // Only one item is returned from this call.
        $items = [
          $this->registrationManager->getAmNetEventRegistrations($member, $event['year'], $event['code']),
        ];
        break;

      case 'member_date':
        $items = $this->registrationManager->getAmNetEventRegistrations($member, NULL, NULL, $since_date);
        break;
    }
    $operations = [];
    if (!empty($items)) {
      foreach ($items as $key => $item) {
        $operations[] = [
          [$this, 'syncAmNetCpeEventRegistration'],
          [$item],
        ];
      }
    }
    if (!empty($operations)) {
      if ($limit > 0) {
        $operations = array_slice($operations, 0, $limit);
      }
      $batch = [
        'title' => t('Syncing registrations from AM.net...'),
        'operations' => $operations,
        'finished' => [$this, 'syncFinishedCallback'],
      ];
      batch_set($batch);
    }
    else {
      $message = t('There are no registrations to be processed.');
      drupal_set_message($message, 'warning');
    }
  }

  /**
   * Syncs an Event registration from AM.net to Drupal.
   *
   * @param array $record
   *   The AM.net event registration record.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function syncAmNetCpeEventRegistration(array $record, array &$context = []) {
    $event_year = $record['EventYear'];
    $event_code = $record['EventCode'];
    $member_id = $record['NamesId'];
    $message = '<h3>Processing Registration for ' . $member_id . ': ' . $event_year . ' / ' . $event_code . '...</h3>';
    if (!empty($event_code)) {
      $event_code = trim($event_code);
      $event_year = trim($event_year);
      try {
        /** @var \Drupal\am_net_cpe\CpeRegistrationManagerInterface $manager */
        $manager = \Drupal::service('am_net_cpe.registration_manager');
        $registration = $manager->syncAmNetCpeEventRegistration($record);
        $context['results']['success'][] = [
          'member' => $member_id,
          'code' => $event_code,
          'year' => $event_year,
          'link' => $registration->toUrl()->toString(),
          'title' => $registration->label(),
        ];
      }
      catch (\Exception $e) {
        \Drupal::logger('am_net_cpe')->debug($e->getMessage());
      }
    }
    $context['message'] = $message;
  }

  /**
   * Sync AM.net Registrations finished callback.
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
        drupal_set_message(t('Successfully synced <a href="@link">:title for :member (:year/:code)</a>', [
          ':member' => $succeeded['member'],
          ':year' => $succeeded['year'],
          ':code' => $succeeded['code'],
          ':title' => $succeeded['title'],
          '@link' => $succeeded['link'],
        ]));
      }
    }
  }

}
