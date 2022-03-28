<?php

namespace Drupal\am_net_payments;

use Drupal\user\UserInterface;

/**
 * AM.net Payments Updater.
 *
 * @package Drupal\am_net_payments
 */
interface PaymentsUpdaterInterface {

  /**
   * Updates a given user with payments and payment methods from AM.net.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $payment_gateway_id
   *   The payment gateway id.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function update(UserInterface $user, $payment_gateway_id);

}
