<?php

namespace Drupal\am_net_membership\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface;

/**
 * Defines the membership application event.
 *
 * @see \Drupal\am_net_membership\Event\MembershipEvents
 */
class MembershipApplicationEvent extends Event {

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The cart owner account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $cartOwner = NULL;

  /**
   * Donations submitted with the membership application.
   *
   * @var array
   */
  protected $donations;

  /**
   * The Dues Payment Plan Info.
   *
   * @var \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface|null
   */
  protected $paymentPlanInfo = NULL;

  /**
   * Constructs a new MembershipApplicationEvent.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param array $donations
   *   Any donations.
   * @param \Drupal\user\UserInterface $cart_owner
   *   The cart owner account.
   * @param \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface $payment_plan_info
   *   The payment plan info.
   */
  public function __construct(UserInterface $account, array $donations, UserInterface $cart_owner = NULL, DuesPaymentPlanInfoInterface $payment_plan_info = NULL) {
    $this->account = $account;
    $this->cartOwner = $cart_owner;
    $this->donations = $donations;
    $this->paymentPlanInfo = $payment_plan_info;
  }

  /**
   * Gets the cart owner account.
   *
   * @return \Drupal\user\UserInterface
   *   The user account.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Gets the flag value: 'Enroll Payment Plan'.
   *
   * @return bool
   *   The flag value.
   */
  public function enrollPaymentPlan() {
    return ($this->paymentPlanInfo instanceof DuesPaymentPlanInfoInterface) && $this->paymentPlanInfo->isPlanActive();
  }

  /**
   * Gets the Payment Plan Info.
   *
   * @return \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface|null
   *   The flag value.
   */
  public function getPaymentPlanInfo() {
    return $this->paymentPlanInfo;
  }

  /**
   * Gets the user account.
   *
   * @return \Drupal\user\UserInterface
   *   The user account.
   */
  public function getCartOwnerAccount() {
    if (!is_null($this->cartOwner)) {
      return $this->cartOwner;
    }
    return $this->account;
  }

  /**
   * Gets the donations submitted with the membership application.
   *
   * @return array
   *   An array of donations, each with the following keys:
   *     - amount: Price The price of the donation.
   *     - anonymous: bool TRUE if the donations is to be made anonymously.
   *     - destination: string 'EF' or 'PAC'.
   *     - source: string 'I' (Individual) or 'F' (Firm).
   */
  public function getDonations() {
    return $this->donations;
  }

}
