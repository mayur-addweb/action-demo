<?php

namespace Drupal\am_net_cpe;

use Drupal\user\UserInterface;
use Drupal\Core\Url;

/**
 * AM.net Virtual Conference manager.
 *
 * @package Drupal\am_net_cpe
 */
class VirtualConferenceManager {

  use MyCpeTrait;

  /**
   * The AM.net CPE registration manager.
   *
   * @var \Drupal\am_net_cpe\CpeRegistrationManager
   */
  protected $cpeRegistrationManager;

  /**
   * Constructs a new RegistrationManager.
   *
   * @param \Drupal\am_net_cpe\CpeRegistrationManager $cpe_registration_manager
   *   The CPE registration manager.
   */
  public function __construct(CpeRegistrationManager $cpe_registration_manager) {
    $this->cpeRegistrationManager = $cpe_registration_manager;
  }

  /**
   * Load the load Digital Rewind Info.
   *
   * @param string $am_net_name_id
   *   An AM.net Name ID.
   * @param int $event_year
   *   An AM.net event year.
   * @param string $event_code
   *   An AM.net event code.
   *
   * @return array
   *   An array with the Virtual conference info.
   */
  public function loadVirtualConferenceInfoSingleSignOnDownTime($am_net_name_id = NULL, $event_year = NULL, $event_code = NULL) {
    if (empty($am_net_name_id)) {
      return FALSE;
    }
    $am_net_name_id = trim($am_net_name_id);
    if (empty($event_year) || empty($event_code)) {
      return FALSE;
    }
    // Get the event data cached  statically, or locally(state)
    // or in the worst case rebuilt the data(get it from AM.net).
    $event_registrations = $this->cpeRegistrationManager->getEventRegistrations($event_year, $event_code);
    if (!is_array($event_registrations)) {
      $event_registrations = [];
    }
    // Get the registrations.
    $registrations = $this->getVirtualConferenceEventRegistrations($event_year, $event_code, $am_net_name_id, $event_registrations, FALSE);
    // Return Result.
    return [
      'am_net_name_id' => $am_net_name_id,
      'registrations' => $registrations,
    ];
  }

  /**
   * Load the load Digital Rewind Info.
   *
   * @param \Drupal\user\UserInterface $user
   *   An AM.net event year.
   * @param int $event_year
   *   An AM.net event year.
   * @param string $event_code
   *   An AM.net event code.
   *
   * @return array
   *   An array with the Virtual conference info.
   */
  public function loadVirtualConferenceInfo(UserInterface $user = NULL, $event_year = NULL, $event_code = NULL) {
    if (!$user) {
      return FALSE;
    }
    $am_net_name_id = $user->get('field_amnet_id')->getString();
    if (empty($am_net_name_id)) {
      return FALSE;
    }
    $am_net_name_id = trim($am_net_name_id);
    if (empty($event_year) || empty($event_code)) {
      return FALSE;
    }
    // Get the event data cached  statically, or locally(state)
    // or in the worst case rebuilt the data(get it from AM.net).
    $event_registrations = $this->cpeRegistrationManager->getEventRegistrations($event_year, $event_code);
    if (!is_array($event_registrations)) {
      $event_registrations = [];
    }
    // Make sure that admin user can preview the event.
    $is_admin = $user->hasRole('administrator') || $user->hasRole('vscpa_administrator');
    $registrations = $this->getVirtualConferenceEventRegistrations($event_year, $event_code, $am_net_name_id, $event_registrations, $is_admin);
    // Return Result.
    return [
      'am_net_name_id' => $am_net_name_id,
      'registrations' => $registrations,
      'full_name' => $this->getUserFullName($user),
    ];
  }

  /**
   * Concatenate Items.
   *
   * @param string $a
   *   An first string.
   * @param string $b
   *   The second string.
   * @param string $separator
   *   The separator.
   *
   * @return string|NULL
   *   The concatenated string, otherwise NULL.
   */
  public function concatenateItems($a = NULL, $b = NULL, $separator = ' - ') {
    $items = [];
    if (!empty($a)) {
      $items[] = $a;
    }
    if (!empty($b)) {
      $items[] = $b;
    }
    if (empty($items)) {
      return NULL;
    }
    return implode($items, $separator);
  }

