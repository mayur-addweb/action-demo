<?php

namespace Drupal\am_net_cpe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\am_net\AmNetRecordExcludedException;
use Drupal\am_net_cpe\CpeProductManagerInterface;
use Drupal\am_net_triggers\QueueItem\EventSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\ProductSyncQueueItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for fetch Changes on CPE events from AM.net.
 */
class FetchChangesOnCpeEventsFromAmNetForm extends FormBase {

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
    return 'am_net_cpe_update_drupal_events_form';
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
    // Add Sync description.
    $description = [];
    $events_count = $this->getNumberOfCpeEvents();
    $description[] = t('<br><small>1. Number of AM.net <strong>CPE events</strong> on Drupal: <mark>#@events</mark></small>', ['@events' => $events_count]);
    $cpe_self_study_products_count = $this->getNumberOfCpeSelfStudyProducts();
    $description[] = t('<small>2. Number of <strong>Self-study CPE products</strong> on Drupal: <mark>#@products</mark></small>', ['@products' => $cpe_self_study_products_count]);
    $form['description'] = [
      '#type' => 'item',
      '#markup' => 'The following are the numbers of records available for sync:' . implode('<br>', $description),
    ];
    // Define Options.
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Sync Options'),
      '#options' => [
        '1' => $this->t('Fetch Changes from AM.net'),
        '2' => $this->t('Update Event Flag: Exclude From Internal Catalog'),
        '3' => $this->t('Add Records from AM.net to Queue'),
      ],
      '#default_value' => '1',
      '#required' => TRUE,
    ];
    // Define Options.
    $form['limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Limit'),
      '#options' => [
        '-1' => $this->t('All'),
        '5' => $this->t('5'),
        '10' => $this->t('10'),
        '50' => $this->t('50'),
        '100' => $this->t('100'),
        '500' => $this->t('500'),
      ],
      '#default_value' => 'all',
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_events',
      '#value' => $this->t('Fetch Changes on AM.net CPE events'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
    ];
    // Sync Self-study CPE products.
    $form['actions']['sync_cpe_self_study_products'] = [
      '#type' => 'submit',
      '#name' => 'sync_cpe_self_study_products',
      '#value' => $this->t('Sync Self-study CPE products'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
      '#submit' => [[$this, 'submitSyncSelfStudyCpeProducts']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $limit = $form_state->getValue('limit');
    $operation = $form_state->getValue('type');
    if ($operation == '3') {
      // Add Events to the Queue.
      /** @var \Drupal\am_net_cpe\CpeProductManagerInterface $product_manager */
      $product_manager = \Drupal::service('am_net_cpe.product_manager');
      try {
        $events = $product_manager->getDrupalEvents();
        if (!empty($events)) {
          /* @var \Drupal\Core\Queue\QueueFactory $queueFactory */
          $queueFactory = \Drupal::service('queue');
          $queue = $queueFactory->get('am_net_triggers');
          // Clear Queue.
          $queue->deleteQueue();
          // Add new items.
          foreach ($events as $key => $item) {
            $code = $item->field_amnet_event_id_code ?? FALSE;
            $year = $item->field_amnet_event_id_year ?? FALSE;
            if (!empty($code) && !empty($year)) {
              $item = new EventSyncQueueItem($year, $code);
              $queue->createItem($item);
            }
          }
          $queue_number_of_items = $queue->numberOfItems();
          drupal_set_message(t('#@items events added to the queue.', ['@items' => $queue_number_of_items]));
        }
      }
      catch (\Exception $e) {
        drupal_set_message($e->getMessage(), $type = 'error');
      }
      // Stop Here.
      return;
    }
    // Get all Records.
    $records = $this->getCpeEventRecords($limit);
    $title = t('Syncing CPE Events...');
    $empty_records_message = t('There are no pending CPE events to be processed!.');
    // Set Batch Operations.
    // Define Sync callbacks.
    $namespace = '\Drupal\am_net_cpe\Form\FetchChangesOnCpeEventsFromAmNetForm';
    $sync_callback = "{$namespace}::syncAmNetCpeEventProduct";
    $sync_finished_callback = "{$namespace}::syncFinishedCallback";
    // Build batch operations.
    $operations = [];
    if (!empty($records)) {
      // Loop over the record_ids and add them to the operation batch.
      foreach ($records as $key => $entity_id) {
        $operations[] = [
          $sync_callback,
          [$entity_id, $operation],
        ];
      }
    }
    if (!empty($operations)) {
      $batch = [
        'title' => $title,
        'operations' => $operations,
        'finished' => $sync_finished_callback,
      ];
      batch_set($batch);
    }
    else {
      drupal_set_message($empty_records_message, 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitSyncSelfStudyCpeProducts(array &$form, FormStateInterface $form_state) {
    $limit = $form_state->getValue('limit');
    $operation = $form_state->getValue('type');
    if ($operation == '3') {
      // Add Events to the Queue.
      /** @var \Drupal\am_net_cpe\CpeProductManagerInterface $product_manager */
      $product_manager = \Drupal::service('am_net_cpe.product_manager');
      try {
        $products = $product_manager->getAmNetProductCodes();
        if (!empty($products)) {
          /* @var \Drupal\Core\Queue\QueueFactory $queueFactory */
          $queueFactory = \Drupal::service('queue');
          $queue = $queueFactory->get('am_net_triggers');
          // Clear Queue.
          $queue->deleteQueue();
          // Add new items.
          foreach ($products as $key => $code) {
            $item = new ProductSyncQueueItem($code);
            $queue->createItem($item);
          }
          $queue_number_of_items = $queue->numberOfItems();
          drupal_set_message(t('#@items products added to the queue.', ['@items' => $queue_number_of_items]));
        }
      }
      catch (\Exception $e) {
        drupal_set_message($e->getMessage(), $type = 'error');
      }
      // Stop Here.
      return;
    }
    // Get all Records.
    $records = $this->getCpeSelfStudyProducts($limit);
    $title = t('Syncing Self-study CPE products...');
    $empty_records_message = t('There are no pending Self-study CPE products to be processed!.');
    // Set Batch Operations.
    // Define Sync callbacks.
    $namespace = '\Drupal\am_net_cpe\Form\FetchChangesOnCpeEventsFromAmNetForm';
    $sync_callback = "{$namespace}::syncAmNetCpeSelfStudyProduct";
    $sync_finished_callback = "{$namespace}::syncCpeSelfStudyFinishedCallback";
    // Build batch operations.
    $operations = [];
    if (!empty($records)) {
      // Loop over the record_ids and add them to the operation batch.
      foreach ($records as $key => $entity_id) {
        $operations[] = [
          $sync_callback,
          [$entity_id, $operation],
        ];
      }
    }
    if (!empty($operations)) {
      $batch = [
        'title' => $title,
        'operations' => $operations,
        'finished' => $sync_finished_callback,
      ];
      batch_set($batch);
    }
    else {
      drupal_set_message($empty_records_message, 'warning');
    }
  }

  /**
   * Syncs a Self-study CPE products from AM.net.
   *
   * @param string $product_id
   *   The product ID.
   * @param string $operation
   *   The type of operation.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function syncAmNetCpeSelfStudyProduct($product_id = NULL, $operation = '1', array &$context = []) {
    if (empty($product_id)) {
      return;
    }
    $product = Product::load($product_id);
    if (!$product) {
      return;
    }
    $field_name = 'field_course_prodcode';
    if (!$product->hasField($field_name)) {
      return NULL;
    }
    $code = $product->get($field_name)->getString();
    if (empty($code)) {
      return NULL;
    }
    $message = '<h3>Processing Self-study CPE product: ' . $code . ' / ' . ' Drupal Product(' . $product_id . ')...</h3>';
    $code = trim($code);
    if ($operation == '1') {
      // Fetch Changes from AM.net.
      try {
        /** @var \Drupal\am_net_cpe\CpeProductManagerInterface $manager */
        $manager = \Drupal::service('am_net_cpe.product_manager');
        $product = $manager->syncAmNetCpeSelfStudyProduct($code);
        $context['results']['success'][] = [
          'code' => $code,
          'link' => $product->toUrl()->toString(),
          'title' => $product->label(),
        ];
      }
      catch (AmNetRecordExcludedException $e) {
        $context['results']['excluded'][] = [
          'code' => $code,
          'message' => $e->getMessage(),
        ];
      }
      catch (\Exception $e) {
        \Drupal::logger('am_net_cpe')->debug($e->getMessage());
      }
    }
    elseif ($operation == '2') {
      // Update Event Flag: Exclude From Internal Catalog.
      $field_name = 'field_exclude_from_web_catalog';
      $exclude_from_web_catalog = $product->get($field_name)->getValue();
      $field_value = is_array($exclude_from_web_catalog) ? current($exclude_from_web_catalog) : [];
      $value_defined = isset($field_value['value']);
      if (!$value_defined) {
        // Set default value.
        $product->set($field_name, FALSE);
      }
      // Save Changes.
      $product->save();
    }
    $context['message'] = $message;
  }

  /**
   * Syncs a CPE event product from AM.net.
   *
   * @param string $product_id
   *   The product ID.
   * @param string $operation
   *   The type of operation.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function syncAmNetCpeEventProduct($product_id = NULL, $operation = '1', array &$context = []) {
    if (empty($product_id)) {
      return;
    }
    $product = Product::load($product_id);
    if (!$product) {
      return;
    }
    $field_name = 'field_amnet_event_id';
    if (!$product->hasField($field_name)) {
      return NULL;
    }
    $am_net_event_id = $product->get($field_name)->getValue();
    $am_net_event_id = is_array($am_net_event_id) ? current($am_net_event_id) : NULL;
    $event_code = $am_net_event_id['code'] ?? NULL;
    $event_year = $am_net_event_id['year'] ?? NULL;
    if (empty($event_code) || empty($event_year)) {
      return NULL;
    }
    $message = '<h3>Processing Event: ' . $event_year . ' / ' . $event_code . ' Drupal Product(' . $product_id . ')...</h3>';
    $event_code = trim($event_code);
    $event_year = trim($event_year);
    if ($operation == '1') {
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
      catch (AmNetRecordExcludedException $e) {
        $context['results']['excluded'][] = [
          'code' => $event_code,
          'year' => $event_year,
          'message' => $e->getMessage(),
        ];
      }
      catch (\Exception $e) {
        \Drupal::logger('am_net_cpe')->debug($e->getMessage());
      }
    }
    elseif ($operation == '2') {
      // Update Event Flag: Exclude From Internal Catalog.
      $field_name = 'field_exclude_from_web_catalog';
      $exclude_from_web_catalog = $product->get($field_name)->getValue();
      $field_value = is_array($exclude_from_web_catalog) ? current($exclude_from_web_catalog) : [];
      $value_defined = isset($field_value['value']);
      if (!$value_defined) {
        // Set default value.
        $product->set($field_name, FALSE);
      }
      // Save Changes.
      $product->save();
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
        drupal_set_message(t('Could not sync :year/:code (:message)', [
          ':message' => $excluded['message'],
          ':year' => $excluded['year'],
          ':code' => $excluded['code'],
        ]), 'warning');
      }
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
  public static function syncCpeSelfStudyFinishedCallback($success, array $results = [], array $operations = []) {
    if (!empty($results['success'])) {
      drupal_set_message(t('Self-study CPE products Successfully synced.'));
    }
  }

  /**
   * Get the Number of CPE Events.
   *
   * @return int
   *   Number of CPE Events.
   */
  public function getNumberOfCpeEvents() {
    $query = \Drupal::entityQuery('commerce_product');
    // Filter by product type: cpe_event.
    return $query->condition('type', 'cpe_event')->count()->execute();
  }

  /**
   * Get all the Cpe Event Record IDs.
   *
   * @param string $limit
   *   Required param, The limit.
   *
   * @return array
   *   The list of Cpe Events IDs.
   */
  public function getCpeEventRecords($limit = '-1') {
    $query = \Drupal::entityQuery('commerce_product');
    // Filter node type.
    $query->condition('type', 'cpe_event');
    // Add Limit.
    if ($limit != '-1') {
      $query->range(0, $limit);
    }
    return $query->execute();
  }

  /**
   * Get the Number of Self-study CPE products.
   *
   * @return int
   *   Number of Self-study CPE products.
   */
  public function getNumberOfCpeSelfStudyProducts() {
    $query = \Drupal::entityQuery('commerce_product');
    // Filter by product type: cpe_self_study.
    return $query->condition('type', 'cpe_self_study')->count()->execute();
  }

  /**
   * Get all the Self-study CPE products IDs.
   *
   * @param string $limit
   *   Required param, The limit.
   *
   * @return array
   *   The list of Self-study CPE products IDs.
   */
  public function getCpeSelfStudyProducts($limit = '-1') {
    $query = \Drupal::entityQuery('commerce_product');
    // Filter node type.
    $query->condition('type', 'cpe_self_study');
    // Add Limit.
    if ($limit != '-1') {
      $query->range(0, $limit);
    }
    return $query->execute();
  }

}
