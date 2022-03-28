<?php

namespace Drupal\am_net;

/**
 * Defines a common interface store AM.Net Entity Types.
 */
interface AMNetEntityTypesInterface {

  /**
   * The Account code: Event.
   */
  const EVENT = 'event';

  /**
   * The Account code: EventRegistration.
   */
  const EVENT_REGISTRATION = 'event_registration';

  /**
   * The AM.net entity type: Product Course.
   */
  const COURSE = 'product';

  /**
   * The Account code: Dues Payment Plan.
   */
  const PAYMENT_PLANS = 'payment_plans';

  /**
   * The Account code: Dues Payment.
   */
  const DUES_PAYMENT_PLANS = 'dues_payment';

}
