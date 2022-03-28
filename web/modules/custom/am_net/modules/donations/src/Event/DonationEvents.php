<?php

namespace Drupal\am_net_donations\Event;

/**
 * Donation events.
 *
 * @package Drupal\am_net_donations\Event
 */
final class DonationEvents {

  /**
   * Name of the event fired after submitting a Membership Donation form.
   *
   * @Event
   *
   * @see \Drupal\am_net_donations\Event\DonationEvent
   */
  const SUBMIT_DONATION = 'am_net_donations.donation_submission';

}
