<?php

namespace Drupal\am_net;

use Drupal\am_net_firms\Entity\Firm;

/**
 * AM.net Firm Codes Helper trait implementation.
 */
trait FirmCodesTrait {

  /**
   * Fetches all AM.net Firm Codes.
   *
   * @return array
   *   List of AM.net Firm Codes.
   */
  public function getAllFirmCodes() {
    $entities = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    $query = (!is_null($_client)) ? $_client->get(Firm::getApiEndPoint(), ['all' => 'true']) : FALSE;
    if (!$query->hasError()) {
      $entities = $query->getResult();
    }
    return $entities;
  }

  /**
   * Fetches a AM.net Firm Codes object by Date.
   *
   * @param string $date
   *   The given date since.
   *
   * @return array|bool
   *   List of AM.net Firm Codes.
   */
  public function getAllFirmByDate($date) {
    $entities = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    $query = (!is_null($_client)) ? $_client->get(Firm::getApiEndPoint(), ['since' => $date]) : FALSE;
    if (!$query->hasError()) {
      $entities = $query->getResult();
    }
    return $entities;
  }

  /**
   * Get firms changed recently from AM.net.
   *
   * @return array
   *   List of AM.net User Profiles IDs.
   */
  public function getFirmsChangedRecentlyFromAmNet() {
    $interval = AM_NET_RECORDS_FETCH_INTERVAL;
    if (!($interval > 0)) {
      return [];
    }
    $trigger_key = 'am_net_triggers.firms_fetch_last';
    $state = \Drupal::state();
    $request_time = \Drupal::time()->getRequestTime();
    $firms_fetch_last = $state->get($trigger_key);
    if (is_numeric($firms_fetch_last)) {
      $firms_fetch_next = $firms_fetch_last + $interval;
      $run = ((int) $request_time > $firms_fetch_next);
      if (!$run) {
        return [];
      }
    }
    else {
      $firms_fetch_next = $request_time;
    }
    $records = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    // Get records with changes since the last run.
    $format = 'Y-m-d H';
    $time = $firms_fetch_next - $interval;
    // @todo validate 1h behind issue on AM.net records.
    // For now just reduce current time in 1h.
    $time = $time - 3600;
    $since = date($format, $time) . ':00:00';
    $query = (!is_null($_client)) ? $_client->get('FirmChanges', ['since' => $since]) : FALSE;
    if (!$query->hasError()) {
      $records = $query->getResult();
      // Update the firms fetch last run time.
      $state->set($trigger_key, $request_time);
    }
    return $records;
  }

}
