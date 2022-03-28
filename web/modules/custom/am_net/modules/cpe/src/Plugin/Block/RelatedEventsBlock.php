<?php

namespace Drupal\am_net_cpe\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\am_net_triggers\QueueItem\EventSyncQueueItem;

/**
 * Provides the 'Related Events' block.
 *
 * @Block(
 *   id = "am_net_cpe_related_events_block",
 *   admin_label = @Translation("AM.Net Related Events")
 * )
 */
class RelatedEventsBlock extends BlockBase {

  /**
   * The event object.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $event = NULL;

  /**
   * The list of Drupal CPE products.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface[]
   */
  protected $listProducts = NULL;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $events = $this->getListOfEvents();
    if (empty($events)) {
      return NULL;
    }
    $items = [];
    foreach ($events as $delta => $event) {
      $items[] = entity_view($event, 'block');
    }
    $elements = [
      '#theme' => 'am_net_related_events',
      '#items' => $items,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $elements;
  }

  /**
   * Get main event.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The main product.
   */
  protected function getMainEvent() {
    if (!$this->event) {
      $this->event = \Drupal::routeMatch()->getParameter('commerce_product');
    }
    return $this->event;
  }

  /**
   * {@inheritdoc}
   */
  protected function getListOfEvents() {
    if (empty($this->listProducts)) {
      $related_events = $this->getAmNetRelatedEvents();
      if (empty($related_events)) {
        return NULL;
      }
      $this->listProducts = [];
      foreach ($related_events as $delta => $event_info) {
        $event_code = $event_info['code'] ?? NULL;
        $event_year = $event_info['year'] ?? NULL;
        if (empty($event_code) || empty($event_year)) {
          continue;
        }
        $event = $this->getDrupalCpeEventProduct($event_code, $event_year);
        if (!$event) {
          continue;
        }
        $this->listProducts[] = $event;
      }
    }
    return $this->listProducts;
  }

  /**
   * Get Drupal Cpe Event Product.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year (two digits).
   * @param bool $add_to_sync_queue
   *   The Flag to determine if the event should be added to the sync queue.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The vent product.
   */
  public function getDrupalCpeEventProduct($event_code, $event_year, $add_to_sync_queue = TRUE) {
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_amnet_event_id', 'amnet_event_id');
    $query->fields('amnet_event_id', ['entity_id']);
    $query->condition('field_amnet_event_id_code', $event_code);
    $query->condition('field_amnet_event_id_year', $event_year);
    $entity_id = $query->execute()->fetchField();
    $event = NULL;
    if (!empty($entity_id)) {
      $event = Product::load($entity_id);
    }
    if (!$event && $add_to_sync_queue) {
      /* @var \Drupal\Core\Queue\QueueFactory $queue_factory */
      $queue_factory = \Drupal::service('queue');
      $queue = $queue_factory->get('am_net_triggers');
      $item = new EventSyncQueueItem($event_year, $event_code);
      $queue->createItem($item);
    }
    return $event;
  }

  /**
   * Get related event codes.
   *
   * @return array|null
   *   The AM.net related products.
   */
  protected function getAmNetRelatedEvents() {
    $event = $this->getMainEvent();
    if (!$event) {
      return NULL;
    }
    if (!($event instanceof ProductInterface)) {
      return NULL;
    }
    if ($event->bundle() != 'cpe_event') {
      return NULL;
    }
    $field = 'field_am_net_related_events';
    $value = $event->get($field)->getValue();
    if (empty($value)) {
      return NULL;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $products = $this->getListOfEvents();
    if (empty($products)) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::allowed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
