<?php

namespace Drupal\am_net_cpe;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\am_net\AMNetEntityTypesInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\am_net\AMNetEntityTypeContext;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce_price\Price;
use Drupal\node\Entity\Node;

/**
 * The AM.net Events Helper.
 *
 * @package Drupal\am_net_cpe
 */
class EventHelper {

  /**
   * Multi-Event update parent keywords.
   *
   * @param string $acronym
   *   The event acronym.
   * @param string $year
   *   The event year.
   *
   * @return bool
   *   TRUE of the event represents a parent event, otherwise FALSE.
   */
  public static function multiEventUpdateParentKeywords($acronym = NULL, $year = NULL) {
    if (empty($acronym) || empty($year)) {
      return FALSE;
    }
    $events = self::getGroupedEventsByAcronym($acronym, $year);
    if (empty($events)) {
      // There are not other event with the same acronym in the database,
      // this is the first one, and therefore it is the parent one.
      return TRUE;
    }
    $id = NULL;
    $keywords = [];
    foreach ($events as $product_id => $event) {
      $excluded = (bool) $event->exclude;
      $published = (bool) $event->published;
      $code = $event->code;
      if (!$excluded && $published) {
        $id = $product_id;
      }
      else {
        $keywords[] = $code;
      }
    }
    if (empty($id)) {
      return FALSE;
    }
    $keywords[] = $acronym;
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::load($id);
    // Update the Keyword field.
    $field_name = 'field_search_keywords';
    $current_values = $product->get($field_name)->getString();
    $delimiter = ' | ';
    $pieces = explode($delimiter, $current_values);
    $current_value = current($pieces);
    $field_value = $current_value . $delimiter . implode(' ', $keywords);
    $product->set($field_name, $field_value);
    // Save the changes.
    try {
      $product->save();
    }
    catch (EntityStorageException $e) {
      return FALSE;
    }
    // Process completed.
    return TRUE;
  }

