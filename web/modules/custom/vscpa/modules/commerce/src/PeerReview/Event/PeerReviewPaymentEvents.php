<?php

namespace Drupal\vscpa_commerce\PeerReview\Event;

/**
 * Peer Review events.
 *
 * @package Drupal\vscpa_commerce\PeerReview
 */
final class PeerReviewPaymentEvents {

  /**
   * Name of the event fired after submitting a Peer Review Payment form.
   *
   * @Event
   *
   * @see \Drupal\vscpa_commerce\PeerReview\Event\PeerReviewPaymentEvent
   */
  const SUBMIT_PAYMENT = 'vscpa_commerce.peer_review_payment_submission';

}
