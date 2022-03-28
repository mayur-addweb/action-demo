<?php

namespace Drupal\vscpa_content_migration\Commands;

use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\Product;
use Drupal\am_net_triggers\QueueItem\EventSyncQueueItem;

/**
 * Class for delete Content in batch.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\am_net_user_profile\Commands
 */
class DeleteAll extends DrushCommands {

  /**
   * Check if a given variation Exist.
   *
   * @param string $variation_id
   *   The product variation ID.
   *
   * @return bool
   *   Return TRUE if the variation exist, otherwise FALSE.
   */
  public function variationExist($variation_id = NULL) {
    if (empty($variation_id)) {
      return FALSE;
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product_variation', 'variation');
    $query->fields('variation', ['variation_id']);
    $query->condition('variation_id', $variation_id);
    $entity_id = $query->execute()->fetchField();
    return !empty($entity_id);
  }

  /**
   * Get products with non existing variations.
   *
   * @command get-products-with-non-existing-variations
   *
   * @usage drush get-products-with-non-existing-variations
   *   Delete all product of type events.
   *
   * @aliases gpwnev
   */
  public function getProductsWithNonExistingVariations($type = '') {
    if (empty($type)) {
      return;
    }
    $ids = \Drupal::entityQuery('commerce_product')
      ->condition('type', $type)
      ->execute();
    if (empty($ids)) {
      return;
    }
    $items = [];
    foreach ($ids as $delta => $id) {
      $message = t('Checking @type product: @product_id.', ['@product_id' => $id, '@type' => $type]);
      drush_log($message, 'success');
      // Get variations.
      $product = Product::load($id);
      $variations = $product->get("variations")->getValue();
      if (!empty($variations)) {
        foreach ($variations as $key => $variation) {
          $target_id = $variation['target_id'];
          $variation_exist = $this->variationExist($target_id);
          if (!$variation_exist) {
            $message = t('-- Product with non-existing variations: @product_id.', ['@product_id' => $id]);
            drush_log($message, 'success');
            $items[] = $id;
            break;
          }
        }
      }
    }
    $state = \Drupal::state();
    $key = "products.with.non.existing.variations.{$type}";
    $state->set($key, $items);
    $message = t('Total number of processed products: @total.', ['@total' => count($ids)]);
    drush_log($message, 'success');
    $message = t('Total Numbers of product with non-existing variations: @total.', ['@total' => count($items)]);
    drush_log($message, 'success');
  }

  /**
   * Clean events Variations.
   *
   * @command clean-events-variations
   *
   * @usage drush clean-events-variations
   *   Clean events Variations.
   *
   * @aliases clean_events_variations
   */
  public function cleanEventsVariations() {
    $type = 'cpe_event';
    $key = "products.with.non.existing.variations.{$type}";
    $items = \Drupal::state()->get($key, []);
    if (empty($items)) {
      return;
    }
    $queue_name = 'am_net_triggers';
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get($queue_name);
    foreach ($items as $delta => $id) {
      $message = t('Cleaning variations on @type product: @product_id.', ['@product_id' => $id, '@type' => $type]);
      drush_log($message, 'success');
      $product = Product::load($id);
      if (!$product) {
        continue;
      }
      // Remove all the variations references.
      $product->setVariations([]);
      // Save the changes.
      $product->save();
      // Add product to the queue.
      $field_value = $product->get('field_amnet_event_id')->getValue();
      $field_value = is_array($field_value) ? current($field_value) : [];
      $code = $field_value['code'] ?? FALSE;
      $year = $field_value['year'] ?? FALSE;
      if (!empty($code) && !empty($year)) {
        $code = trim($code);
        $year = trim($year);
        $item = new EventSyncQueueItem($year, $code);
        $queue->createItem($item);
        $message = t('-- The Product ID: @id added to queue.', ['@id' => $id]);
        drush_log($message, 'success');
      }
    }
  }

  /**
   * Delete the no migrated users from AM.net.
   *
   * @command delete-all:users_no_migrated
   *
   * @usage drush delete-all:users_no_migrated
   *   Delete the no migrated users from AM.net.
   *
   * @aliases delete_all_users_no_migrated
   */
  public function commandDeleteAllUsers() {
    $state = \Drupal::state();
    $key = "amnet.synchronized.users";
    $type = 'success';
    $values = $state->get($key, []);
    if (empty($values)) {
      $message = t('Total Numbers of users no migrated: @total.', ['@total' => 0]);
      drush_log($message, $type);
      return;
    }
    // Get the products that will to be processed.
    $ids = \Drupal::entityQuery('user')
      ->condition('uid', $values, 'NOT IN')
      ->execute();
    $total = 0;
    if (!empty($ids)) {
      /** @var \Drupal\vscpa_sso\GluuClient $gluuClient */
      $gluuClient = \Drupal::service('gluu.client');
      foreach ($ids as $delta => $id) {
        $total = $total + 1;
        $entity = User::load($id);
        $is_admin = $entity && ($entity->hasRole('administrator') || $entity->hasRole('vscpa_administrator') || $entity->hasRole('amnet_agent'));
        if ($entity && !$is_admin) {
          $message = t('Removing user: @user_id.', ['@user_id' => $id]);
          $email = $entity->getEmail();
          // #1. Remove Drupal User locally.
          $entity->delete();
          // Get the Gluu account tie to this email.
          $gluu_account = $gluuClient->getByMail($email);
          if ($gluu_account) {
            $gluu_uid = $gluu_account->id;
            $result = $gluuClient->deleteUser($gluu_uid);
          }
          drush_log($message, $type);
        }
      }
    }
    $message = t('Total Numbers of users no migrated:: @total.', ['@total' => $total]);
    drush_log($message, $type);
  }

  /**
   * Delete all product of type cpe_self_study.
   *
   * @command delete-all:cpe_self_study
   *
   * @usage drush delete-all:cpe_self_study
   *   Delete all product of type cpe_self_study.
   *
   * @aliases delete_all_cpe_self_study
   */
  public function commandDeleteAllCpeSelfStudy() {
    $state = \Drupal::state();
    $key = "amnet.synchronized.cpe_product";
    $type = 'success';
    $values = $state->get($key, []);
    if (empty($values)) {
      $message = t('Total Numbers of Cpe-Self-Study products: @total.', ['@total' => 0]);
      drush_log($message, $type);
      return;
    }
    // Get the products that will to be processed.
    $ids = \Drupal::entityQuery('commerce_product')
      ->condition('type', 'cpe_self_study')
      ->condition('product_id', $values, 'NOT IN')
      ->execute();
    $total = 0;
    if (!empty($ids)) {
      foreach ($ids as $delta => $id) {
        $total = $total + 1;
        $message = t('Removing self-study product: @self_study_id.', ['@self_study_id' => $id]);
        $product = Product::load($id);
        if ($product) {
          $product->delete();
        }
        drush_log($message, $type);
      }
    }
    $message = t('Total Numbers of Cpe-Self-Study products: @total.', ['@total' => $total]);
    drush_log($message, $type);
  }

  /**
   * Delete all product of type events.
   *
   * @command delete-all:events
   *
   * @usage drush delete-all:events
   *   Delete all product of type events.
   *
   * @aliases delete_all_events
   */
  public function commandDeleteAllEvents() {
    $state = \Drupal::state();
    $key = "amnet.synchronized.events";
    $values = $state->get($key, []);
    $type = 'success';
    if (empty($values)) {
      $message = t('Total Numbers of Events: @total.', ['@total' => 0]);
      drush_log($message, $type);
      return;
    }
    // Get the products that will to be processed.
    $ids = \Drupal::entityQuery('commerce_product')
      ->condition('type', 'cpe_event')
      ->condition('product_id', $values, 'NOT IN')
      ->execute();
    $total = 0;
    if (!empty($ids)) {
      foreach ($ids as $delta => $id) {
        $total = $total + 1;
        $message = t('Removing product event: @event_id.', ['@event_id' => $id]);
        $product = Product::load($id);
        if ($product) {
          $product->delete();
        }
        drush_log($message, $type);
      }
    }
    $message = t('Total Numbers of Events: @total.', ['@total' => $total]);
    drush_log($message, $type);
  }

  /**
   * Delete all the commerce orders on the site.
   *
   * @command delete-all:orders
   *
   * @usage drush delete-all:orders
   *   Delete all the commerce orders on the site.
   *
   * @aliases delete_all_orders
   */
  public function commandDeleteAllOrders() {
    // Get the Ordres that will to be processed.
    $ids = \Drupal::entityQuery('commerce_order')->execute();
    $total = 0;
    $type = 'success';
    foreach ($ids as $delta => $id) {
      $total = $total + 1;
      $message = t('Removing order @order_id.', ['@order_id' => $id]);
      $entity = Order::load($id);
      if ($entity) {
        $entity->delete();
      }
      drush_log($message, $type);
    }
    $message = t('Total Numbers of Orders: @total.', ['@total' => $total]);
    drush_log($message, $type);
  }

  /**
   * Add all events to the Queue.
   *
   * @command add-all-event-to-queue
   *
   * @usage drush add-all-event-to-queue
   *   Add all events to the Queue.
   *
   * @aliases add_all_event_to_queue
   */
  public function addAllEventToQueue() {
    // Get All the events.
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_amnet_event_id', 'amnet_event_id');
    $query->fields('amnet_event_id', [
      'entity_id',
      'field_amnet_event_id_code',
      'field_amnet_event_id_year',
    ]);
    $items = $query->execute()->fetchAll();
    if (!empty($items)) {
      $message = t('No Events pending to be added to the queue.');
      drush_log($message, 'success');
    }
    $queue_name = 'am_net_triggers';
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get($queue_name);
    $total = 0;
    foreach ($items as $delta => $item) {
      $code = $item->field_amnet_event_id_code ?? FALSE;
      $year = $item->field_amnet_event_id_year ?? FALSE;
      $entity_id = $item->entity_id ?? FALSE;
      if (!empty($code) && !empty($year)) {
        $code = trim($code);
        $year = trim($year);
        $item = new EventSyncQueueItem($year, $code);
        $queue->createItem($item);
        $params = [
          '@id' => $entity_id,
          '@code' => $code,
          '@year' => $year,
        ];
        $message = t('-- The Event ID: @id added to queue(@code / @year).', $params);
        drush_log($message, 'success');
        $total = $total + 1;
      }

    }
    $message = t('Total Numbers of Events added to the queue: @total.', ['@total' => $total]);
    drush_log($message, 'success');
  }

  /**
   * Add events to the Queue since given date.
   *
   * @command add-events-to-queue-since-date
   *
   * @usage drush add-events-to-queue-since-date
   *   Add events to the Queue since a given date.
   *
   * @aliases add_events_to_queue_since_date
   */
  public function addEventsToQueueSinceDate($date = '') {
    if (empty($date)) {
      drush_log('Please provide a valid date', 'alert');
      return;
    }
    $records = \Drupal::service('am_net.client')->getEventsChangedRecentlyFromAmNetByDate($date);
    if (empty($records) || !is_array($records)) {
      drush_log('No Events pending to be added to the queue.', 'success');
      return;
    }
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('am_net_triggers');
    $total = 0;
    foreach ($records as $key => $record) {
      $code = $record['EventCode'] ?? FALSE;
      $year = $record['EventYear'] ?? FALSE;
      if (empty($code) || empty($year)) {
        continue;
      }
      $item = new EventSyncQueueItem($year, $code);
      $queue->createItem($item);
      $params = [
        '@code' => $code,
        '@year' => $year,
      ];
      $message = t('-- The Event ID: @id added to queue(@code / @year).', $params);
      drush_log($message, 'success');
      $total = $total + 1;
    }
    $message = t('Total Numbers of Events added to the queue: @total.', ['@total' => $total]);
    drush_log($message, 'success');
  }

}
