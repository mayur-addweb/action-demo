<?php

namespace Drupal\am_net_cpe;

use Drupal\smart_trim\Truncate\TruncateHTML;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\user\UserInterface;
use Drupal\Core\Url;

/**
 * My CPE Helper trait implementation.
 */
trait MyCpeTrait {

  /**
   * {@inheritdoc}
   */
  public function getMyCpe(UserInterface $user = NULL, $include_expired_product = FALSE) {
    if (!$user) {
      return FALSE;
    }
    $am_net_name_id = $user->get('field_amnet_id')->getString();
    if (empty($am_net_name_id)) {
      return FALSE;
    }
    $am_net_name_id = trim($am_net_name_id);
    // Get User full name.
    $cpe_info = [
      'am_net_name_id' => $am_net_name_id,
      'on_demand' => [],
      'in_person' => [],
      'online' => [],
      'full_name' => $this->getUserFullName($user),
    ];
    // Get the event data cached  statically, or locally(state)
    // or in the worst case rebuilt the data(get it from AM.net).
    $data = $this->loadMyCpeInfo($am_net_name_id);
    // Set the data.
    $product_sales = $data['product_sales'] ?? [];
    if (!is_array($product_sales)) {
      $product_sales = [];
    }
    // Build On-demand section.
    $on_demand = $this->getOnDemandSales($product_sales, $include_expired_product);
    // Build Online section.
    $event_registrations = $data['event_registrations'] ?? [];
    if (!is_array($event_registrations)) {
      $event_registrations = [];
    }
    $info = $this->getInPersonAndOnlineRegistrations($event_registrations, $include_expired_product);
    $in_person = $info['in_person'];
    usort($in_person, [$this, 'dateCompare']);
    $cpe_info['in_person'] = $in_person;

    $online = $info['online'];
    usort($online, [$this, 'dateCompare']);
    $cpe_info['online'] = $online;

    $on_demand_events = $info['on_demand'] ?? [];
    if (!empty($on_demand_events)) {
      $on_demand = array_merge($on_demand, $on_demand_events);
    }
    usort($on_demand, [$this, 'dateCompare']);
    $cpe_info['on_demand'] = $on_demand;
    // Return CPE Info.
    return $cpe_info;
  }

  /**
   * {@inheritdoc}
   */
  public function dateCompare(array $a = [], array $b = []) {
    if (!isset($a['order_date']) || !isset($b['order_date'])) {
      return FALSE;
    }
    $v1 = strtotime($a['order_date']);
    $v2 = strtotime($b['order_date']);
    return $v1 - $v2;
  }

