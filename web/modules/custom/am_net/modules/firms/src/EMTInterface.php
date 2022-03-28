<?php

namespace Drupal\am_net_firms;

use Drupal\user\UserInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the interface for Employee Management Tool objects.
 */
interface EMTInterface {

  /**
   * Get Membership Checker.
   *
   * @return \Drupal\am_net_membership\MembershipCheckerInterface
   *   The Membership Checker instance.
   */
  public function getMembershipChecker();

  /**
   * Get Membership Status Info given a user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   *
   * @return bool|array
   *   Array with the Membership Status Info, otherwise FALSE.
   */
  public function getMembershipStatusInfo(UserInterface $user = NULL);

  /**
   * Link a given user to a given Firm.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The parent firm entity.
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   */
  public function linkUserToFirm(TermInterface $firm = NULL, UserInterface $user = NULL);

  /**
   * Unlink a given user to a given Firm.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The parent firm entity.
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   */
  public function unLinkUserToFirm(TermInterface $firm = NULL, UserInterface $user = NULL);

  /**
   * Firm Administrator Access Check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return bool
   *   TRUE if the given account has access, otherwise FALSE.
   */
  public function firmAdministratorAccessCheck(AccountInterface $account);

  /**
   * Check if user has the role firm admin.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return bool
   *   TRUE if the given account has access, otherwise FALSE.
   */
  public function isFirmAdmin(AccountInterface $account);

  /**
   * Get user Summary.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   * @param bool $single_line
   *   Flag that format the firm description in a single line.
   *
   * @return string|null
   *   The User Description, Otherwise null.
   */
  public function getUserSummary(UserInterface $user = NULL, $single_line = FALSE);

  /**
   * Get Firm Description.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The user entity.
   * @param bool $single_line
   *   Flag that format the firm description in a single line.
   *
   * @return string|null
   *   The Firm Description, Otherwise null.
   */
  public function getFirmDescription(TermInterface $firm = NULL, $single_line = FALSE);

  /**
   * Get Firm Detailed Title.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The user entity.
   *
   * @return string|null
   *   The Firm Detailed title, Otherwise null.
   */
  public function getFirmDetailedTitle(TermInterface $firm = NULL);

}
