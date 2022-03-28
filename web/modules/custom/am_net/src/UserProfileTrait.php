<?php

namespace Drupal\am_net;

use Drupal\am_net_user_profile\Entity\Person;

/**
 * AM.net User Profile Helper trait implementation.
 */
trait UserProfileTrait {

  /**
   * Get names changed recently from AM.net.
   *
   * @return array
   *   List of AM.net User Profiles IDs.
   */
  public function getNamesChangedRecentlyFromAmNet() {
    $interval = AM_NET_RECORDS_FETCH_INTERVAL;
    if (!($interval > 0)) {
      return [];
    }
    $trigger_key = 'am_net_triggers.names_fetch_last';
    $state = \Drupal::state();
    $request_time = \Drupal::time()->getRequestTime();
    $names_fetch_last = $state->get($trigger_key);
    if (is_numeric($names_fetch_last)) {
      $names_fetch_next = $names_fetch_last + $interval;
      $run = ((int) $request_time > $names_fetch_next);
      if (!$run) {
        return [];
      }
    }
    else {
      $names_fetch_next = $request_time;
    }
    $records = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    // Get records with changes since the last run.
    $format = 'Y-m-d H';
    $time = $names_fetch_next - $interval;
    // @todo validate 1h behind issue on AM.net records.
    // For now just reduce current time in 1h.
    $time = $time - 3600;
    $since = date($format, $time) . ':00:00';
    $query = (!is_null($_client)) ? $_client->get('PersonChanges', ['since' => $since]) : FALSE;
    if (!$query->hasError()) {
      $records = $query->getResult();
      // Update the names fetch last run time.
      $state->set($trigger_key, $request_time);
    }
    return $records;
  }

  /**
   * Get names merged recently from AM.net.
   *
   * @return array
   *   List of AM.net Names Ids merged on AM.net.
   */
  public function getNamesMergesRecentlyFromAmNet() {
    $interval = AM_NET_RECORDS_FETCH_INTERVAL;
    if (!($interval > 0)) {
      return [];
    }
    $trigger_key = 'am_net_triggers.names_merges_fetch';
    $state = \Drupal::state();
    $request_time = \Drupal::time()->getRequestTime();
    $names_fetch_last = $state->get($trigger_key);
    if (is_numeric($names_fetch_last)) {
      $names_fetch_next = $names_fetch_last + $interval;
      $run = ((int) $request_time > $names_fetch_next);
      if (!$run) {
        return [];
      }
    }
    else {
      $names_fetch_next = $request_time;
    }
    $records = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    // Get records with changes since the last run.
    $format = 'Y-m-d H';
    $time = $names_fetch_next - $interval;
    // For now just reduce current time in 1h.
    $time = $time - 3600;
    $since = date($format, $time) . ':00:00';
    $query = (!is_null($_client)) ? $_client->get('Person/merges', ['since' => $since]) : FALSE;
    if (!$query->hasError()) {
      $records = $query->getResult();
      // Update the names fetch last run time.
      $state->set($trigger_key, $request_time);
    }
    return $records;
  }

  /**
   * Fetches all AM.net Firm Codes.
   *
   * @return array
   *   List of AM.net User Profiles IDs.
   */
  public function getAllUserProfiles() {
    $entities = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    $query = (!is_null($_client)) ? $_client->get(Person::getApiEndPoint(), ['all' => 'true']) : FALSE;
    if (!$query->hasError()) {
      $entities = $query->getResult();
    }
    return $entities;
  }

  /**
   * Fetches a AM.net User Profiles object by Date.
   *
   * @param string $date
   *   The given date since.
   *
   * @return array|bool
   *   List of AM.net User Profiles IDs.
   */
  public function getAllUserProfilesByDate($date) {
    $entities = [];
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    $query = (!is_null($_client)) ? $_client->get(Person::getApiEndPoint(), ['since' => $date]) : FALSE;
    if (!$query->hasError()) {
      $entities = $query->getResult();
    }
    return $entities;
  }

  /**
   * Search a AM.net person object by email address.
   *
   * @param string $mail
   *   String with the account's email address.
   *
   * @return string|bool
   *   The PersonID when the person was found, otherwise FALSE.
   */
  public function loadPersonIdByEmail($mail = '') {
    if (empty($mail) || !(\Drupal::service('email.validator')->isValid($mail))) {
      return FALSE;
    }
    $person_id = FALSE;
    $_client = $this->getClient();
    $query = (!is_null($_client)) ? $_client->get(Person::getLoadByPropertiesApiEndpoint(), ['email' => $mail]) : FALSE;
    if ($query && !$query->hasError()) {
      $result = $query->getResult();
      if (isset($result['PersonID']) && !empty($result['PersonID'])) {
        $person_id = $result['PersonID'];
      }
      if (isset($result['ResultCount']) && !empty($result['ResultCount']) && ($result['ResultCount'] > 1)) {
        // Logs an error.
        $error = t('More than one namerecord was found with the email: @email. Result Count: @resultcount.', ['@email' => $mail, '@resultcount' => $result['ResultCount']]);
        \Drupal::logger('am_net_user_profile')->error($error);
      }
    }
    return $person_id;
  }

}
