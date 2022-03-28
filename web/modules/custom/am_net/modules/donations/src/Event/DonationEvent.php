<?php

namespace Drupal\am_net_donations\Event;

use Drupal\commerce_price\Price;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the donation event.
 *
 * @see \Drupal\am_net_donations\Event\DonationEvents
 */
class DonationEvent extends Event {

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The amount of the donation.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $amount;

  /**
   * If the donation should be made anonymously.
   *
   * @var bool
   */
  protected $anonymous;

  /**
   * The destination for the donation funds, either 'EF' or 'PAC'.
   *
   * @var string
   */
  protected $destination;

  /**
   * The source of the donation, either 'P' or 'F'.
   *
   * @var string
   */
  protected $source;

  /**
   * The Contribution Fund code: 'AF', 'VS', 'WM', 'MJ'.
   *
   * @var string
   */
  protected $fund;

  /**
   * Flag for check if the donation is recurring..
   *
   * @var string
   */
  protected $isRecurring = FALSE;

  /**
   * The recurring Interval code: 'MNTH', '3MNT', 'YEAR'.
   *
   * @var string
   */
  protected $recurringInterval = 'MNTH';

  /**
   * A url to redirect to after this event.
   *
   * @var \Drupal\Core\Url|null
   */
  protected $redirectUrl;

  /**
   * Constructs a new DonationEvent.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\commerce_price\Price $amount
   *   The selected donation price.
   * @param bool $anonymous
   *   TRUE if the donation should be anonymous.
   * @param string $destination
   *   'EF' for "Education Fund" or 'PAC' for "Political Action Committee".
   * @param string $source
   *   'P' for "Person" or 'F' for "Firm".
   * @param string $fund
   *   Contribution Fund code: 'AF', 'VS', 'WM', 'MJ'..
   * @param bool $is_recurring
   *   Recurring Flag.
   * @param string $recurring_interval
   *   The recurring Interval code: 'MNTH', '3MNT', 'YEAR'.
   */
  public function __construct(AccountInterface $account, Price $amount, $anonymous, $destination, $source, $fund = NULL, $is_recurring = FALSE, $recurring_interval = NULL) {
    $this->account = $account;
    $this->amount = $amount;
    $this->anonymous = $anonymous;
    $this->destination = $destination;
    $this->source = $source;
    if (!empty($fund)) {
      $this->fund = $fund;
    }
    if (!empty($is_recurring)) {
      $this->isRecurring = $is_recurring;
      $this->recurringInterval = $recurring_interval;
    }
  }

  /**
   * Gets the user account.
   *
   * @return \Drupal\user\UserInterface
   *   The user account.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Gets the amount of the donation.
   *
   * @return \Drupal\commerce_price\Price
   *   The amount of the donation.
   */
  public function getAmount() {
    return $this->amount;
  }

  /**
   * Returns TRUE if the donation should be made recurring.
   *
   * @return bool
   *   TRUE if the donation should be made recurring.
   */
  public function isRecurring() {
    return $this->isRecurring;
  }

  /**
   * Gets the Recurring Interval.
   *
   * @return string
   *   'MNTH' for Monthly, '3MNT' for Quarterly, 'YEAR' for Annually.
   */
  public function getRecurringInterval() {
    return $this->recurringInterval;
  }

  /**
   * Returns TRUE if the donation should be made anonymously.
   *
   * @return bool
   *   TRUE if the donation should be made anonymously.
   */
  public function isAnonymous() {
    return $this->anonymous;
  }

  /**
   * Gets the destination of the donation funds.
   *
   * @return string
   *   'EF' for "Education Fund" or 'PAC' for "Political Action Committee".
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * Gets the source of the donation.
   *
   * @return string
   *   'P' for "Person" or 'F' for "Firm".
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Gets a redirect url, if one is set.
   *
   * @return \Drupal\Core\Url|null
   *   The URL to which the user should be redirected.
   */
  public function getRedirectUrl() {
    return $this->redirectUrl;
  }

  /**
   * Sets the redirect url.
   *
   * @param string $url
   *   The URL to which the user should be redirected.
   */
  public function setRedirectUrl($url) {
    $this->redirectUrl = $url;
  }

  /**
   * Gets a fund code, if one is set.
   *
   * @return string|null
   *   The fund code.
   */
  public function getFund() {
    return $this->fund;
  }

  /**
   * Sets the fund code.
   *
   * @param string $fund_code
   *   The URL to which the user should be redirected.
   */
  public function setFund($fund_code) {
    $this->fund = $fund_code;
  }

}
