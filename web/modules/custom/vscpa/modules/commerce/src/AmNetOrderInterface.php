<?php

namespace Drupal\vscpa_commerce;

/**
 * Order interface which represents the order sync process with AM.net.
 */
interface AmNetOrderInterface {

  /**
   * Flag describes order has not yet been synchronized with AM.net System.
   */
  const ORDER_NOT_SYNCHRONIZED = 0;

  /**
   * Flag describes order has been synchronized with AM.net System.
   */
  const ORDER_SYNCHRONIZED = 1;

  /**
   * Flag describes order has been partially synchronized with AM.net System.
   */
  const ORDER_PARTIALLY_SYNCHRONIZED = 0;

  /**
   * Flag describes order item not synchronized with AM.net System.
   */
  const ORDER_ITEM_NOT_SYNCHRONIZED = 0;

  /**
   * Flag describes order item synchronized with AM.net System.
   */
  const ORDER_ITEM_SYNCHRONIZED = 1;

  /**
   * Flag that describes the order item type: Membership.
   */
  const ORDER_ITEM_TYPE_MEMBERSHIP = 'membership';

  /**
   * Flag that describes the order item type: Donation.
   */
  const ORDER_ITEM_TYPE_DONATION = 'donation';

  /**
   * Flag that describes the order item type: Event registration.
   */
  const ORDER_ITEM_TYPE_EVENT_REGISTRATION = 'event_registration';

  /**
   * Flag that describes the order item type: Self-study registration.
   */
  const ORDER_ITEM_TYPE_SELF_STUDY_REGISTRATION = 'self_study_registration';

  /**
   * Flag that describes the order item type: Peer Review Administrative Fee.
   */
  const ORDER_ITEM_TYPE_PEER_REVIEW_PAYMENT = 'peer_review_administrative_fee';

  /**
   * Flag that describes the order item type: Undefined.
   */
  const ORDER_ITEM_TYPE_UNDEFINED = 'UND';

}
