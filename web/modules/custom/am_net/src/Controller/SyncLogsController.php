<?php

namespace Drupal\am_net\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class SyncLogsController.
 *
 *  Returns Recent log messages.
 */
class SyncLogsController extends ControllerBase {

  /**
   * Generates an overview table of Recent sync log messages.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function list() {
    $state = \Drupal::state();
    $key = 'am_net.sync.error';
    $items = $state->get($key, []);
    // Build the array of rows.
    $rows = [];
    foreach ($items as $delta => $item) {
      // Date-time.
      $time = $item['time'] ?? NULL;
      if (!empty($time)) {
        $time = date('Y-m-d H:i:s', $time);
      }
      // ID.
      $id = $item['id'] ?? NULL;
      // Operation.
      $operation = $item['operation'] ?? NULL;
      // Endpoint.
      $endpoint = $item['endpoint'] ?? NULL;
      // Request Type.
      $request_type = $item['request_type'] ?? NULL;
      // Error Message.
      $error_message = $item['error_message'] ?? NULL;
      // Error Code.
      $error_code = $item['error_code'] ?? NULL;
      // Entity detail.
      $entity_detail = $item['entity_detail'] ?? NULL;
      // Host.
      $host = $item['host'] ?? NULL;
      // Current uri.
      $current_uri = $item['current_uri'] ?? NULL;
      // Query Params.
      $query_params = $item['queryParams'] ?? NULL;
      if (!empty($query_params)) {
        $query_params = json_encode($query_params);
      }
      // Json Entity.
      $json_entity = $item['json_entity'] ?? NULL;
      $add = !empty($time);
      if ($add) {
        // Add item to the list.
        $rows[] = [
          'data' => [
            'time ' => $time,
            'id' => $id,
            'operation' => $operation,
            'endpoint' => $endpoint,
            'request_type' => strtoupper($request_type),
            'error_message' => $error_message,
            'error_code' => $error_code,
            'entity_detail' => $entity_detail,
            'host' => $host,
            'current_uri' => $current_uri,
            'query_params' => $query_params,
            'json_entity' => $json_entity,
          ],
        ];
      }
    }
    $header = [
      $this->t('Date Time'),
      $this->t('ID'),
      $this->t('Operation'),
      $this->t('Endpoint'),
      $this->t('Request Type'),
      $this->t('Error Message'),
      $this->t('Error Code'),
      $this->t('Entity Detail'),
      $this->t('Current Uri'),
      $this->t('Host'),
      $this->t('Query Params'),
      $this->t('Json Entity'),
    ];
    $build['#title'] = $this->t('AM.net Sync - Recent log messages');
    $build['log_messages'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#empty' => $this->t('There are no new sync log message.'),
    ];
    return $build;
  }

}
