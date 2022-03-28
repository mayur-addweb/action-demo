<?php

namespace Drupal\vscpa_commerce;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * The event registration manager.
 *
 * @package Drupal\vscpa_commerce
 */
class EventRegistrationManager implements EventRegistrationManagerInterface {

  use EventRegistrationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEventSessions(PurchasableEntityInterface $entity, $flat = TRUE) {
    $options = [];
    $sessions = [];
    $materials_session_codes = ['EM1', 'PM1'];
    $materials_sessions = [];
    $sponsor_session_codes = ['SPE', 'SPM'];
    $sponsor_sessions = [];
    if ($entity instanceof ProductVariationInterface && $entity->bundle() === 'event_registration') {
      if (!$product = $entity->getProduct()) {
        throw new \Exception(t('Variation does not have product.'));
      }
      $is_bundle = $product->get('field_search_index_is_bundle')->getString() == '1';
      if ($is_bundle) {
        return [];
      }
      // Date storage is always in UTC.
      $utc = new \DateTimeZone('UTC');
      $dates_times = $product->get('field_dates_times')->count();
      $event_start = new DrupalDateTime($product->get('field_dates_times')->first()->value, $utc);
      $event_end = new DrupalDateTime($product->get('field_dates_times')->get($dates_times - 1)->end_value, $utc);
      $ticket_start = $ticket_end = NULL;
      if ($entity->hasField('field_applies_to_date_range')) {
        $ticket_start = new DrupalDateTime($entity->get('field_applies_to_date_range')->value, $utc);
        $ticket_end = new DrupalDateTime($entity->get('field_applies_to_date_range')->end_value, $utc);
      }
      if ($product->hasField('field_event_timeslot_groups')) {
        foreach ($product->get('field_event_timeslot_groups') as $group_item) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $group */
          $group = $group_item->entity;
          $timeslot_group = [
            'label' => $group->get('field_label')->value,
            'start' => $group->hasField('field_timeslot_group_time') ? $group->get('field_timeslot_group_time')->start_date : NULL,
            'end' => $group->hasField('field_timeslot_group_time') ? $group->get('field_timeslot_group_time')->end_date : NULL,
          ];
          if ($timeslot_group['end'] && $event_start && ($timeslot_group['end']->getTimestamp() < $event_start->getTimestamp()) ||
              $timeslot_group['start'] && $event_end && ($timeslot_group['start']->getTimestamp() > $event_end->getTimestamp())) {
            // Allow timeslot groups that fall outside the range of official
            // event days to be selected regardless of selected ticket.
            // Supports pre- and post-event add-on sessions.
          }
          else {
            // Make sure timeslot group falls within range of selected ticket.
            if ($timeslot_group['start'] && $ticket_end && ($timeslot_group['start']->getTimestamp() > $ticket_end->getTimestamp())) {
              continue;
            }
            if ($timeslot_group['end'] && $ticket_start && ($timeslot_group['end']->getTimestamp() < $ticket_start->getTimestamp())) {
              continue;
            }
          }
          if ($group->hasField('field_timeslots')) {
            foreach ($group->get('field_timeslots') as $timeslot_item) {
              $timeslot = $timeslot_item->entity;
              $option = [
                'label' => $timeslot->get('field_label')->value,
              ];
              if ($timeslot->hasField('field_sessions')) {
                foreach ($timeslot->get('field_sessions') as $session) {
                  $session_entity = $session->entity ?? NULL;
                  $item = [
                    'general' => ($session_entity->field_session_general->value),
                    'entity' => $session_entity,
                  ];
                  $session_code = ($session_entity) ? $session_entity->get('field_session_code')->getString() : '';
                  if (in_array($session_code, $materials_session_codes)) {
                    $materials_sessions[$session->target_id] = $item;
                  }
                  if (in_array($session_code, $sponsor_session_codes)) {
                    $sponsor_sessions[$session->target_id] = $item;
                  }
                  else {
                    $option['sessions'][$session->target_id] = $item;
                    $sessions[$session->target_id] = $session_entity;
                  }
                }
              }
              if (!empty($option['sessions'])) {
                $timeslot_group['timeslots'][] = $option;
              }
            }
          }
          if (!empty($timeslot_group['timeslots'])) {
            $options[] = $timeslot_group;
          }
        }
      }
    }
    // Include materials sessions.
    if (!empty($materials_sessions)) {
      if ($flat) {
        foreach ($materials_sessions as $target_id => $session) {
          $sessions[$target_id] = $session;
        }
      }
      else {
        $item = [
          'label' => 'Material Preference',
          'start' => '',
          'end' => '',
          'type' => 'materials',
          'timeslots' => [
            0 => [
              'label' => '',
              'sessions' => $materials_sessions,
            ],
          ],
        ];
        if (!empty($options)) {
          $tmp = $options[0];
          $options[0] = $item;
          $options[] = $tmp;
        }
        else {
          $options[] = $item;
        }
      }
    }
    // Include 'Sponsor Opt-In' sessions.
    if (!empty($sponsor_sessions)) {
      if ($flat) {
        foreach ($sponsor_sessions as $target_id => $session) {
          $sessions[$target_id] = $session;
        }
      }
      else {
        $item = [
          'label' => 'Sponsor Opt-in',
          'start' => new DrupalDateTime('3000-01-01 00:00:00'),
          'end' => '',
          'type' => 'sponsors',
          'timeslots' => [
            0 => [
              'label' => 'For this event, how would you like to hear from our sponsors/exhibitors? If you do not wish to hear from them, leave the boxes unchecked.',
              'sessions' => $sponsor_sessions,
            ],
          ],
        ];
        $options[] = $item;
      }
    }
    // Return the result as raw array or as a formatted array.
    return $flat ? $sessions : $options;
  }

  /**
   * {@inheritdoc}
   */
  public function selectSessions(array $sessions, OrderItemInterface $order_item) {
    $all_selections = [];
    foreach ($order_item->get('field_sessions_selected') as $session) {
      $all_selections[$session->value] = $session->value;
    }
    foreach ($sessions as $new_session) {
      $all_selections[$new_session] = $new_session;
    }
    $order_item->set('field_sessions_selected', $all_selections);
    $order_item->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedSessions(OrderItemInterface $order_item) {
    $selections = [];
    if ($order_item->hasField('field_sessions_selected')) {
      foreach ($order_item->get('field_sessions_selected') as $selection) {
        $selections[$order_item->id()][$selection->target_id] = $selection->entity;
      }
    }

    return $selections;
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueId($base = NULL) {
    if (empty($base)) {
      return NULL;
    }
    $id = strtolower($base);
    $id = trim($id);
    $id = str_replace(',', '', $id);
    $id = str_replace(' ', '_', $id);
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function sortRemainingOptionsByDays($groups = []) {
    if (empty($groups)) {
      return $groups;
    }
    $options = [];
    $items = [];
    foreach ($groups as $delta => $group) {
      $label = $group['label'] ?? NULL;
      /* @var \Drupal\Core\Datetime\DrupalDateTime $start */
      $start = $group['start'] ?? NULL;
      if (empty($label)) {
        continue;
      }
      if (empty($start)) {
        // EM type session.
        $options[] = $group;
        continue;
      }
      $start_date = $start->format('Y-m-d');
      $key = strtotime($start_date);
      $start_timestamp = $start->getTimestamp();
      $items[$key][$start_timestamp] = $group;
    }
    // First sort the main group.
    ksort($items);
    foreach ($items as $delta => $item) {
      // First sort internal timeslots.
      ksort($item);
      foreach ($item as $timeslots) {
        $options[] = $timeslots;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingOptions(PurchasableEntityInterface $entity, $order_item = NULL, $flat = TRUE) {
    $all_options = $this->getEventSessions($entity, $flat);

    $selected_options = $order_item ? $this->getSelectedSessions($order_item) : [];

    $groups = array_diff_key($all_options, $selected_options);
    if ($flat) {
      return $groups;
    }
    return $this->sortRemainingOptionsByDays($groups);
  }

}
