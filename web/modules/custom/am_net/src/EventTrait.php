<?php

namespace Drupal\am_net;

/**
 * AM.net Event Helper trait implementation.
 */
trait EventTrait {

  /**
   * Get events changed recently from AM.net since given date.
   *
   * @param string $date
   *   The given date.
   *
   * @return array
   *   List of AM.net Events IDs.
   */
  public function getEventsChangedRecentlyFromAmNetByDate($date = NULL) {
    if (empty($date)) {
      return [];
    }
    $records = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    // Get records with changes since the last run.
    $format = 'Y-m-d H';
    $time = strtotime($date);
    $since = date($format, $time) . ':00:00';
    $query = (!is_null($_client)) ? $_client->get('Event', ['since' => $since]) : FALSE;
    if (!$query->hasError()) {
      $records = $query->getResult();
    }
    return $records;
  }

  /**
   * Get events changed recently from AM.net.
   *
   * @return array
   *   List of AM.net Events IDs.
   */
  public function getEventsChangedRecentlyFromAmNet() {
    $interval = AM_NET_RECORDS_FETCH_INTERVAL;
    if (!($interval > 0)) {
      return [];
    }
    $trigger_key = 'am_net_triggers.events_fetch_last';
    $state = \Drupal::state();
    $request_time = \Drupal::time()->getRequestTime();
    $events_fetch_last = $state->get($trigger_key);
    if (is_numeric($events_fetch_last)) {
      $events_fetch_next = $events_fetch_last + $interval;
      $run = ((int) $request_time > $events_fetch_next);
      if (!$run) {
        return [];
      }
    }
    else {
      $events_fetch_next = $request_time;
    }
    $records = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    // Get records with changes since the last run.
    $format = 'Y-m-d H';
    $time = $events_fetch_next - $interval;
    // @todo validate 1h behind issue on AM.net records.
    // For now just reduce current time in 1h.
    $time = $time - 3600;
    $since = date($format, $time) . ':00:00';
    $request_params = [
      'includeFees' => 'true',
      'includeSessions' => 'true',
      'since' => $since,
    ];
    $query = (!is_null($_client)) ? $_client->get('Event', $request_params) : FALSE;
    if (!$query->hasError()) {
      $records = $query->getResult();
      // Update the events fetch last run time.
      $state->set($trigger_key, $request_time);
    }
    return $records;
  }

  /**
   * Get products changed recently from AM.net.
   *
   * @return array
   *   List of AM.net User Profiles IDs.
   */
  public function getProductsChangedRecentlyFromAmNet() {
    $interval = AM_NET_RECORDS_FETCH_INTERVAL;
    if (!($interval > 0)) {
      return [];
    }
    $trigger_key = 'am_net_triggers.products_fetch_last';
    $state = \Drupal::state();
    $request_time = \Drupal::time()->getRequestTime();
    $products_fetch_last = $state->get($trigger_key);
    if (is_numeric($products_fetch_last)) {
      $products_fetch_next = $products_fetch_last + $interval;
      $run = ((int) $request_time > $products_fetch_next);
      if (!$run) {
        return [];
      }
    }
    else {
      $products_fetch_next = $request_time;
    }
    $records = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    // Get records with changes since the last run.
    $format = 'Y-m-d';
    $time = $products_fetch_next - $interval;
    // @todo validate 1h behind issue on AM.net records.
    // For now just reduce current time in 1h.
    $time = $time - 3600;
    $since = date($format, $time);
    $query = (!is_null($_client)) ? $_client->get('Product', ['since' => $since]) : FALSE;
    if (!$query->hasError()) {
      $records = $query->getResult();
      // Update the products fetch last run time.
      $state->set($trigger_key, $request_time);
    }
    return $records;
  }

}