  /**
   * Overwrite Session Url(If Applies).
   *
   * @param int $event_year
   *   An AM.net event year.
   * @param string $event_code
   *   An AM.net event code.
   * @param string $session_code
   *   The session code.
   * @param string $session_url
   *   The session URL.
   *
   * @return array
   *   The new session URL.
   */
  public function overwriteSessionUrl($event_year = NULL, $event_code = NULL, $session_code = NULL, $session_url = NULL) {
    if ($event_code == '4-196W' && $event_year == '21') {
      if ($session_code == '1') {
        $session_url = 'https://zoom.us/j/97675343499';
      }
      elseif ($session_code == '2') {
        $session_url = 'https://zoom.us/j/91223810412';
      }
      elseif ($session_code == '3') {
        $session_url = 'https://zoom.us/j/93990503978';
      }
      elseif ($session_code == '4') {
        $session_url = 'https://zoom.us/j/99044844282';
      }
      elseif ($session_code == '5') {
        $session_url = 'https://zoom.us/j/96814578378';
      }
      elseif ($session_code == '6') {
        $session_url = 'https://zoom.us/j/95173259335';
      }
      elseif ($session_code == '7') {
        $session_url = 'https://zoom.us/j/96493264891';
      }
      elseif ($session_code == '8') {
        $session_url = 'https://zoom.us/j/93062400611';
      }
      elseif ($session_code == '9') {
        $session_url = 'https://zoom.us/j/99566012185';
      }
      elseif ($session_code == '10') {
        $session_url = 'https://zoom.us/j/91886372625';
      }
      elseif ($session_code == '11') {
        $session_url = 'https://zoom.us/j/94096100817';
      }
      elseif ($session_code == '12') {
        $session_url = 'https://zoom.us/j/94274215799';
      }
      elseif ($session_code == '13') {
        $session_url = 'https://zoom.us/j/95982848527';
      }
      elseif ($session_code == '14') {
        $session_url = 'https://zoom.us/j/98687720977';
      }
      elseif ($session_code == '15') {
        $session_url = 'https://zoom.us/j/96793458895';
      }
      elseif ($session_code == '16') {
        $session_url = 'https://zoom.us/j/97087696102';
      }
      elseif ($session_code == '17') {
        $session_url = 'https://zoom.us/j/92228349744';
      }
      elseif ($session_code == '18') {
        $session_url = 'https://zoom.us/j/92214751975';
      }
      elseif ($session_code == '19') {
        $session_url = 'https://zoom.us/j/97699604288';
      }
      elseif ($session_code == '20') {
        $session_url = 'https://zoom.us/j/97029785744';
      }
      elseif ($session_code == '21') {
        $session_url = 'https://zoom.us/j/98428524113';
      }
      elseif ($session_code == '22') {
        $session_url = 'https://zoom.us/j/91649264472';
      }
      elseif ($session_code == '23') {
        $session_url = 'https://zoom.us/j/99848121484';
      }
      elseif ($session_code == '24') {
        $session_url = 'https://zoom.us/j/97258116081';
      }
      elseif ($session_code == '25') {
        $session_url = 'https://zoom.us/j/95115264980';
      }
      elseif ($session_code == '26') {
        $session_url = 'https://zoom.us/j/92806405147';
      }
      elseif ($session_code == '27') {
        $session_url = 'https://zoom.us/j/93652131659';
      }
      elseif ($session_code == '28') {
        $session_url = 'https://zoom.us/j/95786800476';
      }
    }
    elseif ($event_code == '4-138W' && $event_year == '21') {
      if ($session_code == '20') {
        $session_url = 'https://zoom.us/rec/share/_ffZtf3DERF2a3zx7n4H40BGjm8n8trpJwAYyeLPhWYnu246awGoAGeDlNnYjI02.IuvLCb9UUhVcn186';
      }
      elseif ($session_code == '27') {
        $session_url = 'https://zoom.us/rec/share/kJ2sIKlR9F8uf0sc8nI94-V1sXCCf5LIIYuWr7lGtv0TaZBAPVL03RwIATuwMB6J.lnAZzk3uC_lISh3M';
      }
      elseif ($session_code == '7') {
        $session_url = 'https://zoom.us/rec/share/yl_1kf15NuDeYpzkQ0TbfY4HnjAvEp19sEFb74mwj-fqC2PPh0hW9RcWhF3kEbej.pkh-8-J92I8Aqo0I';
      }
    }
    elseif ($event_code == '4-181W' && $event_year == '21') {
      if ($session_code == '3') {
        $session_url = 'https://zoom.us/rec/share/eA7SreyypEr6xdVkw0BJNbH7dBg8I5M89hIqJY1y_bsExZcbgRM3H-yNK3u18RIM.P5wpQrq4_irhyR1P';
      }
      elseif ($session_code == '6') {
        $session_url = 'https://zoom.us/rec/share/vpiBzg4qAvt5kSxspii0nw5C3caiQABM2V0mtAJICZ7Uq7rsyEJciDjDcKLbjgxT.G9Ej8EaOr4LivZ8r';
      }
      elseif ($session_code == 'BON') {
        $session_url = 'https://zoom.us/j/91625165432?pwd=VVhLVCtFSnpUWEZUNFExSzRUWlJkUT09';
      }
    }
    return $session_url;
  }

