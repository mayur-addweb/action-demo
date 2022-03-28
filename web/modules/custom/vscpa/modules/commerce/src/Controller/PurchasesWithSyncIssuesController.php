<?php

namespace Drupal\vscpa_commerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class PurchasesWithSyncIssuesController.
 *
 *  Returns responses Order with Sync Issues.
 */
class PurchasesWithSyncIssuesController extends ControllerBase {

  /**
   * Generates an overview table of older revisions of a Event session.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function list() {
    $query = \Drupal::database()->select('commerce_order__field_am_net_sync', 't');
    $query->join('commerce_order', 'o', 'o.order_id = t.entity_id');
    $fields = [
      'field_am_net_sync_sync_status',
      'entity_id',
      'bundle',
    ];
    $query->fields('t', $fields);
    $query->fields('o', ['mail']);
    $query->condition('field_am_net_sync_sync_status', '0');
    $query->condition('state', 'draft', '<>');
    $items = $query->execute()->fetchAll();
    // Build the array of rows.
    $rows = [];
    foreach ($items as $delta => $order) {
      $order_id = $order->entity_id ?? NULL;
      $bundle = $order->bundle ?? NULL;
      $mail = $order->mail ?? NULL;
      $url = Url::fromRoute('entity.commerce_order.edit_form', ['commerce_order' => $order_id]);
      $link = Link::fromTextAndUrl('View Order', $url);
      $rows[] = [
        'data' => [
          'order_id ' => '#' . $order_id,
          'bundle' => $bundle,
          'mail ' => $mail,
          'link' => $link->toString(),
        ],
      ];
    }
    $header = [
      $this->t('# Order Number'),
      $this->t('Type'),
      $this->t('Email'),
      $this->t('Operations'),
    ];
    $build['#title'] = $this->t('List of Purchases With Sync Issues.');
    $build['event_session_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#empty' => $this->t('There are no new purchases with syncing issues.'),
    ];
    return $build;
  }

}
