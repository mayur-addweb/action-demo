<?php

namespace Drupal\vscpa_commerce;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * Defines an event registration manager interface.
 */
interface EventRegistrationManagerInterface {

  /**
   * Gets all event session options within the date range of the given entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param bool $flat
   *   TRUE if the return array should be one level.
   *   FALSE to return an array of timeslot groups with the following keys:
   *     'label': (string) A label for the timeslot group.
   *     'start': (DrupalDateTime) Timeslot group start date and time.
   *     'end': (DrupalDateTime) Timeslot group end date and time.
   *     'timeslots': (array) Timeslots with the following keys:
   *       'label': (string) A label for the timeslot.
   *       'sessions': (EventSession[]) An array of sessions, keyed by id.
   *
   * @return array
   *   The required and yet unselected session options, keyed by session id.
   */
  public function getEventSessions(PurchasableEntityInterface $entity, $flat = TRUE);

  /**
   * Update an event order item with selected sessions.
   *
   * @param array $sessions
   *   An array of event session ids.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   */
  public function selectSessions(array $sessions, OrderItemInterface $order_item);

  /**
   * Gets the selected session options already saved on the order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   *
   * @return array
   *   The accepted event sessions, keyed by session id.
   */
  public function getSelectedSessions(OrderItemInterface $order_item);

  /**
   * Gets session options not yet selected for the given entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface|null $order_item
   *   The order item, if it exists.
   * @param bool $flat
   *   TRUE if the return array should be one level.
   *   FALSE to return an array of timeslot groups with the following keys:
   *     'label': (string) A label for the timeslot group.
   *     'start': (DrupalDateTime) Timeslot group start date and time.
   *     'end': (DrupalDateTime) Timeslot group end date and time.
   *     'timeslots': (array) Timeslots with the following keys:
   *       'label': (string) A label for the timeslot.
   *       'sessions': An array of sessions keyed by id with:
   *          'entity': The \Drupal\vscpa_commerce\Entity\EventSession entity.
   *          'general': A flag indicating if it is a General Session.
   *
   * @return array
   *   The not-yet-selected session options, keyed by session id.
   */
  public function getRemainingOptions(PurchasableEntityInterface $entity, $order_item = NULL, $flat = TRUE);

}
