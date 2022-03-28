<?php

namespace Drupal\am_net_membership;

use Drupal\user\UserInterface;

/**
 * Defines a common interface for Membership Checker Implementations.
 */
interface MembershipCheckerInterface {

  /**
   * Check if the Current user the can complete membership checkout process.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   True if the Current user the can complete membership checkout process,
   *   otherwise FALSE.
   */
  public function userCanCompleteMembershipCheckoutProcess(UserInterface $user = NULL);

  /**
   * Check if the given user the can complete membership renewal process.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   True if the given user the can complete membership renewal process,
   *   otherwise FALSE.
   */
  public function userCanCompleteMembershipRenewalProcess(UserInterface $user = NULL);

  /**
   * Check if user can complete membership application process.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   True if the Current user the can complete membership application process,
   *   otherwise FALSE.
   */
  public function userCanCompleteMembershipApplicationProcess(UserInterface $user = NULL);

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
   * Returns TRUE if the account is authenticated.
   *
   * @return bool
   *   TRUE if the account is authenticated.
   */
  public function userIsAuthenticated();

  /**
   * Returns TRUE if the account is anonymous.
   *
   * @return bool
   *   TRUE if the account is anonymous.
   */
  public function userIsAnonymous();

  /**
   * Check if the user has available dues defined.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user has available dues defined, otherwise FALSE.
   */
  public function userHasAvailableDuesDefined(UserInterface $user = NULL);

  /**
   * Assign Membership License to an user account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   */
  public function assignMembershipLicense(UserInterface $user = NULL);

  /**
   * Check if the current user is the owner of the account being processed.
   *
   * @return bool
   *   The owner user entity.
   */
  public function isCurrentUserTheOwner();

  /**
   * Set member status to good standing.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   */
  public function setMemberStatusToGoodStanding(UserInterface $user = NULL);

  /**
   * Assign role: Member to the account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   */
  public function assignMemberRole(UserInterface $user = NULL);

  /**
   * Get Current User Object.
   *
   * @return \Drupal\user\UserInterface
   *   The current user entity.
   */
  public function getCurrentUser();

  /**
   * Get the default membership product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The current default membership product.
   */
  public function getDefaultMembershipProduct();

  /**
   * Get the default 'Payment Plan Administrative Fee' product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The current default 'payment plan administrative fee' product.
   */
  public function getDefaultPaymentPlanAdministrativeFeeProduct();

  /**
   * Get Membership License Expiration date.
   *
   * @param string $format
   *   The date format.
   *
   * @return string
   *   The membership license expiration date for example Y-m-d.
   */
  public function getMembershipLicenseExpirationDate($format = '');

  /**
   * Get end date of current fiscal year.
   *
   * @param string $format
   *   The date format.
   *
   * @return string
   *   The membership license expiration date for example Y-m-d.
   */
  public function getEndDateOfCurrentFiscalYear($format = '');

  /**
   * Get Membership License Expiration date of a given user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @return string|bool
   *   The membership license expiration date for example Y-m-d,
   *   otherwise FALSE.
   */
  public function getUserMembershipLicenseExpirationDate(UserInterface $account);

  /**
   * Get the current fiscal year.
   *
   * @return string
   *   The current fiscal year on the form: Y.
   */
  public function getCurrentFiscalYear();

  /**
   * Get Membership Price.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @return string
   *   The due price.
   */
  public function getMembershipPrice(UserInterface $account);

  /**
   * Get billing Class Service Manager.
   *
   * @return \Drupal\am_net_membership\BillingClass\BillingClassCheckerManagerInterface
   *   The billing class service manager.
   */
  public function getBillingClassCheckerManager();

  /**
   * Get the user full name.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @return string
   *   The user's full name.
   */
  public function getUserFullName(UserInterface $account);

  /**
   * Get Membership License Status.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @return string
   *   The membership license status.
   */
  public function getMembershipLicenseStatus(UserInterface $account);

  /**
   * Get the available Membership Renewal on days left.
   *
   * @return int
   *   The number of days left..
   */
  public function getAvailableMembershipRenewalOnDaysLeft();

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return int
   *   The Current user id.
   */
  public static function getCurrentUserId();

  /**
   * Set member role.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string $member_role
   *   The role ID to add.
   */
  public function setMemberRole(UserInterface $user = NULL, $member_role = NULL);

  /**
   * Set member status to good standing.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string $member_status
   *   The member status value.
   */
  public function setMemberStatus(UserInterface $user = NULL, $member_status = NULL);

  /**
   * Set Default member status to Non-member.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   */
  public function setDefaultMemberStatus(UserInterface $user = NULL);

  /**
   * Set Membership Dues.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param bool $reset
   *   Flag that determine whether the Membership dues
   *   need to be recalculated or not.
   */
  public function setMembershipDues(UserInterface &$user = NULL, $reset = TRUE);

  /**
   * Check if the user has Dues Posted for the New fiscal Year.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user has dues balance posted, Otherwise FALSE.
   */
  public function userHasDuesPostedForTheYear(UserInterface $user = NULL);

  /**
   * Check if the user has Dues Balance greater than 0.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user has dues balance  greater than 0, Otherwise FALSE.
   */
  public function userHasDuesBalance(UserInterface $user = NULL);

  /**
   * Get the posted Dues balance from the API for a given user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return float|bool
   *   The dues balance, Otherwise FALSE.
   */
  public function getUserDuesBalance(UserInterface $user = NULL);

  /**
   * Unlock user sync.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   */
  public function unlockUserSync(UserInterface $user);

  /**
   * Get the Billing Class Code relate to the user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return string|bool
   *   TRUE The billing class code, otherwise FALSE.
   */
  public function getBillingClassCode(UserInterface $user = NULL);

  /**
   * Check if user is terminated member on re-apply.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if user is terminated member on re-apply, otherwise FALSE.
   */
  public function isTerminatedMemberOnReApply(UserInterface $user = NULL);

}