  /**
   * Get 'Virtual Conference' Event Registrations.
   *
   * @param int $event_year
   *   An AM.net event year.
   * @param string $event_code
   *   An AM.net event code.
   * @param string $name_id
   *   An AM.net Name ID.
   * @param array $event_registrations
   *   The registrations array.
   * @param bool $is_admin
   *   The flag to preview as admin.
   *
   * @return array
   *   An array with the Virtual conference info.
   */
  public function getVirtualConferenceEventRegistrations($event_year = NULL, $event_code = NULL, $name_id = NULL, array $event_registrations = [], $is_admin = FALSE) {
    if (empty($event_registrations) || empty($name_id)) {
      return [];
    }
    if (empty($event_code) || empty($event_year)) {
      return [];
    }
    // Get the registration.
    $registration = $event_registrations[$name_id] ?? NULL;
    // Check for access.
    if (!$is_admin && (empty($registration))) {
      // The person is not registered and is not an admin.
      return [];
    }
    $registration_status_code = $registration['RegistrationStatusCode'] ?? 'good';
    if ($registration_status_code == 'C') {
      // The registration was canceled - Stop here.
      return [];
    }
    $data = [];
    $event_code = trim($event_code);
    $event_year = trim($event_year);
    $registration_date = $registration['RegistrationDate'] ?? "2020-04-15T00:00:00";
    $item = [
      'event_code' => $event_code,
      'event_year' => $event_year,
      'order_date' => $registration_date,
      'registration_date' => $registration_date,
      'registration_status_code' => $registration_status_code,
      'sessions' => [],
    ];
    $this->loadEventInfo($event_code, $event_year, $item, TRUE);
    if (empty($item)) {
      return [];
    }
    $em = $item['sessions_electronic_materials'] ?? [];
    // Load the session listing.
    $event_info = EventHelper::getCachedEventInfo($event_code, $event_year);
    $sessions = $event_info['Sessions'] ?? [];
    $redirect_url = 'https://onlinexperiences.com/Launch/Event.htm';
    $now = time();
    foreach ($sessions as $session) {
      $session_code = ($session['SessionCode'] ?? NULL);
      $exclude_items = ['EM1', 'SPM', 'SPE'];
      if (empty($session_code) || in_array($session_code, $exclude_items)) {
        continue;
      }
      $show_key = ($session['ExternalVendorCode1'] ?? NULL);
      $query = [
        'ShowKey' => $show_key,
        'v' => $now,
      ];
      $options = ['query' => $query];
      $url = Url::fromUri($redirect_url, $options)->toString();
      $url = $this->overwriteSessionUrl($event_year, $event_code, $session_code, $url);
      $being_time = $session['SessionTime'] ?? NULL;
      $session_timestamp = EventHelper::getDrupalSessionTime($session, 'America/New_York')->getTimestamp();
      $session_end_timestamp = EventHelper::getDrupalSessionTime($session, 'America/New_York', 'end')->getTimestamp();
      $session_date = ($session['SessionDate'] ?? 'NAN');
      $session_category = $session_date;
      if ($now >= $session_end_timestamp) {
        // It is a 'Previous Sessions'.
        $session_category = 'previous';
      }
      $end_time = $session['EndTime'] ?? NULL;
      $session_item = [
        'day' => ($session['Day'] ?? NULL),
        'session_time' => $being_time,
        'session_timestamp' => $session_timestamp,
        'end_time' => $end_time,
        'session_date' => ($session['SessionDate'] ?? NULL),
        'title' => ($session['Description'] ?? NULL),
        'description' => ($session['MarketingCopy'] ?? NULL),
        'session_code' => $session_code,
        'launch' => $url,
        'speakers' => $this->getDrupalSessionSpeakers($session),
        'electronic_materials' => ($em[$session_code] ?? NULL),
      ];
      $item['sessions'][] = $session_item;
      // Add Session to the group.
      $delta = $this->concatenateItems($being_time, $end_time);
      $date_time = strtotime($session_date);
      $date = date("D, F j, Y", $date_time);
      $date_short = date("F j", $date_time);
      $delta_timestamp = $session_timestamp;
      if (!isset($item['session_group'][$session_category]['sessions'][$delta_timestamp])) {
        $item['session_group'][$session_category]['sessions'][$delta_timestamp]['time'] = $delta;
        $item['session_group'][$session_category]['sessions'][$delta_timestamp]['date'] = $date_short;
        $item['session_group'][$session_category]['sessions'][$delta_timestamp]['items'][] = $session_item;
      }
      else {
        $item['session_group'][$session_category]['sessions'][$delta_timestamp]['items'][] = $session_item;
      }
      $item['session_group'][$session_category]['date'] = $date;
      ksort($item['session_group'][$session_category]['sessions']);
    }
    // Move 'Previous Sessions' to a different container.
    if (isset($item['session_group']['previous'])) {
      $item['previous_session_group'] = $item['session_group']['previous'];
      unset($item['session_group']['previous']);
    }
    // Sor the groups.
    ksort($item['session_group']);
    // Add the number of days.
    $count_session_group = count($item['session_group']);
    $event_default_dates = ['One', 'Two', 'Three'];
    $event_days = $item['event_days'] ?? 0;
    if (!empty($event_days)) {
      $remove_index = ($event_days - $count_session_group);
      if ($remove_index > 0) {
        for ($i = 1; $i <= $remove_index; $i++) {
          $index = $i - 1;
          unset($event_default_dates[$index]);
        }
      }
    }
    $item['days'] = array_values($event_default_dates);
    $data[] = $item;
    return $data;
  }

}