  /**
   * Check if a given event represents the parent event from grouped events.
   *
   * @param string $acronym
   *   The event acronym.
   * @param string $code
   *   The event code.
   * @param string $year
   *   The event year.
   *
   * @return bool
   *   TRUE of the event represents a parent event, otherwise FALSE.
   */
  public static function isParentEventByAcronym($acronym = NULL, $code = NULL, $year = NULL) {
    if (empty($acronym) || empty($code) || empty($year)) {
      return FALSE;
    }
    $events = self::getGroupedEventsByAcronym($acronym, $year);
    if (empty($events)) {
      // There are not other event with the same acronym in the database,
      // this is the first one, and therefore it is the parent one.
      return TRUE;
    }
    // The parent event should not be excluded and only one event should not
    // be excluded(the parent).
    $all_excluded = TRUE;
    foreach ($events as $product_id => $event) {
      $excluded = (bool) $event->exclude;
      if (!$excluded && ($code == $event->code) && ($year == $event->year)) {
        // This is the parent event.
        return TRUE;
      }
      if (!$excluded) {
        $all_excluded = FALSE;
      }
    }
    if ($all_excluded) {
      // Since that at least one event of the group should not be exclude, we
      // can select the current one as the parent.
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get grouped events by Acronym that are Open for Registration.
   *
   * @param string $acronym
   *   The event acronym.
   * @param string $year
   *   The event year.
   *
   * @return array
   *   One array with the event IDs.
   */
  public static function getGroupedEventIdsOpenForRegistration($acronym = NULL, $year = NULL) {
    if (empty($acronym)) {
      return [];
    }
    $now = new DrupalDateTime('now');
    $expiry_date = $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $events = self::getGroupedEventsByAcronym($acronym, $year, $expiry_date);
    if (empty($events)) {
      return [];
    }
    return array_keys($events);
  }

  /**
   * Get grouped events by Acronym that are Open for Registration.
   *
   * @param string $acronym
   *   The event acronym.
   * @param string $year
   *   The event year.
   *
   * @return array
   *   One array with the event IDs.
   */
  public static function getGroupedEventsOpenForRegistration($acronym = NULL, $year = NULL) {
    $ids = self::getGroupedEventIdsOpenForRegistration($acronym, $year);
    if (empty($ids)) {
      return [];
    }
    return Product::loadMultiple($ids);
  }

  /**
   * Check if the Acronym is excluded.
   *
   * @param string $acronym
   *   The event acronym.
   *
   * @return bool
   *   TRUE if the given acronym is excluded, otherwise FALSE.
   */
  public static function isExcludedAcronym($acronym = NULL) {
    if (empty($acronym)) {
      return NULL;
    }
    $acronym = trim($acronym);
    $items = \Drupal::state()->get('am_net_cpe.multi_event.excluded.acronyms');
    if (empty($items)) {
      return FALSE;
    }
    foreach ($items as $item) {
      $code = $item['acronym'] ?? NULL;
      if ($code == $acronym) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets Events AM.net IDs.
   *
   * @param string $product_id
   *   The CPE events ID.
   *
   * @return array|bool
   *   The array list of events codes, otherwise FALSE.
   */
  public static function getEventAmNetIds($product_id = NULL) {
    if (empty($product_id)) {
      return FALSE;
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_amnet_event_id', 'events');
    $query->condition('entity_id', $product_id);
    $fields = [
      'field_amnet_event_id_code',
      'field_amnet_event_id_year',
    ];
    $query->fields('events', $fields);
    $result = $query->execute();
    $items = $result->fetchAll();
    if (empty($items)) {
      return FALSE;
    }
    $item = current($items);
    $code = $item->field_amnet_event_id_code ?? NULL;
    $year = $item->field_amnet_event_id_year ?? NULL;
    if (empty($code) || empty($year)) {
      return FALSE;
    }
    return [
      'code' => $code,
      'year' => $year,
    ];
  }

  /**
   * Get grouped events by Acronym.
   *
   * @param string $acronym
   *   The event acronym.
   * @param string $year
   *   The event year.
   * @param string $expiry_date
   *   The event expire date.
   *
   * @return array|null
   *   One array with the list of event that make the group.
   */
  public static function getGroupedEventsByAcronym($acronym = NULL, $year = NULL, $expiry_date = NULL) {
    if (empty($acronym)) {
      return NULL;
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_acronym', 'acro');
    // Add condition.
    $query->condition('acro.field_acronym_value', $acronym);
    // Add field: Product ID.
    $query->addField('acro', 'entity_id', 'product_id');
    // Add Field: Acronym.
    $query->addField('acro', 'field_acronym_value', 'acronym');
    // Add Fields: Event ID.
    $query->leftJoin('commerce_product__field_amnet_event_id', 'event_id', 'event_id.entity_id = acro.entity_id');
    $query->addField('event_id', 'field_amnet_event_id_code', 'code');
    $query->addField('event_id', 'field_amnet_event_id_year', 'year');
    // Filter by year(if applies).
    if (!empty($year)) {
      $query->condition('event_id.field_amnet_event_id_year', $year);
    }
    // Add Field: Start date.
    $query->leftJoin('commerce_product__field_event_expiry', 'event_expiry', 'event_expiry.entity_id = acro.entity_id');
    $query->addField('event_expiry', 'field_event_expiry_value', 'start_date');
    // Filter by expire date(if applies).
    $and_group = $query->andConditionGroup();
    if (!empty($expiry_date)) {
      $and_group->condition('event_expiry.field_event_expiry_value', $expiry_date, '>=');
    }
    // Exclude past events from the group.
    $now = new DrupalDateTime('now');
    $now_date = $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    // drush_print("now_date: $now_date | expiry_date: $expiry_date.");
    $and_group->condition('event_expiry.field_event_expiry_value', $now_date, '>=');
    $query->condition($and_group);
    // Add Field: Excluded.
    $query->leftJoin('commerce_product__field_exclude_from_web_catalog', 'exclude', 'exclude.entity_id = acro.entity_id');
    $query->addField('exclude', 'field_exclude_from_web_catalog_value', 'exclude');
    // Add Field: Published.
    $query->leftJoin('commerce_product_field_data', 'product_field_data', 'product_field_data.product_id = acro.entity_id');
    $query->addField('product_field_data', 'status', 'published');
    // Sort By Start date.
    $query->orderBy('event_expiry.field_event_expiry_value', 'ASC');
    // Execute.
    $result = $query->execute();
    // Return the result Keyed by 'product_id'.
    return $result->fetchAllAssoc('product_id');
  }

  /**
   * Get event expire dates.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The event entity.
   *
   * @return string|null
   *   The event expire date, otherwise NULL.
   */
  public static function getEventExpireDate(ProductInterface $product = NULL) {
    if (!$product) {
      return NULL;
    }
    $value = $product->get('field_event_expiry')->getValue();
    $value = is_array($value) ? current($value) : NULL;
    return $value['value'] ?? NULL;
  }

  /**
   * Get event group expire dates.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The event entity.
   *
   * @return mixed
   *   The list of expire dates of the event, otherwise NULL.
   */
  public static function getEventGroupExpireDates(ProductInterface $product = NULL) {
    if (!$product) {
      return NULL;
    }
    $field_name = 'field_acronym';
    $field = $product->get($field_name);
    if ($field->isEmpty()) {
      return self::getEventExpireDate($product);
    }
    $acronym = $field->getString();
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_acronym', 'acro');
    $query->fields('event_expiry', ['field_event_expiry_value']);
    $query->leftJoin('commerce_product__field_event_expiry', 'event_expiry', 'event_expiry.entity_id = acro.entity_id');
    $query->condition('acro.field_acronym_value', $acronym);
    $query->orderBy('event_expiry.field_event_expiry_value', 'ASC');
    $result = $query->execute();
    $values = $result->fetchCol();
    if (empty($values)) {
      return NULL;
    }
    return end($values);
  }

  /**
   * Clear event registration cache.
   *
   * @param string $event_code
   *   The event code.
   * @param string $event_year
   *   The event year.
   *
   * @return bool
   *   TRUE if the operation was completed, otherwise false.
   */
  public static function clearEventRegistrationCache($event_code = NULL, $event_year = NULL) {
    if (empty($event_code) || empty($event_year)) {
      return FALSE;
    }
    $event_code = trim($event_code);
    $event_year = trim($event_year);
    $state_key = "am.net.event.registrations.{$event_year}.{$event_code}";
    \Drupal::state()->delete($state_key);
    return TRUE;
  }

  /**
   * Get Event Rating info.
   *
   * @param string $event_code
   *   The event code.
   * @param string $event_year
   *   The event year.
   *
   * @return string|null
   *   The event rating info.
   */
  public static function getEventRatingInfo($event_code, $event_year) {
    if (empty($event_code) || empty($event_year)) {
      return NULL;
    }
    // Check if the value exist locally.
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_search_index_rating', 'info');
    $query->fields('info', ['field_search_index_rating_value']);
    // Add Fields: Event ID.
    $query->leftJoin('commerce_product__field_amnet_event_id', 'event_id', 'event_id.entity_id = info.entity_id');
    $query->condition('event_id.field_amnet_event_id_code', $event_code);
    $query->condition('event_id.field_amnet_event_id_year', $event_year);
    // Return Rating Info.
    return $query->execute()->fetchField();
  }

  /**
   * Check if a given event represent a group of events.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The event product variation.
   *
   * @return bool
   *   TRUE if the given event represent a group, otherwise FALSE.
   */
  public static function isEventGroupByPurchasableEntity(PurchasableEntityInterface $purchased_entity = NULL) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    if (!$purchased_entity) {
      return FALSE;
    }
    $product = $purchased_entity->getProduct();
    return self::isEventGroup($product, TRUE);
  }

  /**
   * Check if a given event represent a group of events.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product entity.
   * @param bool $check_availability
   *   Flag to check event registration availability.
   * @param bool $check_exclusion
   *   Flag to check exclusion.
   *
   * @return bool
   *   TRUE if the given event represent a group, otherwise FALSE.
   */
  public static function isEventGroup(ProductInterface $product = NULL, $check_availability = FALSE, $check_exclusion = FALSE) {
    if (!$product) {
      return FALSE;
    }
    if ($product->bundle() != 'cpe_event') {
      return FALSE;
    }
    $field_name = 'field_exclude_from_web_catalog';
    $exclude_from_web_catalog = $product->get($field_name)->getString();
    if ($exclude_from_web_catalog) {
      return FALSE;
    }
    $field_name = 'field_acronym';
    $field = $product->get($field_name);
    if ($field->isEmpty()) {
      return FALSE;
    }
    if ($check_availability) {
      $acronym = $product->get('field_acronym')->getString();
      $event_year = $product->field_amnet_event_id->year ?? NULL;
      $ids = self::getGroupedEventIdsOpenForRegistration($acronym, $event_year);
      return !empty($ids);
    }
    $acronym = $field->getString();
    if ($check_exclusion && self::isExcludedAcronym($acronym)) {
      // This acronym has been excluded from the multi-day logic.
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get cached event info.
   *
   * @param string $event_code
   *   An AM.net event code.
   * @param int $event_year
   *   An AM.net event year.
   *
   * @return array
   *   An array with the event info.
   */
  public static function getCachedEventInfo($event_code = NULL, $event_year = NULL) {
    $data = [
      'type' => AMNetEntityTypesInterface::EVENT,
      'is_statically_cacheable' => TRUE,
    ];
    $context = new AMNetEntityTypeContext($data);
    $id = [
      'EventCode' => $event_code,
      'EventYear' => $event_year,
    ];
    return \Drupal::service('am_net.entity.repository')->getEntity($id, $context);
  }

  /**
   * Gets the products IDs associated to a given event acronym.
   *
   * @param array $acronyms
   *   The acronyms.
   *
   * @return array
   *   The list of product IDs.
   */
  public static function getProductIdByAcronyms(array $acronyms = []) {
    if (empty($acronyms)) {
      return [];
    }
    $database = \Drupal::database();
    $query = $database->select('commerce_product__field_acronym', 'acronym');
    $query->fields('acronym', ['entity_id']);
    $query->condition('acronym.field_acronym_value', $acronyms, 'IN');
    return $query->execute()->fetchAllKeyed(0, 0);
  }

  /**
   * Gets the 'Virtual Conference' base template NID.
   *
   * @return string|null
   *   The Virtual conference Node ID, or NULL if not found.
   */
  public static function getVirtualConferenceBaseTemplateNid() {
    return \Drupal::state()->get('am_net_cpe.settings.digital.rewind.node_id', NULL);
  }

  /**
   * Gets the 'Virtual Conference' node tie to a event by event code and year.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year (two digits).
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node entity, or NULL if not found.
   */
  public static function getVirtualConferenceByEventId($event_code = NULL, $event_year = NULL) {
    if (empty($event_code) || empty($event_year)) {
      return NULL;
    }
    $entity_id = self::getVirtualConferenceIdByEventId($event_code, $event_year);
    if (empty($entity_id)) {
      return NULL;
    }
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load($entity_id);
    return $node;
  }

  /**
   * Gets the 'Virtual Conference' ID related to a event by event code and year.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year (two digits).
   * @param bool $only_published
   *   The flag to return only published Virtual Conferences.
   *
   * @return string|null
   *   The Virtual conference Node ID, or NULL if not found.
   */
  public static function getVirtualConferenceIdByEventId($event_code = NULL, $event_year = NULL, $only_published = NULL) {
    if (empty($event_code) || empty($event_year)) {
      return NULL;
    }
    $database = \Drupal::database();
    $query = $database->select('node__field_digital_rewind_event_id', 'node');
    $query->fields('node', ['entity_id']);
    $query->condition('field_digital_rewind_event_id_code', $event_code);
    $query->condition('field_digital_rewind_event_id_year', $event_year);
    if ($only_published) {
      $query->leftJoin('node_field_data', 'bn', 'bn.nid = node.entity_id');
      $query->condition('bn.status', 1);
    }
    return $query->execute()->fetchField();
  }

  /**
   * Converts Drupal event date/time ranges to field item values.
   *
   * @param array $event_times
   *   An array of date/time ranges, each with the following keys:
   *    - start_date: A DrupalDateTime object.
   *    - end_date: A DrupalDateTime object.
   * @param string $timezone
   *   The timezone to use for formatting the datetime objects.
   *
   * @return array
   *   An array of 'value' and 'end_value' properties formatted for field items.
   */
  public static function convertDrupalEventDateTimeRangesToFieldItems(array $event_times, $timezone) {
    return array_map(function ($date) use ($timezone) {
      /** @var \Drupal\Core\DateTime\DrupalDateTime[] $date */
      return [
        'value' => $date['start_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => $timezone]),
        'end_value' => $date['end_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => $timezone]),
      ];
    }, $event_times);
  }

  /**
   * Gets the session group key.
   *
   * Returns a string that identifies a session and its concurrent sessions.
   *
   * @param array $session
   *   An AM.net event session record.
   *
   * @return string
   *   The session group key.
   */
  public static function getAmNetSessionTimeslotKey(array $session) {
    $concurrent_sessions = array_unique(array_merge(
      [$session['SessionCode']],
      $session['ConcurrentSesssions']
    ));
    sort($concurrent_sessions);
    $session_group_key = implode(':', $concurrent_sessions);

    return $session_group_key;
  }

  /**
   * Gets a link field data structure for the event course link.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return array|null
   *   An array of 'uri' and 'title' values if an external url is set.
   */
  public static function getDrupalCourseLink(array $event) {
    if (!empty($event['WebURL'])) {
      $event_name = $event['EventName'] ?? '';
      return [
        'uri' => trim($event['WebURL']),
        'title' => 'Register for ' . trim($event_name),
      ];
    }
  }

  /**
   * Gets DrupalDateTime objects for the begin/end dates of an event.
   *
   * @param array $event
   *   An AM.net event record.
   * @param string $timezone
   *   A timezone to use for the generated dates.
   *
   * @return \Drupal\Core\DateTime\DrupalDateTime[]
   *   An array of DrupalDateTime pairs: 'start_date' and 'end_date'.
   *
   * @throws \Exception
   */
  public static function getDrupalEventDateRanges(array $event, $timezone) {
    $event_dates_times = [];
    $begin_time = [
      'hour' => 8,
      'minute' => 0,
    ];;
    $end_time = [
      'hour' => 9,
      'minute' => 0,
    ];;
    $datetime_begin = new DrupalDateTime($event['BeginDate'], $timezone);
    $datetime_end = new DrupalDateTime($event['EndDate'], $timezone);
    $datetime_begin->setTime($begin_time['hour'], $begin_time['minute']);
    $datetime_end->setTime($end_time['hour'], $end_time['minute']);
    $event_dates_times[] = [
      'start_date' => $datetime_begin,
      'end_date' => $datetime_end,
    ];
    return $event_dates_times;
  }

  /**
   * Gets DrupalDateTime objects for the begin/end dates and times of an event.
   *
   * @param array $event
   *   An AM.net event record.
   * @param string $timezone
   *   A timezone to use for the generated dates.
   *
   * @return \Drupal\Core\DateTime\DrupalDateTime[]
   *   An array of DrupalDateTime pairs: 'start_date' and 'end_date'.
   *
   * @throws \Exception
   */
  public static function getDrupalEventDateTimeRangesOld(array $event, $timezone) {
    $event_dates_times = [];
    foreach (range(1, 3) as $day_number) {
      $begin_time_key = "BeginTimeDay{$day_number}";
      $end_time_key = "EndTimeDay{$day_number}";
      if (!empty($event[$begin_time_key])) {
        $begin_time = static::convertAmNetTimeParts($event[$begin_time_key]);
        $end_time = static::convertAmNetTimeParts($event[$end_time_key]);
        $datetime_begin = new DrupalDateTime($event['BeginDate'], $timezone);
        $datetime_end = new DrupalDateTime($event['BeginDate'], $timezone);
        $datetime_begin->setTime($begin_time['hour'], $begin_time['minute']);
        $datetime_end->setTime($end_time['hour'], $end_time['minute']);
        if ($day_number >= 2) {
          $additional_day = $day_number - 1;
          $x_days = new \DateInterval("P{$additional_day}D");
          $datetime_begin->add($x_days);
          $datetime_end->add($x_days);
        }
        $event_dates_times[] = [
          'start_date' => $datetime_begin,
          'end_date' => $datetime_end,
        ];
      }
    }

    return $event_dates_times;
  }

  /**
   * Gets the time parts for a given AM.net time field.
   *
   * @param string $am_net_time
   *   A string like "5:00 pm" or "3:30pm".
   *
   * @return array
   *   An array of 'hour' and 'minute' integers in 24-hour time.
   */
  public static function convertAmNetTimeParts($am_net_time) {
    $pm = preg_match('/pm/i', $am_net_time);
    $time_no_suffix = preg_replace('/\s?[ap]m/i', '', $am_net_time);
    $parts = explode(':', $time_no_suffix);
    $hour = (int) trim($parts[0]);
    $minute = (int) isset($parts[1]) ? trim($parts[1]) : 0;
    $hour = ($pm && $hour !== 12) ? $hour + 12 : $hour;

    return [
      'hour' => $hour,
      'minute' => $minute,
    ];
  }

  /**
   * Gets the first key of an array.
   *
   * @param array $array
   *   The given Array.
   *
   * @return string|null
   *   The first key of the array.
   */
  public static function arrayKeyFirst(array $array = []) {
    if (empty($array)) {
      return NULL;
    }
    $copy = $array;
    reset($copy);
    return key($copy);
  }

  /**
   * Gets the last key of an array.
   *
   * @param array $array
   *   The given Array.
   *
   * @return string|null
   *   The last key of the array.
   */
  public static function arrayKeyLast(array $array = []) {
    if (empty($array)) {
      return NULL;
    }
    $copy = $array;
    $keys = array_keys($copy);
    return end($keys);
  }

  /**
   * Gets DrupalDateTime objects for the begin/end dates and times of an event.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $event
   *   An AM.net event record.
   * @param string $format
   *   The date format.
   *
   * @return array|null
   *   An array of DrupalDateTime pairs: 'start_date' and 'end_date'.
   */
  public static function preprocessEventDate(ProductInterface $event = NULL, $format = 'F d, Y') {
    $event_date = self::formatEventDate($event, $format);
    if (empty($event_date)) {
      return NULL;
    }
    return [
      '#type' => 'processed_text',
      '#text' => "<div class='field field--name-field-dates-times field--type-daterange field--label-hidden field--items'><div><span class='icon-calendar'>&nbsp;</span>{$event_date}</div></div>",
      '#format' => 'full_html',
    ];
  }

  /**
   * Format a given timestamp.
   *
   * @param string $timestamp
   *   The timestamp.
   * @param string $format
   *   The datetime format.
   * @param string $target_timezone
   *   The target timezone.
   * @param string $base_timezone
   *   The base timezone.
   *
   * @return string|null
   *   The formatted date time, otherwise NULL.
   */
  public static function formatTimestamp($timestamp = NULL, $format = NULL, $target_timezone = 'America/New_York', $base_timezone = 'UTC') {
    if (empty($timestamp) || empty($format)) {
      return NULL;
    }
    $datetime = DrupalDateTime::createFromTimestamp($timestamp, $base_timezone);
    $datetime->setTimezone(new \DateTimeZone($target_timezone));
    if ($format == 'timestamp') {
      return $datetime->getTimestamp();
    }
    return $datetime->format($format);
  }

  /**
   * Format a given Date Time.
   *
   * @param string $datetime
   *   The timestamp.
   * @param string $format
   *   The datetime format.
   * @param string $base_timezone
   *   The base timezone.
   * @param string $target_timezone
   *   The target timezone.
   *
   * @return string|null
   *   The formatted date time, otherwise NULL.
   */
  public static function formatDatetime($datetime = NULL, $format = NULL, $base_timezone = 'America/New_York', $target_timezone = 'America/New_York') {
    if (empty($datetime) || empty($format)) {
      return NULL;
    }
    $datetime = new DrupalDateTime($datetime, $base_timezone);
    $datetime->setTimezone(new \DateTimeZone($target_timezone));
    if ($format == 'timestamp') {
      return $datetime->getTimestamp();
    }
    return $datetime->format($format);
  }

  /**
   * Gets DrupalDateTime objects for the begin/end dates and times of an event.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $event
   *   An AM.net event record.
   * @param string $format
   *   The date format.
   *
   * @return string|null
   *   An array of DrupalDateTime pairs: 'start_date' and 'end_date'.
   */
  public static function formatEventDate(ProductInterface $event = NULL, $format = 'F d, Y') {
    if (!$event) {
      return NULL;
    }
    if (!$event->hasField('field_dates_times')) {
      return NULL;
    }
    $field = $event->get('field_dates_times');
    if ($field->isEmpty()) {
      return NULL;
    }
    $values = $field->getValue();
    $items = [];
    foreach ($values as $delta => $value) {
      $begin = $value['value'] ?? NULL;
      if (empty($begin)) {
        continue;
      }
      $time = self::formatDatetime($begin, 'timestamp', 'UTC');
      $items[$time] = $time;
      // Set the end date.
      $end = $value['end_value'] ?? NULL;
      if (!empty($end)) {
        $time = self::formatDatetime($end, 'timestamp', 'UTC');
        $items[$time] = $time;
      }
    };
    if (empty($items)) {
      return NULL;
    }
    $datetime_begin = self::arrayKeyFirst($items);
    $datetime_end = self::arrayKeyLast($items);
    if (self::formatTimestamp($datetime_begin, 'Y-m-d') == self::formatTimestamp($datetime_end, 'Y-m-d')) {
      // Is one day event.
      $suffix = NULL;
      if ($datetime_begin != $datetime_end) {
        // The event happens in different hours.
        $suffix = ' from ' . self::formatTimestamp($datetime_begin, 'g:i a') . ' to ' . self::formatTimestamp($datetime_end, 'g:i a');
      }
      return self::formatTimestamp($datetime_begin, $format) . $suffix;
    }
    // Compare the diff of days on the dates.
    $begin = new DrupalDateTime();
    $begin->setTimestamp($datetime_begin);
    $end = new DrupalDateTime();
    $end->setTimestamp($datetime_end);
    // Check the diff on days.
    $interval = $begin->diff($end);
    $diff_on_days = !empty($interval) ? $interval->format('%a') : 0;
    $diff_on_days = intval($diff_on_days);
    $event_more_than_three_days = ($diff_on_days > 7);
    if ($event_more_than_three_days) {
      return t('Currently available');
    }
    $dates = [
      self::formatTimestamp($datetime_begin, $format),
      self::formatTimestamp($datetime_end, $format),
    ];
    return implode(' to ', $dates);
  }

  /**
   * Gets DrupalDateTime objects for the begin/end dates and times of an event.
   *
   * @param array $event
   *   An AM.net event record.
   * @param string $timezone
   *   A timezone to use for the generated dates.
   *
   * @return \Drupal\Core\DateTime\DrupalDateTime[]
   *   An array of DrupalDateTime pairs: 'start_date' and 'end_date'.
   */
  public static function getDrupalBundleEventDateTimeRange(array $event, $timezone) {
    $datetime_begin = new DrupalDateTime($event['BeginDate'], $timezone);
    $begin_time_raw = $event['BeginTimeDay1'];
    if (!empty($begin_time_raw)) {
      $begin_time = static::convertAmNetTimeParts($begin_time_raw);
      $datetime_begin->setTime($begin_time['hour'], $begin_time['minute']);
    }
    $end_time_raw = $event['EndTimeDay1'];
    $datetime_end = new DrupalDateTime($event['EndDate'], $timezone);
    if (!empty($end_time_raw)) {
      $end_time = static::convertAmNetTimeParts($end_time_raw);
      $datetime_end->setTime($end_time['hour'], $end_time['minute']);
    }
    return [
      'start_date' => $datetime_begin,
      'end_date' => $datetime_end,
    ];
  }

  /**
   * Gets bundle event "Expiration Date".
   *
   * @param array $event
   *   An AM.net event record.
   * @param string $timezone
   *   A timezone to use for the generated dates.
   *
   * @return \Drupal\Core\DateTime\DrupalDateTime
   *   The Expiration Date instance of DrupalDateTime.
   */
  public static function getDrupalBundleEventExpirationDate(array $event, $timezone) {
    $begin_date = $event['BeginDate'] ?? NULL;
    if (empty($begin_date)) {
      return NULL;
    }
    $datetime_begin = new DrupalDateTime($begin_date, $timezone);
    return $datetime_begin;
  }

  /**
   * Gets the various prices for an event record.
   *
   * @param array $event
   *   An AM.net event record.
   * @param string $currency_code
   *   The event currency code.
   *
   * @return array
   *   A multidimensional array (grouped by 'multi_day' and 'single_day') of
   *   \Drupal\commerce\Price or NULL values for the following:
   *   - 'price_member_early': The early-bird member price.
   *   - 'price_member': The regular member price.
   *   - 'price_early': The early-bird non-member price.
   *   - 'price': The regular non-member price.
   */
  public static function getDrupalEventPrices(array $event, $currency_code) {
    $prices = [
      'multi_day' => [
        'price_member_early' => NULL,
        'price_member' => NULL,
        'price_early' => NULL,
        'price' => NULL,
      ],
      'single_day' => [
        'price_member_early' => NULL,
        'price_member' => NULL,
        'price_early' => NULL,
        'price' => NULL,
      ],
      'one_day_fee' => NULL,
      'overrides' => [],
    ];
    foreach ($event['Fees'] as $fee) {
      $fee_code = implode('-', [$fee['Ty'], $fee['Ty2']]);
      $member = $fee['ApplyToMemberType'] === 'M';
      $all = $fee['ApplyToMemberType'] === 'A';
      $amount = new Price((string) $fee['Amount'], $currency_code);
      switch ($fee_code) {
        case 'R-ER':
          // Early registration.
          $prices['multi_day'][$member ? 'price_member_early' : 'price_early'] = $amount;
          if ($all) {
            $prices['overrides']['fields']['field_override_e_fee_apply_to'] = 'A';
          }
          break;

        case 'R-SF':
          // Standard (Multi-day) fee.
          $prices['multi_day'][$member ? 'price_member' : 'price'] = $amount;
          if ($all) {
            $prices['overrides']['fields']['field_override_s_fee_apply_to'] = 'A';
          }
          break;

        case 'R-OD':
          // One-day fee.
          // (No explicit early bird registration fee for One-day fees.)
          $prices['single_day'][$member ? 'price_member' : 'price'] = $amount;
          $prices['single_day'][$member ? 'price_member_early' : 'price_early'] = $amount;
          // Add flag on the variation that indicates it is for a
          // one-day registration.
          $prices['one_day_fee'] = TRUE;
          break;
      }
    }

    return $prices;
  }

  /**
   * Get Event Early Registration.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return string|null
   *   The Early registration date of the event if applies.
   */
  public static function getDrupalEventEarlyRegistration(array $event) {
    $early_registration = NULL;
    if (isset($event['Fees']) && !empty($event['Fees'])) {
      foreach ($event['Fees'] as $fee) {
        $ty = $fee['Ty'] ?? NULL;
        $ty2 = $fee['Ty2'] ?? NULL;
        $fee_date = $fee['FeeDate'] ?? NULL;
        $parameter_defined = (!is_null($ty) && !is_null($ty2) && !is_null($fee_date));
        if ($parameter_defined && ($ty == 'R') && ($ty2 == 'ER')) {
          $early_registration = $fee_date;
        }
      }
    }
    return $early_registration;
  }

  /**
   * Check if an event contain AICPA Discounts.
   *
   * AICPA would correspond to events whose vendor is listed as the AICPA.
   * If an event has an "AICPA Discount ($30)" fee listed, it qualifies
   * for that discount.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return bool
   *   The Early registration date of the event if applies.
   */
  public static function getDrupalEventAicpa(array $event) {
    if (isset($event['Fees']) && !empty($event['Fees'])) {
      foreach ($event['Fees'] as $fee) {
        $ty = $fee['Ty'] ?? NULL;
        $ty2 = $fee['Ty2'] ?? NULL;
        $parameter_defined = (!is_null($ty) && !is_null($ty2));
        if ($parameter_defined && ($ty == 'S') && ($ty2 == 'AD')) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Check the list of AM.Net Related events.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return array
   *   The List of related events.
   */
  public static function getAmNetRelatedEvents(array $event) {
    $events = [];
    $am_net_related_events = $event['RelatedEvents'] ?? [];
    if (empty($am_net_related_events)) {
      return $events;
    }
    foreach ($am_net_related_events as $delta => $event) {
      $code = $event['EventCode'] ?? NULL;
      $year = $event['EventYear'] ?? NULL;
      if (empty($code) || empty($year)) {
        continue;
      }
      $events[] = [
        'code' => trim($code),
        'year' => trim($year),
      ];
    }
    return $events;
  }

  /**
   * Check if the event is free.
   *
   * @param array $event
   *   An AM.net event record.
   * @param string $currency_code
   *   The event currency code.
   *
   * @return bool
   *   Free would correspond to events that don't have any fees
   *   (or sessions w/fees) in them.
   */
  public static function isFreeEvent(array $event, $currency_code) {
    // Free would correspond to events that don't have
    // any fees (or sessions w/fees) in them.
    // Check Fees.
    if (isset($event['Fees']) && !empty($event['Fees'])) {
      foreach ($event['Fees'] as $fee) {
        $amount_fee = $fee['Amount'] ?? NULL;
        if (!is_null($amount_fee)) {
          $amount = new Price((string) $amount_fee, $currency_code);
          if (!$amount->isZero()) {
            return FALSE;
          }
        }
      }
    }
    // Check Sessions.
    if (isset($event['Sessions']) && !empty($event['Sessions'])) {
      foreach ($event['Sessions'] as $session) {
        $prices = [];
        $member_fee = $session['MemberFee'] ?? NULL;
        if (!is_null($member_fee)) {
          $prices[] = $member_fee;
        }
        $nonmember_fee = $session['NonmemberFee'] ?? NULL;
        if (!is_null($nonmember_fee)) {
          $prices[] = $nonmember_fee;
        }
        foreach ($prices as $delta => $price) {
          $amount = new Price((string) $price, $currency_code);
          if (!$amount->isZero()) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Gets the early bird pricing expiration date for a given event.
   *
   * @param array $event
   *   An AM.net event record.
   * @param string $timezone
   *   The event timezone.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The date and time when early bird pricing expires.
   */
  public static function getDrupalEventEarlyBirdExpiration(array $event, $timezone) {
    foreach ($event['Fees'] as $fee) {
      if ($fee['Ty'] === 'R' && $fee['Ty2'] === 'ER') {
        return new DrupalDateTime($fee['FeeDate'], $timezone);
      }
    }
    return NULL;
  }

  /**
   * Check if the event record is Self-Study event.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return bool
   *   TRUE if the event is Self-study event, otherwise FALSE.
   */
  public static function isSelfStudyEventByEventRecord(array $event) {
    if (empty($event)) {
      return FALSE;
    }
    $code = $event['DivisionCode'] ?? NULL;
    if (empty($code)) {
      return FALSE;
    }
    return am_net_is_self_study($code);
  }

  /**
   * Gets the event registration cutoff date and time.
   *
   * @param array $event
   *   An AM.net event record.
   * @param string $timezone
   *   A timezone to use for the generated date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The registration cutoff date/time.
   *
   * @throws \Exception
   */
  public static function getDrupalEventRegistrationCutoff(array $event, $timezone) {
    if (!empty($event['RegistrationCutoff'])) {
      return new DrupalDateTime($event['RegistrationCutoff'], $timezone);
    }
    $is_self_study_event = self::isSelfStudyEventByEventRecord($event);
    if ($is_self_study_event) {
      $date_key = 'end_date';
    }
    else {
      $date_key = 'start_date';
    }
    return current(self::getDrupalEventDateTimeRanges($event, $timezone))[$date_key];
  }

  /**
   * Gets DrupalDateTime objects for the begin/end dates and times of an event.
   *
   * @param array $event
   *   An AM.net event record.
   * @param string $timezone
   *   A timezone to use for the generated dates.
   *
   * @return \Drupal\Core\DateTime\DrupalDateTime[]
   *   An array of DrupalDateTime pairs: 'start_date' and 'end_date'.
   *
   * @throws \Exception
   */
  public static function getDrupalEventDateTimeRanges(array $event, $timezone) {
    $begin_date = $event['BeginDate'] ?? NULL;
    $datetime_begin = !empty($begin_date) ? new DrupalDateTime($begin_date, $timezone) : NULL;
    $end_date = $event['EndDate'] ?? NULL;
    $datetime_end = !empty($end_date) ? new DrupalDateTime($end_date, $timezone) : NULL;
    $event_more_than_three_days = FALSE;
    if ($datetime_begin && $datetime_end) {
      // Check the diff on days.
      $interval = $datetime_begin->diff($datetime_end);
      $diff_on_days = !empty($interval) ? $interval->format('%a') : 0;
      $diff_on_days = intval($diff_on_days);
      if ($diff_on_days > 7) {
        $event_more_than_three_days = TRUE;
      }
    }
    $dates_times = [];
    if ($event_more_than_three_days) {
      $dates_times[] = [
        'start_date' => $datetime_begin,
        'end_date' => $datetime_end,
      ];
      // Stop here.
      return $dates_times;
    }
    // Check the days that the event take place.
    foreach (range(1, 3) as $day_number) {
      $begin_time_key = "BeginTimeDay{$day_number}";
      if (empty($event[$begin_time_key])) {
        continue;
      }
      $end_time_key = "EndTimeDay{$day_number}";
      $begin_time = static::convertAmNetTimeParts($event[$begin_time_key]);
      $begin = clone $datetime_begin;
      $begin->setTime($begin_time['hour'], $begin_time['minute']);
      $end_time = static::convertAmNetTimeParts($event[$end_time_key]);
      $end = clone $datetime_begin;
      $end->setTime($end_time['hour'], $end_time['minute']);
      if ($day_number > 1) {
        $additional_day = $day_number - 1;
        $x_days = new \DateInterval("P{$additional_day}D");
        $begin->add($x_days);
        $end->add($x_days);
      }
      $dates_times[] = [
        'start_date' => $begin,
        'end_date' => $end,
      ];
    }
    return $dates_times;
  }

  /**
   * Gets a SKU for an event product variation.
   *
   * @param array $event
   *   An AM.net event record.
   * @param string|int $day
   *   The day number, or 'all' to represent all days.
   *
   * @return string
   *   A SKU for the given event record and day(s).
   */
  public static function getDrupalEventVariationSku(array $event, $day = 'all') {
    $sku = "{$event['Year']}-{$event['Code']}";
    if (is_int($day)) {
      $sku .= "--{$day}";
    }
    return $sku;
  }

  /**
   * Gets a link field data structure for the external event registration url.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return array|null
   *   An array of 'uri' and 'title' values if an external url is set.
   */
  public static function getDrupalExternalRegistrationLink(array $event) {
    if (!empty($event['WebLink'])) {
      return [
        'uri' => trim($event['WebLink']),
        'title' => trim($event['EventName']),
      ];
    }
  }

  /**
   * Gets the session start or end time.
   *
   * @param array $session
   *   The AM.net event session record.
   * @param string $timezone
   *   The event timezone.
   * @param string $property
   *   The time property to get: either 'start' or 'end'.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The start or end datetime for the given session and time.
   */
  public static function getDrupalSessionTime(array $session, $timezone, $property = 'start') {
    $property_key = $property === 'end' ? 'EndTime' : 'SessionTime';
    $datetime = new DrupalDateTime($session['SessionDate'], $timezone);
    $time_parts = self::convertAmNetTimeParts($session[$property_key]);
    $datetime->setTime($time_parts['hour'], $time_parts['minute']);

    return $datetime;
  }

  /**
   * Gets the CPE registration type for a given AM.net event.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return string
   *   The RNG registration type.
   */
  public static function getDrupalEventRegistrationType(array $event) {
    return ($event['FacilityLocationFirmCode'] === 'OL' || substr($event['Code'], -1, 1) === 'W') ? 'event_online' : 'event_in_person';
  }

  /**
   * Gets the CPE registration type for a given AM.net session.
   *
   * @param array $session
   *   An AM.net session record.
   * @param array $event
   *   The session's parent event record.
   *
   * @return string
   *   The RNG registration type.
   */
  public static function getDrupalSessionRegistrationType(array $session, array $event) {
    return (substr($event['Code'], -1, 1) === 'W') ? 'session_online' : 'session_in_person';
  }

  /**
   * Gets the CPE registration type for a given AM.net product.
   *
   * @param array $product
   *   An AM.net session record.
   *
   * @return string
   *   The RNG registration type.
   */
  public static function getDrupalProductRegistrationType(array $product) {
    if ($product['CategoryCode'] === 'SS' && $product['FormatCode'] === 'ON') {
      return 'self_study_online';
    }
  }

}