  /**
   * {@inheritdoc}
   */
  public function getInPersonAndOnlineRegistrations(array $registrations = [], $include_expired_product = FALSE) {
    $data = [
      'in_person' => [],
      'online' => [],
    ];
    if (empty($registrations)) {
      return $data;
    }
    $digital_rewind_events = $this->getDigitalRewindEventIndex();
    foreach ($registrations as $delta => $registration) {
      $event_code = $registration['EventCode'] ?? FALSE;
      $event_year = $registration['EventYear'] ?? FALSE;
      $registration_date = $registration['RegistrationDate'] ?? FALSE;
      $registration_status_code = $registration['RegistrationStatusCode'] ?? 'good';
      $registration_note = $registration['Note'] ?? FALSE;
      $zoom_join_url = $registration['ZoomJoinUrl'] ?? FALSE;
      if ($registration_status_code == 'C') {
        // The registration was canceled - Stop here.
        continue;
      }
      if (!empty($event_code) && !empty($event_year)) {
        $event_code = trim($event_code);
        $event_year = trim($event_year);
        $key = $event_code . '.' . $event_year;
        $item = [
          'event_code' => $event_code,
          'event_year' => $event_year,
          'order_date' => $registration_date,
          'registration_date' => $registration_date,
          'registration_status_code' => $registration_status_code,
          'is_digital_rewind' => isset($digital_rewind_events[$key]),
          'registration_note' => $registration_note,
          'zoom_join_url' => $zoom_join_url,
        ];
        $this->loadEventInfo($event_code, $event_year, $item, $include_expired_product);
        if (!empty($item) && isset($item['type'])) {
          if ($item['type'] == 'in_person') {
            $data['in_person'][] = $item;
          }
          elseif ($item['type'] == 'online') {
            $data['online'][] = $item;
          }
          elseif ($item['type'] == 'on_demand') {
            $data['on_demand'][] = $item;
          }
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getDigitalRewindEventIndex() {
    $events = \Drupal::state()->get('am_net_cpe.settings.digital.rewind.events', []);
    if (empty($events)) {
      return FALSE;
    }
    $items = [];
    foreach ($events as $delta => $event) {
      $code = $event['code'] ?? NULL;
      $year = $event['year'] ?? NULL;
      if (!empty($code) && !empty($year)) {
        $key = trim($code) . '.' . trim($year);
        $items[$key] = TRUE;
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalSessionSpeakers(array $record) {
    $leaders = $record['Leaders'] ?? [];
    if (empty($leaders)) {
      return [];
    }
    $person_manager = \Drupal::service('am_net.person_manager');
    $speakers = [];
    $name_ids = [];
    foreach ($leaders as $leader) {
      // Load names that haven't been loaded already.
      if (empty($name_ids[$leader['NamesId']])) {
        try {
          /* @var \Drupal\node\NodeInterface $person */
          $person = $person_manager->getDrupalPerson((int) $leader['NamesId']);
          $bio_body = $person->get('body')->getValue();
          $bio_text = is_array($bio_body) ? current($bio_body) : [];
          $bio = NULL;
          if (!empty($bio_text)) {
            $bio = [
              '#type' => 'processed_text',
              '#text' => $bio_text['value'] ?? NULL,
              '#format' => 'full_html',
            ];
          }
          $speakers[$person->id()] = [
            'name' => $person->getTitle(),
            'url' => $person->toUrl()->setAbsolute(TRUE),
            'bio' => $bio,
          ];
        }
        catch (\Exception $e) {
          continue;
        }
      }
      // Mark the name id as seen.
      $name_ids[$leader['NamesId']] = TRUE;
    }
    return $speakers;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMyCpeInfo($am_net_name_id = '') {
    if (empty($am_net_name_id)) {
      return FALSE;
    }
    $key = "am_net_cpe_my_cpe.{$am_net_name_id}";
    // Get the data cached statically.
    if (isset($this->storedSyncEntities[$key])) {
      return $this->storedSyncEntities[$key];
    }
    // Rebuilt data(get it from AM.net).
    $data = [
      'event_registrations' => [],
      'product_sales' => [],
    ];
    /* @var \Drupal\am_net\AssociationManagementClient $this->client */
    // Get the User's product sales info from the API.
    $response = $this->client->get("/Person/{$am_net_name_id}/productsales");
    if (!$response->hasError()) {
      $data['product_sales'] = $response->getResult();
    }
    // Get the User's event registration info from the API.
    $response = $this->client->get("/Person/{$am_net_name_id}/registrations");
    if (!$response->hasError()) {
      $data['event_registrations'] = $response->getResult();
    }
    // Update values on cache locally.
    $this->storedSyncEntities[$key] = $data;
    // Return Result.
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserFullName(UserInterface $user = NULL) {
    if (!$user) {
      return FALSE;
    }
    $suffix = $user->get('field_name_suffix')->getString();
    $first_name_or_initial = $user->get('field_givenname')->getString();
    $middle_name_or_initial = $user->get('field_additionalname')->getString();
    $last_name = $user->get('field_familyname')->getString();
    $names = [
      $suffix,
      $first_name_or_initial,
      $middle_name_or_initial,
      $last_name,
    ];
    return implode(' ', $names);
  }

  /**
   * {@inheritdoc}
   */
  public function getOnDemandSales(array $product_sales = [], $include_expired_product = FALSE) {
    if (empty($product_sales)) {
      return [];
    }
    // Build on-demand section.
    $added = [];
    $on_demand = [];
    foreach ($product_sales as $delta => $product_sale) {
      $items = $product_sale['Items'] ?? [];
      if (!empty($items)) {
        foreach ($items as $key => $item) {
          $product_code = $item['ProductCode'] ?? '';
          $order_date = $item['OrderDate'] ?? '';
          if (!empty($product_code)) {
            $on_demand_item = [
              'product_code' => $product_code,
              'order_date' => $order_date,
              'SelfStudyCompletion' => $item['SelfStudyCompletion'] ?? [],
            ];
            $this->loadProductInfo($product_code, $on_demand_item, $include_expired_product);
            if (!empty($on_demand_item)) {
              $key = $on_demand_item['product_id'] . '.' . $on_demand_item['product_code'];
              if (!isset($added[$key])) {
                $on_demand[] = $on_demand_item;
                $added[$key] = TRUE;
              }
            }
          }
        }
      }
    }
    return $on_demand;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEventInfo($event_code = '', $event_year = '', &$item = [], $include_expired_product = FALSE) {
    if (empty($event_code) || empty($event_year)) {
      return;
    }
    $info = $this->getDrupalEventInfo($event_code, $event_year);
    if (empty($info)) {
      $item = [];
      // Stop here.
      return;
    }
    // Product ID.
    $product_id = $info['product_id'];
    // Check Expire date.
    $event_dates = $this->getEventEndDate($product_id);
    $event_begin_date = $event_dates['begin_date'] ?? NULL;
    $date_original_timestamp1 = NULL;
    if (!empty($event_begin_date)) {
      // Ensure Timezone convertion.
      $date_original = new DrupalDateTime($event_begin_date, 'UTC');
      $date_original_timestamp1 = $date_original->getTimestamp();
      $event_begin_date = date('Y-m-d H:i:s', $date_original_timestamp1);
    }
    $event_end_date = $event_dates['end_date'] ?? NULL;
    $date_original_timestamp2 = NULL;
    if (!empty($event_end_date)) {
      // Ensure Timezone convertion.
      $date_original = new DrupalDateTime($event_end_date, 'UTC');
      $date_original_timestamp2 = $date_original->getTimestamp();
      $event_end_date = date('Y-m-d H:i:s', $date_original_timestamp2);
    }
    if ($date_original_timestamp1 && $date_original_timestamp2) {
      $item['event_days'] = ceil(abs($date_original_timestamp2 - $date_original_timestamp1) / 86400);
    }
    $item['expiry_date'] = NULL;
    $item['event_begin_date'] = $event_begin_date;
    $item['event_end_date'] = $event_end_date;
    $item['expiry_month'] = NULL;
    $item['expiry_day'] = NULL;
    $item['expiry_year'] = NULL;
    $item['show_launch_buttons'] = FALSE;
    $available_now = TRUE;
    $show_vc_launch_button = FALSE;
    if (!empty($event_begin_date) && !empty($event_end_date)) {
      $event_begin_date_datetime = strtotime($event_begin_date);
      $event_begin_end_datetime = strtotime($event_end_date);
      $current_datetime = strtotime('now');
      $item['expiry_month'] = date('M', $event_begin_date_datetime);
      $item['expiry_day'] = date('d', $event_begin_date_datetime);
      $item['expiry_year'] = date('Y', $event_begin_date_datetime);
      // Calculate if the event is closed.
      // Event is appears on MyCpe Block through 1 full day
      // after the event is over.
      $date_mind_night_date = date('Y-m-d 23:59:00', $event_begin_end_datetime);
      $date_mind_night_datetime = strtotime($date_mind_night_date);
      $event_expiry_datetime = strtotime('+1 day', $date_mind_night_datetime);
      $available_now = ($current_datetime < $event_expiry_datetime);
      $item['expiry_date'] = $event_expiry_datetime;
      // Launch buttons should ONLY appear 60 minutes prior to
      // a course's begin time.
      $sixty_mins_before_expiry_begin_ime = strtotime('-60 min', $event_begin_date_datetime);
      $item['show_launch_buttons'] = ($sixty_mins_before_expiry_begin_ime < $current_datetime) && ($current_datetime < $event_begin_end_datetime);
      // For virtual conference events "Launch" button should show up 5 days
      // before conference start time.
      $five_days_before_expiry_begin_time = strtotime('-5 days', $event_begin_date_datetime);
      $show_vc_launch_button = ($five_days_before_expiry_begin_time <= $current_datetime);
    }
    if (!$available_now && !$include_expired_product) {
      $item = [];
      return;
    }
    // Add item info.
    $item['product_id'] = $product_id;
    $item['title'] = $info['title'];
    $status = $info['status'] ?? FALSE;
    if ($status) {
      $item['url'] = Url::fromRoute('entity.commerce_product.canonical', ['commerce_product' => $product_id])->toString();
    }
    $summary = $info['body_summary'];
    if (empty($summary) && !empty($info['body_value'])) {
      $html = $info['body_value'];
      $limit = 260;
      $ellipsis = '...';
      $truncate = new TruncateHTML();
      $summary = $truncate->truncateChars($html, $limit, $ellipsis);
    }
    $item['summary'] = $summary;
    $division_value = $info['field_division_value'];
    $item['division'] = $division_value;
    $item['type'] = 'UND';
    // Determine the type.
    $location_target_id = '15345';
    $city_area = $info['field_city_area_target_id'];
    $city_is_not_online = !empty($city_area) && ($city_area != $location_target_id);
    if (am_net_is_self_study($division_value)) {
      // Individual on-demand Event.
      $item['type'] = 'on_demand';
    }
    elseif (($division_value == 'GROUP') && $city_is_not_online) {
      // 1. In-Person = events in AM.net where Division = “Group live” and/or
      // City is anything other than “Online”.
      $item['type'] = 'in_person';
    }
    elseif ($city_area == $location_target_id) {
      // 2. Online = events in AM.net where Location = "Online”.
      $item['type'] = 'online';
    }
    // Launch Link.
    $item['launch'] = $info['course_link'] ?? 'javascript:void(0)';
    // Load Electronic materials.
    $item['electronic_materials'] = $this->loadElectronicMaterials($product_id);
    $item['sessions_electronic_materials'] = $this->loadEventElectronicMaterials($product_id);
    // Load Product vendors.
    $vendors = $this->getProductVendors($product_id);
    // Check if is well-known vendor.
    $is_well_known_vendor = FALSE;
    $well_known_vendor = NULL;
    $vendor_name = '';
    if (!empty($vendors)) {
      foreach ($vendors as $delta => $vendor) {
        $vendor_name = $vendor['name'];
        if ($vendor['is_smartpros'] || $vendor['is_inxpo'] || $vendor['is_acpen'] || $vendor['is_zoom']) {
          $is_well_known_vendor = TRUE;
          $well_known_vendor = $vendor;
          // Stop here.
          break;
        }
      }
    }
    $item['vendor_name'] = $vendor_name;
    $item['is_well_known_vendor'] = $is_well_known_vendor;
    $item['vendors'] = $vendors;
    // Add Vendors Launch buttons.
    if ($is_well_known_vendor) {
      $item['launch'] = $this->getLaunchVendorUrl($item, $well_known_vendor);
    }
    // Check if the event is Self-Study Event.
    if ($item['type'] == 'on_demand') {
      $order_date = $item['order_date'];
      $item['company_code'] = $info['field_company_code_value'] ?? '';
      if (!empty($order_date)) {
        $order_date_time = strtotime($order_date);
        if ($item['company_code'] == 'ETHIC') {
          // Self-study ethics; it should ALWAYS expire on Jan. 31st at 11:59
          // p.m. of the following calendar year after the purchase is made.
          $order_year = date('Y', $order_date_time);
          // Next Year.
          $next_year = date('Y', $order_date_time) + 1;
          // Determine the fiscal year.
          if (strtotime("31 January {$order_year}") > $order_date_time) {
            $order_date_time_next_year = $order_year;
          }
          else {
            $order_date_time_next_year = $next_year;
          }
          $expiration_date = "{$order_date_time_next_year}-01-31 23:59:00";
          $expiration_date_time = strtotime($expiration_date);
          $available = ($current_datetime < $expiration_date_time);
          $expiration_date_year = $order_date_time_next_year;
          $expiration_date_month = 'Jan';
          $expiration_date_day = '31';
        }
        else {
          // ALL products expire 365 days from the date a user purchased the
          // product. Ex: I purchase a product on June 12, 2018.
          // I should be allowed to complete the course all the way up until
          // midnight on June 13, 2019.
          // Get the current date time.
          $expiration_date_time = strtotime('+1 year', $order_date_time);
          $available = ($current_datetime < $expiration_date_time);
          $expiration_date_year = date('Y', $expiration_date_time);
          $expiration_date_month = date('M', $expiration_date_time);
          $expiration_date_day = date('d', $expiration_date_time);
        }
        $item['available'] = $available;
        $item['expiration_date_month'] = $expiration_date_month;
        $item['expiration_date_day'] = $expiration_date_day;
        $item['expiration_date_year'] = $expiration_date_year;
      }
    }
    // Check if Exits any "virtual conference" associated to this event.
    $nid = EventHelper::getVirtualConferenceIdByEventId($event_code, $event_year, TRUE);
    if (!empty($nid)) {
      $item['show_launch_buttons'] = $show_vc_launch_button;
      $item['is_well_known_vendor'] = TRUE;
      $item['is_vc'] = TRUE;
      $options = ['absolute' => TRUE];
      $item['launch'] = Url::fromRoute('entity.node.canonical', ['node' => $nid], $options);
    }
    else {
      // Maybe has a "virtual conference" associated but it is unpublished.
      $nid = EventHelper::getVirtualConferenceIdByEventId($event_code, $event_year);
      $item['is_vc'] = !empty($nid);
    }
    if ($event_code == '7-999' && $event_year == '21') {
      $item['show_launch_buttons'] = TRUE;
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadProductInfo($product_code = '', &$item = [], $include_expired_product = FALSE) {
    if (empty($product_code)) {
      return;
    }
    $info = $this->getDrupalSelfStudyInfo($product_code);
    if (empty($info)) {
      $item = [];
      // Stop here.
      return;
    }
    // Check Self Study Completion Date.
    $self_study_completion = $item['SelfStudyCompletion'];
    $current_datetime = strtotime('now');
    if (!empty($self_study_completion) && !$include_expired_product) {
      $completion_date_time = strtotime($self_study_completion);
      if ($current_datetime >= $completion_date_time) {
        $item = [];
        // Stop here.
        return;
      }
    }
    $product_id = $info['product_id'];
    $item['product_id'] = $product_id;
    $item['title'] = $info['title'];
    $item['url'] = Url::fromRoute('entity.commerce_product.canonical', ['commerce_product' => $product_id])->toString();
    $summary = $info['body_summary'];
    if (empty($summary) && !empty($info['body_value'])) {
      $html = $info['body_value'];
      $limit = 260;
      $ellipsis = '...';
      $truncate = new TruncateHTML();
      $summary = $truncate->truncateChars($html, $limit, $ellipsis);
    }
    $item['summary'] = $summary;
    $item['division'] = $info['field_division_value'] ?? '';
    $item['company_code'] = $info['field_company_code_value'] ?? '';
    $available = TRUE;
    $order_date = $item['order_date'];
    $expiration_date_year = '';
    $expiration_date_month = '';
    $expiration_date_day = '';
    if (!empty($order_date)) {
      $order_date_time = strtotime($order_date);
      // Determine Expiration date.
      if ($item['company_code'] == 'ETHIC') {
        // Self-study ethics; it should ALWAYS expire on Jan. 31st at 11:59 p.m.
        // of the following calendar year after the purchase is made.
        $order_year = date('Y', $order_date_time);
        // Next Year.
        $next_year = date('Y', $order_date_time) + 1;
        // Determine the fiscal year.
        if (strtotime("31 January {$order_year}") > $order_date_time) {
          $order_date_time_next_year = $order_year;
        }
        else {
          $order_date_time_next_year = $next_year;
        }
        $expiration_date = "{$order_date_time_next_year}-01-31 23:59:00";
        $expiration_date_time = strtotime($expiration_date);
        $available = ($current_datetime < $expiration_date_time);
        $expiration_date_year = $order_date_time_next_year;
        $expiration_date_month = 'Jan';
        $expiration_date_day = '31';
      }
      else {
        // ALL products expire 365 days from the date a user purchased the
        // product. Ex: I purchase a product on June 12, 2018.
        // I should be allowed to complete the course all the way up until
        // midnight on June 13, 2019.
        // Get the current date time.
        $expiration_date_time = strtotime('+1 year', $order_date_time);
        $available = ($current_datetime < $expiration_date_time);
        $expiration_date_year = date('Y', $expiration_date_time);
        $expiration_date_month = date('M', $expiration_date_time);
        $expiration_date_day = date('d', $expiration_date_time);
      }
    }
    $item['available'] = $available;
    $item['expiration_date_month'] = $expiration_date_month;
    $item['expiration_date_day'] = $expiration_date_day;
    $item['expiration_date_year'] = $expiration_date_year;
    // Launch Link.
    $item['launch'] = $info['course_link'] ?? '';
    // Load Electronic materials.
    $item['electronic_materials'] = $this->loadElectronicMaterials($product_id);
    // Load Product vendors.
    $vendors = $this->getProductVendors($product_id);
    // Check if is well-known vendor.
    $is_well_known_vendor = FALSE;
    $well_known_vendor = NULL;
    $vendor_name = '';
    if (!empty($vendors)) {
      foreach ($vendors as $delta => $vendor) {
        $vendor_name = $vendor['name'];
        if ($vendor['is_smartpros'] || $vendor['is_inxpo'] || $vendor['is_acpen'] || $vendor['is_zoom']) {
          $is_well_known_vendor = TRUE;
          $well_known_vendor = $vendor;
          // Stop here.
          break;
        }
      }
    }
    $item['vendor_name'] = $vendor_name;
    $item['is_well_known_vendor'] = $is_well_known_vendor;
    $item['vendors'] = $vendors;
    // Add Vendors Launch buttons.
    if ($is_well_known_vendor) {
      $item['launch'] = $this->getLaunchVendorUrl($item, $well_known_vendor);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalSelfStudyInfo($product_code = '') {
    if (empty($product_code)) {
      return FALSE;
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product_field_data', 'product');
    $query->fields('product', ['product_id', 'title', 'status']);
    $query->leftJoin('commerce_product__field_course_prodcode', 'prodcode', 'prodcode.entity_id = product.product_id');
    $query->leftJoin('commerce_product__body', 'prodbody', 'prodbody.entity_id = product.product_id');
    $query->leftJoin('commerce_product__field_division', 'proddivision', 'proddivision.entity_id = product.product_id');
    $query->leftJoin('commerce_product__field_company_code', 'prodcompany_code', 'prodcompany_code.entity_id = product.product_id');
    $query->leftJoin('commerce_product__field_course_link', 'course_link', 'course_link.entity_id = product.product_id');
    $query->fields('prodbody', ['body_value', 'body_summary']);
    $query->fields('proddivision', ['field_division_value']);
    $query->fields('prodcompany_code', ['field_company_code_value']);
    $query->fields('course_link', ['field_course_link_uri']);
    $query->condition('prodcode.field_course_prodcode_value', $product_code);
    $result = $query->execute();
    return $result->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalEventInfo($event_code = '', $event_year = '') {
    if (empty($event_code) || empty($event_year)) {
      return FALSE;
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product_field_data', 'product');
    $query->fields('product', ['product_id', 'title', 'status']);
    $query->leftJoin('commerce_product__field_amnet_event_id', 'amnet_event', 'amnet_event.entity_id = product.product_id');
    $query->leftJoin('commerce_product__body', 'prodbody', 'prodbody.entity_id = product.product_id');
    $query->leftJoin('commerce_product__field_city_area', 'prod_city_area', 'prod_city_area.entity_id = product.product_id');
    $query->leftJoin('commerce_product__field_division', 'proddivision', 'proddivision.entity_id = product.product_id');
    $query->leftJoin('commerce_product__field_event_expiry', 'event_expiry', 'event_expiry.entity_id = product.product_id');
    $query->leftJoin('commerce_product__field_course_link', 'course_link', 'course_link.entity_id = product.product_id');
    $query->leftJoin('commerce_product__field_company_code', 'prodcompany_code', 'prodcompany_code.entity_id = product.product_id');
    $query->fields('prodbody', ['body_value', 'body_summary']);
    $query->fields('proddivision', ['field_division_value']);
    $query->fields('prod_city_area', ['field_city_area_target_id']);
    $query->fields('event_expiry', ['field_event_expiry_value']);
    $query->fields('course_link', ['field_course_link_uri']);
    $query->fields('prodcompany_code', ['field_company_code_value']);
    $query->condition('amnet_event.field_amnet_event_id_code', $event_code);
    $query->condition('amnet_event.field_amnet_event_id_year', $event_year);
    $result = $query->execute();
    return $result->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function getEventEndDate($product_id = '') {
    if (empty($product_id)) {
      return NULL;
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_dates_times', 'dates_times');
    $query->fields('dates_times', [
      'field_dates_times_value',
      'field_dates_times_end_value',
    ]);
    $query->condition('dates_times.entity_id', $product_id);
    $query->orderBy('dates_times.delta', $direction = 'ASC');
    $result = $query->execute();
    $items = $result->fetchAll();
    if (empty($items)) {
      return NULL;
    }
    $stat_date = NULL;
    $end_date = NULL;
    foreach ($items as $delta => $item) {
      // Set Start date.
      if (isset($item->field_dates_times_value) && empty($stat_date)) {
        $stat_date = $item->field_dates_times_value;
      }
      // Set Event date.
      if (isset($item->field_dates_times_end_value) && !empty($item->field_dates_times_end_value)) {
        $end_date = $item->field_dates_times_end_value;
      }
    }
    if (empty($end_date)) {
      $end_date = $stat_date;
    }
    return [
      'begin_date' => $stat_date,
      'end_date' => $end_date,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLaunchVendorUrl($item = [], $vendor = []) {
    if (empty($vendor) || empty($item)) {
      return NULL;
    }
    $url = NULL;
    $route_name = NULL;
    $params = [];
    $query = [];
    $field_keys = [
      'product_code',
      'event_year',
      'event_code',
      'product_id',
    ];
    foreach ($field_keys as $delta => $field_key) {
      if (isset($item[$field_key])) {
        $query[$field_key] = $item[$field_key];
      }
    }
    $options = ['query' => $query];
    if ($vendor['is_smartpros']) {
      $route_name = 'am_net_cpe.mycpe.smartpros';
      $url = Url::fromRoute($route_name, $params, $options)->toString();
    }
    elseif ($vendor['is_inxpo']) {
      $url = $this->getLaunchInxpoUrl($query);
    }
    elseif ($vendor['is_acpen']) {
      $redirect_url = 'https://vscpa.acpen.com/Glue/LoginToSSO';
      $url = Url::fromUri($redirect_url, $params)->toString();
    }
    elseif ($vendor['is_zoom']) {
      $redirect_url = $item['zoom_join_url'] ?? FALSE;
      $is_valid_join_url = !empty($redirect_url) && filter_var($redirect_url, FILTER_VALIDATE_URL);
      if (!$is_valid_join_url) {
        // Try with the registration note.
        $redirect_url = $item['registration_note'] ?? FALSE;
        $is_valid_join_url = !empty($redirect_url) && filter_var($redirect_url, FILTER_VALIDATE_URL);
      }
      if ($is_valid_join_url) {
        $url = Url::fromUri($redirect_url, $params)->toString();
      }
    }
    return $url;
  }

  /**
   * Redirect to the Login or INXPO course access.
   *
   * @param array $query
   *   The query array.
   *
   * @return string
   *   The INXPO redirect url.
   */
  public function getLaunchInxpoUrl(array $query = []) {
    $product_code = $query['product_code'] ?? NULL;
    $event_year = $query['event_year'] ?? NULL;
    $event_code = $query['event_code'] ?? NULL;
    $product_id = $query['product_id'] ?? NULL;
    $is_product = !empty($product_code);
    $is_event = !empty($event_year) && !empty($event_code);
    if (!$is_event && !$is_product) {
      return NULL;
    }
    $redirect_url = 'https://onlinexperiences.com/Launch/Event.htm';
    $external_product_codes = $this->getExternalProductCodes($product_id, $is_event);
    if (empty($external_product_codes)) {
      return NULL;
    }
    // Get the Login Show Key.
    $show_key = current($external_product_codes);
    $query = [
      'ShowKey' => $show_key,
      'v' => time(),
    ];
    $options = ['query' => $query];
    $url = Url::fromUri($redirect_url, $options)->toString();
    return $url;
  }

  /**
   * Get External Product Codes.
   *
   * @param string $product_id
   *   The Drupal product id.
   * @param bool $is_event
   *   The flag: is event.
   *
   * @return array|null
   *   The array of Product coded, otherwise NULL.
   */
  public function getExternalProductCodes($product_id = '', $is_event = FALSE) {
    if (empty($product_id)) {
      return [];
    }
    $database = \Drupal::database();
    if ($is_event) {
      $query = $database->select('commerce_product__field_external_event_codes', 'event_codes');
      $query->fields('event_codes', ['field_external_event_codes_value']);
      $query->condition('event_codes.entity_id', $product_id);
    }
    else {
      $query = $database->select('commerce_product__field_external_product_codes', 'product_codes');
      $query->fields('product_codes', ['field_external_product_codes_value']);
      $query->condition('product_codes.entity_id', $product_id);
    }
    $result = $query->execute();
    return $result->fetchAllKeyed(0, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function loadElectronicMaterials($product_id = '') {
    if (empty($product_id)) {
      return [];
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_electronic_material', 'electronic_material');
    $query->fields('electronic_material', ['field_electronic_material_target_id']);
    $query->condition('electronic_material.entity_id', $product_id);
    $result = $query->execute();
    $materials = $result->fetchAllKeyed(0, 0);
    if (empty($materials)) {
      // Stop here.
      return [];
    }
    $entity_type = 'media';
    $view_mode = 'full';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    // Render the Links.
    $electronic_materials = [];
    foreach ($materials as $material => $fid) {
      $media = $storage->load($fid);
      $build = $view_builder->view($media, $view_mode);
      $electronic_materials[] = render($build);
    }
    return $electronic_materials;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEventElectronicMaterials($product_id = '') {
    if (empty($product_id)) {
      return [];
    }
    $database = \Drupal::database();
    $query = $database->select('event_session__field_session_cpe_parent', 'session_cpe_parent');
    $query->condition('session_cpe_parent.field_session_cpe_parent_target_id', $product_id);
    $query->leftJoin('event_session__field_session_code', 'session_code', 'session_code.entity_id = session_cpe_parent.entity_id');
    $query->leftJoin('event_session__field_electronic_materials', 'electronic_material', 'electronic_material.entity_id = session_cpe_parent.entity_id');
    $query->fields('electronic_material', ['field_electronic_materials_target_id']);
    $query->fields('session_code', ['field_session_code_value', 'entity_id']);
    $result = $query->execute();
    $materials = $result->fetchAll();
    if (empty($materials)) {
      // Stop here.
      return [];
    }
    $entity_type = 'media';
    $view_mode = 'full';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    // Render the Links.
    $electronic_materials = [];
    foreach ($materials as $material) {
      $fid = $material->field_electronic_materials_target_id ?? NULL;
      $session_code = $material->field_session_code_value ?? NULL;
      if (empty($fid)) {
        continue;
      }
      $media = $storage->load($fid);
      $build = $view_builder->view($media, $view_mode);
      $electronic_materials[$session_code][] = render($build);
    }
    return $electronic_materials;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductVendors($product_id = '') {
    if (empty($product_id)) {
      return [];
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_course_vendors', 'vendors');
    $query->fields('vendors', ['field_course_vendors_target_id']);
    $query->leftJoin('taxonomy_term_field_data', 'term', 'term.tid = vendors.field_course_vendors_target_id');
    $query->fields('term', ['name']);
    $query->condition('vendors.entity_id', $product_id);
    $result = $query->execute();
    $vendors = $result->fetchAllKeyed(0, 1);
    if (empty($vendors)) {
      // Stop here.
      return [];
    }
    // Format Vendors.
    $product_vendors = [];
    foreach ($vendors as $vendor_id => $vendor_name) {
      $is_acpen = in_array($vendor_id, [
        AM_NET_CPE_ACPEN,
        AM_NET_CPE_ACPEN_PRODUCT,
      ]);
      $is_zoom = in_array($vendor_id, [
        AM_NET_CPE_ZOOM,
        AM_NET_CPE_ZOOM2,
        AM_NET_CPE_ZOOM3,
        AM_NET_CPE_ZOOM4,
      ]);
      $item = [
        'name' => $vendor_name,
        'is_smartpros' => ($vendor_id == AM_NET_CPE_SMARTPROS),
        'is_inxpo' => ($vendor_id == AM_NET_CPE_INXPO),
        'is_acpen' => $is_acpen,
        'is_zoom' => $is_zoom,
      ];
      $product_vendors[$vendor_id] = $item;
    }
    return $product_vendors;
  }

}
