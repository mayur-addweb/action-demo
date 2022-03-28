<?php

namespace Drupal\am_net_cpe\Commands;

use Drupal\am_net_triggers\QueueItem\ProductSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\EventSyncQueueItem;
use Drupal\commerce_product\Entity\Product;
use Drupal\am_net_cpe\CpeProductManager;
use Drupal\am_net_cpe\EventHelper;
use Drush\Commands\DrushCommands;

/**
 * Class CPE.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\am_net_cpe\Commands
 */
class Cpe extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\am_net_cpe\CpeProductManager
   */
  protected $cpeProductManager;

  /**
   * Cpe constructor.
   *
   * @param \Drupal\am_net_cpe\CpeProductManager $cpeProductManager
   *   The CPE product manager.
   */
  public function __construct(CpeProductManager $cpeProductManager) {
    $this->cpeProductManager = $cpeProductManager;
  }

  /**
   * Un-publish events by a given ID.
   *
   * @command events:un_publish
   *
   * @usage drush events:un_publish
   *   Un-publish events by a given ID.
   *
   * @aliases events_un_publish
   */
  public function unPublishEvent($id = NULL) {
    if (empty($id)) {
      $this->output()->writeln(dt('Please provide a valid string.'));
      return;
    }
    $product = Product::load($id);
    if (!$product) {
      $this->output()->writeln(dt('Please provide a valid event product ID.'));
      return;
    }
    if ($product->bundle() != 'cpe_event') {
      $message = dt('Please provide a Id is not related to a event product.');
      $this->output()->writeln($message);
      return;
    }
    $product->setPublished(FALSE);
    $product->save();
    $message = 'Product(Un-published): [' . $product->label() . ']';
    $this->output()->writeln($message);
  }

  /**
   * Delete events by a given ID.
   *
   * @command events:delete
   *
   * @usage drush events:delete
   *   Delete events by a given ID.
   *
   * @aliases events_delete
   */
  public function deleteEvent($id = NULL) {
    if (empty($id)) {
      $this->output()->writeln(dt('Please provide a valid string.'));
      return;
    }
    $product = Product::load($id);
    if (!$product) {
      $this->output()->writeln(dt('Please provide a valid event product ID.'));
      return;
    }
    if ($product->bundle() != 'cpe_event') {
      $message = dt('Please provide a Id is not related to a event product.');
      $this->output()->writeln($message);
      return;
    }
    $product->delete();
    $message = 'Product(Deleted): [' . $product->label() . ']';
    $this->output()->writeln($message);
  }

  /**
   * Clear event registration cache.
   *
   * @param string $code
   *   The event code.
   * @param string $year
   *   The event year.
   *
   * @command event:clear_event_registrations_cache
   *
   * @usage drush event:clear_event_registrations_cache
   *   Clear event registration cache.
   *
   * @aliases event_clear_event_registrations_cache
   */
  public function clearEventRegistrationCache($code = NULL, $year = NULL) {
    EventHelper::clearEventRegistrationCache($code, $year);
    $message = 'Event-registrations cache cleared for: [' . $code . '/' . $year . ']';
    $this->output()->writeln($message);
  }

  /**
   * Add event to sync queue.
   *
   * @command event:add_to_queue
   *
   * @usage drush event:add_to_queue
   *   Add event to sync queue.
   *
   * @aliases event_add_to_queue
   */
  public function addEventToSyncQueue($code = NULL, $year = NULL) {
    if (empty($code) || empty($year)) {
      $this->output()->writeln(dt('Please provide a valid code and year.'));
      return;
    }
    /* @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = \Drupal::service('queue');
    $queue = $queueFactory->get('am_net_triggers');
    $item = new EventSyncQueueItem($year, $code);
    $queue->createItem($item);
    $output = dt('Event: ' . $code . '/' . $year . ' added to the sync queue.');
    $this->output()->writeln($output);
  }

  /**
   * Un-publish events that start with.
   *
   * @command events:un_publish_that_start_with
   *
   * @usage drush events:un_publish_that_start_with
   *   Un-publish events that start with.
   *
   * @aliases events_un_publish_that_start_with
   */
  public function unPublishThatStartWith($start = NULL, $year = NULL) {
    if (empty($start)) {
      $this->output()->writeln(dt('Please provide a valid string.'));
      return;
    }
    $items = $this->getEventIdsThatStartWith($start, $year);
    if (empty($items)) {
      $output = dt('No events were found with the provide start string(' . $start . ').');
      $this->output()->writeln($output);
      return;
    }
    $count = count($items);
    $output = dt('Are you sure you want Un-publish ' . $count . ' events?');
    if (!$this->io()->confirm($output)) {
      $this->output()->writeln(dt('Task Completed!.'));
      return;
    }
    $commands = '';
    $total = 0;
    foreach ($items as $key => $record) {
      $id = $record->entity_id ?? FALSE;
      if (empty($id)) {
        continue;
      }
      $commands .= "../vendor/bin/drush events_un_publish {$id};";
      $total++;
    }
    $output = dt('Total number of events added to the command: ' . $total . '.');
    $this->output()->writeln($output);
    $this->output()->writeln(' ');
    $this->output()->writeln($commands);
  }

  /**
   * Add events to sync queue that start with.
   *
   * @command events:add_to_queue_that_start_with
   *
   * @usage drush events:add_to_queue_that_start_with
   *   Add events to sync queue that start with.
   *
   * @aliases eatqtsw
   */
  public function addEventsToSyncQueueThatStartWith($start = NULL) {
    if (empty($start)) {
      $this->output()->writeln(dt('Please provide a valid string.'));
      return;
    }
    $items = $this->getEventIdsThatStartWith($start);
    if (empty($items)) {
      $output = dt('No events were found with the provide start string(' . $start . ').');
      $this->output()->writeln($output);
      return;
    }
    $count = count($items);
    $output = dt('Are you sure you want add to the sync queue ' . $count . ' events?');
    if (!$this->io()->confirm($output)) {
      $this->output()->writeln(dt('Task Completed!.'));
      return;
    }
    /* @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = \Drupal::service('queue');
    $queue = $queueFactory->get('am_net_triggers');
    $total = 0;
    foreach ($items as $key => $record) {
      $code = $record->field_amnet_event_id_code ?? FALSE;
      $year = $record->field_amnet_event_id_year ?? FALSE;
      if (empty($code) && empty($year)) {
        continue;
      }
      $item = new EventSyncQueueItem($year, $code);
      $queue->createItem($item);
      $output = dt('Event: ' . $code . '/' . $year . ' added to the sync queue.');
      $this->output()->writeln($output);
      $total++;
    }
    $output = dt('Total number of events added to the sync queue: ' . $total . '.');
    $this->output()->writeln($output);
    $output = dt('Total number of items in the queue: ' . $queue->numberOfItems() . '.');
    $this->output()->writeln($output);
  }

  /**
   * Get the event IDs that start with a given string.
   *
   * @param string $start
   *   The email address.
   * @param string $year
   *   The event year.
   *
   * @return array|null
   *   All records IDs an indexed array of stdClass objects, otherwise NULL.
   */
  public function getEventIdsThatStartWith($start = NULL, $year = NULL) {
    if (empty($start)) {
      return NULL;
    }
    $database = \Drupal::database();
    $start = $database->escapeLike($start);
    $query = $database->select('commerce_product__field_amnet_event_id', 'amnet_field');
    $query->fields('amnet_field', [
      'field_amnet_event_id_code',
      'field_amnet_event_id_year',
      'entity_id',
    ]);
    $query->condition('bundle', 'cpe_event');
    $query->condition('field_amnet_event_id_code', $start . '%', 'LIKE');
    if (!empty($year)) {
      $query->condition('field_amnet_event_id_year', $year);
    }
    return $query->execute()->fetchAll();
  }

  /**
   * Check if string starts with specific sub-string.
   *
   * @param string $string
   *   The given string.
   * @param string $target
   *   The target string.
   *
   * @return bool
   *   TRUE if the given string starts with the target string, otherwise FALSE.
   */
  public function startsWith($string, $target) {
    $len = strlen($target);
    if ($len == 0) {
      return FALSE;
    }
    return (substr($string, 0, $len) === $target);
  }

  /**
   * Add events to sync queue from date that start with.
   *
   * @command events:add_to_queue_from_date_that_start_with
   *
   * @usage drush events:add_to_queue_from_date_that_start_with
   *   Add events to sync queue from date that start with.
   *
   * @aliases eatqfd
   */
  public function addEventsToSyncQueueFromDateThatStartWith($date_range = '2020-01-03', $start = NULL) {
    $messenger = \Drupal::messenger();
    if (empty($date_range)) {
      $messenger->addStatus(t('Please provide a valid date.'));
      return;
    }
    try {
      $events = $this->cpeProductManager->getAmNetEvents($date_range);
    }
    catch (\Exception $e) {
      $messenger->addStatus($e->getMessage());
      return;
    }
    if (empty($events)) {
      $messenger->addStatus(t('No new events were found in that date range.'));
    }
    $count = count($events);
    $params = [
      '@date' => $date_range,
      '@total' => $count,
    ];
    $messenger->addStatus(t('Total # events changed from date(@date): @total.', $params));
    /* @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = \Drupal::service('queue');
    $queue = $queueFactory->get('am_net_triggers');
    $total = 0;
    foreach ($events as $key => $item) {
      $code = $item['EventCode'] ?? NULL;
      $year = $item['EventYear'] ?? NULL;
      if (empty($code) || empty($year)) {
        continue;
      }
      $params = [
        '@start' => $start,
        '@code' => $code,
      ];
      if (!$this->startsWith($code, $start)) {
        continue;
      }
      $item = new EventSyncQueueItem($year, $code);
      $queue->createItem($item);
      $messenger->addStatus(t('Event: @code/@year added to the sync queue.', [
        '@code' => $code,
        '@year' => $year,
      ]));
      $total++;
    }
    $messenger->addStatus(t('Date range: @date.', ['@date' => $date_range]));
    $messenger->addStatus(t('Total number of events added to the sync queue: @total.', ['@total' => $total]));
  }

  /**
   * Add events to sync queue from date.
   *
   * @command events:add_to_queue_from_date
   *
   * @usage drush events:add_to_queue_from_date
   *   Add events to sync queue from date.
   *
   * @aliases eatqfd
   */
  public function addEventsToSyncQueueFromDate($date_range = '2020-01-03') {
    $messenger = \Drupal::messenger();
    if (empty($date_range)) {
      $messenger->addStatus(t('Please provide a valid date.'));
      return;
    }
    try {
      $events = $this->cpeProductManager->getAmNetEvents($date_range);
    }
    catch (\Exception $e) {
      $messenger->addStatus($e->getMessage());
      return;
    }
    if (empty($events)) {
      $messenger->addStatus(t('No new events were found in that date range.'));
    }
    /* @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = \Drupal::service('queue');
    $queue = $queueFactory->get('am_net_triggers');
    $total = 0;
    foreach ($events as $key => $item) {
      $code = $item['EventCode'] ?? NULL;
      $year = $item['EventYear'] ?? NULL;
      if (empty($code) || empty($year)) {
        continue;
      }
      $item = new EventSyncQueueItem($year, $code);
      $queue->createItem($item);
      $messenger->addStatus(t('Event: @code/@year added to the sync queue.', [
        '@code' => $code,
        '@year' => $year,
      ]));
      $total++;
    }
    $messenger->addStatus(t('Date range: @date.', ['@date' => $date_range]));
    $messenger->addStatus(t('Total number of events added to the sync queue: @total.', ['@total' => $total]));
  }

  /**
   * Add events to sync queue.
   *
   * @command events:add_to_queue
   *
   * @usage drush events:add_to_queue
   *   Add events to sync queue.
   *
   * @aliases eatq
   */
  public function addEventsToSyncQueue() {
    $messenger = \Drupal::messenger();
    $ids = $this->cpeProductManager->getActiveEvents();
    if (empty($ids)) {
      $messenger->addStatus(t('No active events were found in the database.'));
      return;
    }
    // Load event info.
    $records = $this->cpeProductManager->getEventAmNetIds($ids);
    if (empty($records) || !is_array($records)) {
      $messenger->addStatus(t('No active events were found in the database.'));
      return;
    }
    /* @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = \Drupal::service('queue');
    $queue = $queueFactory->get('am_net_triggers');
    $total = 0;
    foreach ($records as $key => $record) {
      $code = $record->field_amnet_event_id_code ?? FALSE;
      $year = $record->field_amnet_event_id_year ?? FALSE;
      if (empty($code) && empty($year)) {
        continue;
      }
      $item = new EventSyncQueueItem($year, $code);
      $queue->createItem($item);
      $messenger->addStatus(t('Event: @code/@year added to the sync queue.', [
        '@code' => $code,
        '@year' => $year,
      ]));
      $total++;
    }
    $messenger->addStatus(t('Total number of events added to the sync queue: @total.', ['@total' => $total]));
    $messenger->addStatus(t('Total number of items in the queue: @total.', ['@total' => $queue->numberOfItems()]));
  }

  /**
   * Add cpe self study products to sync queue.
   *
   * @command cpe_self_study_products:add_to_queue
   *
   * @usage drush cpe_self_study_products:add_to_queue
   *   Add cpe self study products to sync queue.
   *
   * @aliases eatq
   */
  public function addSelfStudyProductsToSyncQueue() {
    $messenger = \Drupal::messenger();
    $records = $this->cpeProductManager->getActiveSelfStudyProducts();
    if (empty($records)) {
      $messenger->addStatus(t('No active cpe self study products were found in the database.'));
      return;
    }
    /* @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = \Drupal::service('queue');
    $queue = $queueFactory->get('am_net_triggers');
    $total = 0;
    foreach ($records as $key => $record) {
      $code = $record->field_course_prodcode_value ?? FALSE;
      if (empty($code)) {
        continue;
      }
      $item = new ProductSyncQueueItem($code);
      $queue->createItem($item);
      $messenger->addStatus(t('Self Study Products: @code added to the sync queue.', [
        '@code' => $code,
      ]));
      $total++;
    }
    $messenger->addStatus(t('Total number of SelfStudy products added to the sync queue: @total.', ['@total' => $total]));
    $messenger->addStatus(t('Total number of items in the queue: @total.', ['@total' => $queue->numberOfItems()]));
  }

}
