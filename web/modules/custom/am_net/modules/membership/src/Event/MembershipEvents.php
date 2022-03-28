<?php

namespace Drupal\am_net_membership\Event;

/**
 * Membership events.
 *
 * @package Drupal\am_net_membership\Event
 */
final class MembershipEvents {

  /**
   * Name of the event fired after applying for membership.
   *
   * @Event
   *
   * @see \Drupal\am_net_membership\Event\MembershipApplicationEvent
   */
  const SUBMIT_APPLICATION = 'am_net_membership.application_submission';

  /**
   * Name of the event fired after submitting a membership renewal.
   *
   * @Event
   *
   * @see \Drupal\am_net_membership\Event\MembershipApplicationEvent
   */
  const SUBMIT_RENEWAL = 'am_net_membership.renewal_submission';

}
