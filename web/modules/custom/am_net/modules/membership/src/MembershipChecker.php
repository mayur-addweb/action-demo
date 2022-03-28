<?php

namespace Drupal\am_net_membership;

use Drupal\commerce_price\Price;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\licensing\Entity\License;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\licensing\Entity\LicenseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\am_net_membership\BillingClass\BillingClassCodeTrait;

/**
 * The Default Membership Class checker implementation.
 */
class MembershipChecker implements MembershipCheckerInterface, EntityOwnerInterface {

  use BillingClassCodeTrait, UserSyncTrait;

  /**
   * Defines an account interface which represents the current user.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected $user = NULL;

  /**
   * The billing class service manager.
   *
   * @var \Drupal\am_net_membership\BillingClass\BillingClassCheckerManagerInterface
   */
  protected $billingClassServiceManager = NULL;

  /**
   * The product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productStorage;

  /**
   * The am_net_membership configuration settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Constructs a new MembershipChecker object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->productStorage = $entity_type_manager->getStorage('commerce_product');
    $this->settings = $config_factory->get('am_net_membership.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function setMembershipDues(UserInterface &$user = NULL, $reset = TRUE) {
    $missing_fields_messages = t('Please fill in all the required fields of the membership form in order to determine your membership dues.');
    // Verify if the user has completed the basic fields.
    // Basic Field 1: Billing Class Code.
    if ($reset || !$this->hasBillingClassDefined($user)) {
      /** @var \Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface $billingClassChecker */
      $billingClassChecker = $this->getBillingClassCheckerManager()->getChecker();
      // Get the Billing code from the checker given a user profile.
      $billingClassCode = $billingClassChecker->getCode($user);
      if ($billingClassCode != FALSE) {
        $user->set('field_amnet_billing_class', $billingClassCode);
      }
      else {
        drupal_set_message($missing_fields_messages, 'warning');
      }
    }
    else {
      $billingClassCode = $this->getBillingClassCode($user);
    }
    // Basic Field 2: Membership Due Price.
    if (empty($billingClassCode) || $billingClassCode < 0) {
      drupal_set_message($missing_fields_messages, 'warning');
    }
    else {
      // Get the Membership Due Price from AM.net Service based on
      // the "billing class" value and current date.
      $price = \Drupal::service('am_net.client')->getMembershipDuePrice($billingClassCode, $month = date('F'));
      if (!is_null($price)) {
        // Save Billing class Code and Dues lookup.
        $user->set('field_amnet_dues_lookup', $price);
      }
      else {
        $message = t('We can not process the list of Dues Rates at this time, please try it later, If the problem persists, contact the administrator.');
        drupal_set_message($message, 'warning');
      }
    }
  }

  /**
   * Implements Check Membership Status - On user pre-save event.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   */
  public function checkMembershipStatusOnUserPreSave(UserInterface &$user = NULL) {
    // Check Member Status.
    $member_status = $this->getMembershipStatus($user);
    if (empty($member_status)) {
      // Set the Default Member Status.
      $user->set('field_member_status', MemberStatusCodesInterface::NON_MEMBER);
    }
    // Validate Membership License.
    if ($this->isMemberInGoodStanding($user)) {
      // Grant Member Role.
      if (!$user->hasRole(MemberStatusCodesInterface::ROLE_ID_MEMBER)) {
        $user->addRole(MemberStatusCodesInterface::ROLE_ID_MEMBER);
      }
    }
    else {
      // Otherwise Remove Member Role.
      if ($user->hasRole(MemberStatusCodesInterface::ROLE_ID_MEMBER)) {
        $user->removeRole(MemberStatusCodesInterface::ROLE_ID_MEMBER);
      }
    }
  }

  /**
   * Implements Check Membership Status - On user post-save event.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   */
  public function checkMembershipStatusOnUserPostSave(UserInterface $user = NULL) {
    if (is_null($user) || $user->isNew()) {
      return;
    }
    // Check post-save Member Status and validate Membership License.
    if ($this->isMemberInGoodStanding($user) || $this->isMemberWithDuesBalance($user)) {
      // Ensure that member has a active license.
      if (!$this->hasActiveMembershipLicense($user)) {
        // Create the License.
        $this->assignMembershipLicense($user);
      }
    }
    else {
      // Otherwise expire membership license.
      if ($this->hasActiveMembershipLicense($user)) {
        // Remove the License.
        $this->removeMembershipLicense($user);
      }
    }
  }

  /**
   * Implements Check Membership License Status.
   *
   * @param \Drupal\licensing\Entity\LicenseInterface|null $license
   *   The customer entity.
   */
  public function checkMembershipLicenseStatus(LicenseInterface $license = NULL) {
    if (!$license) {
      return;
    }
    $save_user_changes = FALSE;
    $user = $license->getOwner();
    if ($license->isActive()) {
      // User should be in good standing.
      // Grant Member Role.
      if (!$user->hasRole(MemberStatusCodesInterface::ROLE_ID_MEMBER)) {
        $user->addRole(MemberStatusCodesInterface::ROLE_ID_MEMBER);
        $save_user_changes = TRUE;
      }
      $user_member_status = $this->getMembershipStatus($user);
      if (!in_array($user_member_status, [MemberStatusCodesInterface::MEMBER_IN_GOOD_STANDING, MemberStatusCodesInterface::MEMBER_WITH_A_DUES_BALANCE])) {
        // Set membership status to good standing.
        $user->set('field_member_status', MemberStatusCodesInterface::MEMBER_IN_GOOD_STANDING);
        $save_user_changes = TRUE;
      }
    }
    else {
      // License was expired (Manually or automatically).
      // Remove Member Role.
      if ($user->hasRole(MemberStatusCodesInterface::ROLE_ID_MEMBER)) {
        $user->removeRole(MemberStatusCodesInterface::ROLE_ID_MEMBER);
        $save_user_changes = TRUE;
      }
      // Set the member status to no-member.
      if ($this->isMemberInGoodStanding($user) || $this->isMemberWithDuesBalance($user)) {
        $user->set('field_member_status', MemberStatusCodesInterface::NON_MEMBER);
        $save_user_changes = TRUE;
      }
    }
    // Save User Changes.
    if ($save_user_changes) {
      $user->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userCanCompleteMembershipCheckoutProcess(UserInterface $user = NULL) {
    if (is_null($user)) {
      $user = $this->getOwner();
    }
    // User can Apply for a membership if is not a existing members,
    // or if is a Terminated Member.
    return !($this->isMemberInGoodStanding($user) || $this->isMemberWithDuesBalance($user));
  }

  /**
   * {@inheritdoc}
   */
  public function userCanCompleteMembershipRenewalProcess(UserInterface $user = NULL) {
    if ($this->isTerminatedMember($user)) {
      return TRUE;
    }
    // Check if this user has any Dues posted for the year.
    if ($this->userHasDuesPostedForTheYear($user) && !$this->userHasActivePaymentPlanWithBalance($user)) {
      // We post dues annually in March (to try to get people to renew early),
      // although our fiscal year technically starts May 1. The site should
      // allow people to renew as soon as dues are posted.
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userHasActivePaymentPlanWithBalance(UserInterface $user = NULL) {
    $year = $this->getCurrentFiscalYear();
    $am_net = \Drupal::service('am_net.client');
    $plan = $am_net->getUserActivePaymentPlan($user, $year);
    if (empty($plan)) {
      // The user has no active payment plan for the given fiscal year.
      return FALSE;
    }
    // Get the amount: 'Total to Pay'.
    $total_to_pay = $plan['TotalToPay'] ?? '0';
    $total_to_pay = (string) $total_to_pay;
    // Get the amount: 'Total Paid'.
    $total_paid = $plan['TotalPaid'] ?? '0';
    $total_paid = (string) $total_paid;
    // Compare paid vs total to pay to determine if has balance.
    $zero = new Price('0', 'USD');
    $total_to_pay_price = new Price($total_to_pay, 'USD');
    $total_paid_price = new Price($total_paid, 'USD');
    $diff = $total_to_pay_price->subtract($total_paid_price);
    return $diff->greaterThan($zero);
  }

  /**
   * {@inheritdoc}
   */
  public function userHasDuesPostedForTheYear(UserInterface $user = NULL) {
    $now = strtotime('now');
    $date_of_dues_publication_for_existing_members = $this->getDateOfDuesPublicationForExistingMembers();
    if ($now < $date_of_dues_publication_for_existing_members) {
      // Dues are posted for all existing members at some point in March.
      return FALSE;
    }
    // If now is >= 01 March is possible that the dues are posted
    // for this member check on AM.Net.
    return $this->userHasDuesBalance($user);
  }

  /**
   * {@inheritdoc}
   */
  public function userHasDuesBalance(UserInterface $user = NULL) {
    return $this->getUserDuesBalance($user) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserDues(UserInterface $user = NULL) {
    if (!$user) {
      return [];
    }
    $stored_entities = &drupal_static(__METHOD__, []);
    // Get the AM.net ID.
    $key = $user->get('field_amnet_id')->getString();
    $key = trim($key);
    if (!isset($stored_entities[$key])) {
      $stored_entities[$key] = \Drupal::service('am_net.client')->getUserDues($user);
    }
    return $stored_entities[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function getUserDuesBalance(UserInterface $user = NULL) {
    if (!$user) {
      return FALSE;
    }
    $info = $this->getUserDues($user);
    // Terminated users: Use the the amount they were originally billed.
    if ($this->isTerminatedMember($user)) {
      $fee = $info['Billings'] ?? NULL;
      $termination_amount = $info['TerminationWriteOffAmount'] ?? NULL;
      if (is_numeric($fee) && is_numeric($termination_amount) && ($termination_amount > 0)) {
        // Take into account any discounts.
        $diff = $fee - $termination_amount;
        if ($diff > 0) {
          $fee = $termination_amount;
        }
      }
    }
    else {
      $fee = $info['Balance'] ?? NULL;
    }
    if (is_null($fee) || !is_numeric($fee)) {
      return FALSE;
    }
    return floatval($fee);
  }

  /**
   * {@inheritdoc}
   */
  public function isTerminatedMemberOnReApply(UserInterface $user = NULL) {
    if (!$user) {
      return FALSE;
    }
    if (!$this->isTerminatedMember($user)) {
      return FALSE;
    }
    $info = $this->getUserDues($user);
    if (empty($info)) {
      return FALSE;
    }
    $last_billing_date = $info['LastBilling'] ?? NULL;
    if (empty($last_billing_date)) {
      return FALSE;
    }
    $current_time = new DrupalDateTime($last_billing_date);
    $current_fiscal_date = $this->getEndDateOfCurrentFiscalYear('Y-m-d\TH:i:s');
    $current_fiscal_time = new DrupalDateTime($current_fiscal_date);
    $diff = $current_time->diff($current_fiscal_time);
    $diff_year = $diff->y ?? 0;
    return ($diff_year >= 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getDateOfDuesPublicationForExistingMembers() {
    // Dues are posted for all existing members at some point in March
    // So by default the star period is at March 1 of the current year.
    $current_year = date('Y');
    return strtotime("01 March {$current_year}");
  }

  /**
   * {@inheritdoc}
   */
  public function isCurrentMonthJanuary() {
    return (date('m') == '01');
  }

  /**
   * {@inheritdoc}
   */
  public function jan31Time($year) {
    return strtotime("01 February {$year}");
  }

  /**
   * {@inheritdoc}
   */
  public function feb1Time($year) {
    return strtotime("01 February {$year}");
  }

  /**
   * {@inheritdoc}
   */
  public function may1Time($year) {
    return strtotime("01 May {$year}");
  }

  /**
   * {@inheritdoc}
   */
  public function april30Time($year) {
    return strtotime("01 April {$year}");
  }

  /**
   * {@inheritdoc}
   */
  public function userCanCompleteMembershipApplicationProcess(UserInterface $user = NULL) {
    // Terminated users are forced to re-apply for a membership if the date of
    // attempted transaction is AFTER the last day of the fiscal year dues
    // were posted.
    if ($this->isTerminatedMemberOnReApply($user)) {
      return TRUE;
    }
    elseif ($this->isTerminatedMember($user)) {
      return FALSE;
    }
    elseif ($this->isMemberWithDuesBalance($user)) {
      return FALSE;
    }
    // Validate Memberships Fields.
    if ($this->isMemberInGoodStanding($user)) {
      return FALSE;
    }
    // Check the role.
    if ($user->hasRole(MemberStatusCodesInterface::ROLE_ID_MEMBER)) {
      return FALSE;
    }
    // Check Membership.
    /** @var \Drupal\licensing\Entity\LicenseInterface $order */
    $license = $this->getMembershipLicense($user);
    if ($license != FALSE) {
      // The user already has a membership license!.
      $license_status = $license->getStatus();
      // Check the license status.
      $values = [
        LICENSE_EXPIRED,
        LICENSE_SUSPENDED,
        LICENSE_REVOKED,
      ];
      if (in_array($license_status, $values)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipStatusInfo(UserInterface $user = NULL) {
    if (!$user) {
      return FALSE;
    }
    $info = [
      'is_membership_application' => FALSE,
      'user_has_dues_defined' => FALSE,
      'is_membership_renewal' => FALSE,
      'is_member_in_good_standing' => FALSE,
      'membership_status_message' => '',
      'dues_amount' => '',
    ];
    if ($this->userCanCompleteMembershipApplicationProcess($user)) {
      $info['is_membership_application'] = TRUE;
      // Check if the user has available dues defined.
      $info['user_has_dues_defined'] = $this->userHasAvailableDuesDefined($user);
      // Get the value from the dues lookup field.
      $info['dues_amount'] = $this->getTextFieldValue($user, 'field_amnet_dues_lookup');;
    }
    elseif ($this->userCanCompleteMembershipRenewalProcess($user)) {
      $info['is_membership_renewal'] = TRUE;
      // Get the value from the API Dues are posted for all existing members
      // at some point in March.
      $info['dues_amount'] = strval($this->getUserDuesBalance($user));
    }
    else {
      // User is in good standing with an active license, no further
      // action is required.
      // Set a user-friendly message.
      $info['is_member_in_good_standing'] = TRUE;
      $license_expiration_date = $this->getUserMembershipLicenseExpirationDate($user);
      if (empty($license_expiration_date)) {
        // Something goes wrong, user is a member in good standing,
        // but do not have a license?.
        $license_expiration_date = $this->getMembershipLicenseExpirationDate();
      }
      else {
        $now = strtotime('now');
        $license_expiration_date = ($now < $license_expiration_date) ? date('F j, Y', $license_expiration_date) : NULL;
      }
      if (empty($license_expiration_date)) {
        $message = NULL;
      }
      else {
        $message = t('Thank you for being a member of the VSCPA. Your membership is good through @date.', ['@date' => $license_expiration_date]);
      }
      $info['membership_status_message'] = $message;
    }
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function userHasAvailableDuesDefined(UserInterface $user = NULL) {
    if (is_null($user)) {
      $user = $this->getOwner();
    }
    return ($this->hasBillingClassDefined($user) && $this->hasDuesValueDefined($user));
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentUserId() {
    return \Drupal::currentUser()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function userIsAuthenticated() {
    return \Drupal::currentUser()->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function userIsAnonymous() {
    return \Drupal::currentUser()->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->user = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->user->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->user = User::load($uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingClassCheckerManager() {
    if (is_null($this->billingClassServiceManager)) {
      $this->billingClassServiceManager = \Drupal::service('am_net_membership.billing_class_checker_manager');
    }
    return $this->billingClassServiceManager;
  }

  /**
   * {@inheritdoc}
   */
  public function isCurrentUserTheOwner() {
    $user = $this->getOwner();
    return ($user && ($user->id() == $this->getCurrentUserId()));
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberStatusToGoodStanding(UserInterface $user = NULL) {
    $this->setMemberStatus($user, MemberStatusCodesInterface::MEMBER_IN_GOOD_STANDING);
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberStatus(UserInterface $user = NULL, $member_status = NULL) {
    if (!is_null($user) && !is_null($member_status)) {
      // Set member status.
      $user->set('field_member_status', $member_status);
      $user->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultMemberStatus(UserInterface $user = NULL) {
    if (!is_null($user)) {
      if (empty($this->getMembershipStatus($user))) {
        // Set the Default Membership Status.
        $user->set('field_member_status', MemberStatusCodesInterface::APPLICANT_FOR_MEMBERSHIP);
        $user->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function assignMemberRole(UserInterface $user = NULL) {
    $this->setMemberRole($user, MemberStatusCodesInterface::ROLE_ID_MEMBER);
  }

  /**
   * {@inheritdoc}
   */
  public function assignMembershipLicense(UserInterface $user = NULL) {
    if ($user) {
      // Since user can only renew their membership once a year we
      // will use any existing license that the user has created.
      $license = $this->getMembershipLicense($user);
      if ($license == FALSE) {
        $license = License::create(['type' => 'membership']);
      }
      // Get the membership expiration date using the default format 'Y-m-d'.
      $membership_expiration_date = $this->getDuesPaidThroughDate($user);
      if (empty($membership_expiration_date)) {
        $membership_expiration_date = $this->getMembershipLicenseExpirationDate();
      }
      $license->setStatus(LICENSE_ACTIVE);
      $license->set('expires_automatically', FALSE);
      $license->set('expiry', strtotime($membership_expiration_date));
      $license->set('licensed_entity', AM_NET_MEMBERSHIP_LICENSED_ENTITY);
      $license->setOwner($user);
      $license->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeMembershipLicense(UserInterface $user = NULL) {
    if ($user) {
      // Since user can only renew their membership once a year we
      // will use any existing license that the user has created.
      $license = $this->getMembershipLicense($user);
      if ($license) {
        $license->setStatus(LICENSE_EXPIRED);
        $license->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasActiveMembershipLicense(UserInterface $user = NULL) {
    if (!$user) {
      return FALSE;
    }
    $license = $this->getMembershipLicense($user);
    if (!$license) {
      return FALSE;
    }
    $license_expiration_date = $license->get('expiry')->getString();
    if (empty($license_expiration_date)) {
      return FALSE;
    }
    $active_membership_license = $license->getStatus() == LICENSE_ACTIVE;
    if (!$active_membership_license) {
      return FALSE;
    }
    $dues_paid_through_date = $user->get('field_amnet_dues_paid_through')->getString();
    if (empty($dues_paid_through_date)) {
      return TRUE;
    }
    $dues_paid_through_date = strtotime($dues_paid_through_date);
    // Check Dates.
    if ($license_expiration_date < $dues_paid_through_date) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberRole(UserInterface $user = NULL, $member_role = NULL) {
    if ($user && $member_role) {
      // Set member Role.
      $user->addRole($member_role);
      $user->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultMembershipProduct() {
    $default_membership_product_uuid = $this->settings->get('default_membership_product');
    $product = $this->productStorage->loadByProperties([
      'uuid' => $default_membership_product_uuid,
    ]);

    return current($product);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultPaymentPlanAdministrativeFeeProduct() {
    $default_membership_product_uuid = $this->settings->get('default_payment_plan_admin_fee_product');
    $product = $this->productStorage->loadByProperties([
      'uuid' => $default_membership_product_uuid,
    ]);

    return current($product);
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipPrice(UserInterface $account) {
    // Membership status info.
    $membership_status_info = $this->getMembershipStatusInfo($account);
    // Determine if the user is in a Membership Application or
    // in a Membership Renewal.
    $is_renewal = ($membership_status_info['is_membership_renewal'] == TRUE);
    // Get Dues based on if is a Application or is a Renewal.
    if ($is_renewal) {
      // Get the value from the API Dues are posted for all existing members
      // at some point in March.
      $dues = strval($this->getUserDuesBalance($account));
    }
    else {
      // Get the value from the dues lookup field.
      $dues = $this->getTextFieldValue($account, 'field_amnet_dues_lookup');;
    }
    return $dues;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipRenewalPrice(UserInterface $account) {
    // Get the value from the API Dues are posted for all existing members
    // at some point in March.
    $fee = $this->getUserDuesBalance($account);
    if (is_null($fee) || ($fee === FALSE)) {
      return NULL;
    }
    return strval($fee);
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDateOfCurrentFiscalYear($format = '') {
    // Membership license by default expires April 30 of next year
    // or current fiscal year depending of the fiscal year.
    $config = \Drupal::config('am_net_membership.billing_class_checker_manager');
    $date = date_parse($config->get('license_expiration_month'));
    $expirationMonth = (int) isset($date['month']) ? $date['month'] : '4';
    $expirationDay = $config->get('license_expiration_day');
    $expirationYear = $this->getCurrentFiscalYear();
    // Format the date.
    $date = "{$expirationYear}-{$expirationMonth}-{$expirationDay}";
    $format = (empty($format)) ? 'Y-m-d' : $format;
    return date($format, strtotime($date));
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentFiscalYear() {
    $current_time = new DrupalDateTime('now');
    $currentMonth = (int) $current_time->format('m');
    $month_dues_posted = 3;
    if (($currentMonth >= 1) && ($currentMonth < $month_dues_posted)) {
      // Expiration is the same fiscal year.
      $fiscal_year = $expirationYear = date('Y');
    }
    else {
      // Expiration is the next fiscal year because we are in February,
      // March or April.
      $fiscal_year = date('Y') + 1;
    }
    return $fiscal_year;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipLicenseExpirationDate($format = '') {
    // Membership license by default expires April 30 of next year
    // or current fiscal year.
    $config = \Drupal::config('am_net_membership.billing_class_checker_manager');
    $date = date_parse($config->get('license_expiration_month'));
    $expirationMonth = (int) isset($date['month']) ? $date['month'] : '4';
    $expirationDay = $config->get('license_expiration_day');
    $current_time = new DrupalDateTime('now');
    $currentMonth = (int) $current_time->format('m');
    if ($currentMonth === 1) {
      // Expiration is the same fiscal year because we are in January.
      $expirationYear = date('Y');
    }
    elseif ($currentMonth >= 2 && $currentMonth <= 4) {
      // Expiration is the next fiscal year because we are in February,
      // March or April.
      $expirationYear = date('Y') + 1;
    }
    else {
      // Expiration is the same fiscal year because we are after May 1st.
      $expirationYear = date('Y') + 1;
    }
    $expiration_date = "{$expirationYear}-{$expirationMonth}-{$expirationDay}";
    $license_expiration_date = date('Y-m-d', strtotime($expiration_date));
    $format = (empty($format)) ? 'Y-m-d' : $format;
    $membership_expiration_date = date($format, strtotime($license_expiration_date));
    return $membership_expiration_date;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMembershipLicenseExpirationDate(UserInterface $user = NULL) {
    $license = $this->getMembershipLicense($user);
    if ($license == FALSE) {
      return FALSE;
    }
    $field_name = 'expiry';
    $license_expiration_date = $license->get($field_name)->getString();
    if (empty($license_expiration_date)) {
      return FALSE;
    }
    $timestamp = $this->isTimestamp($license_expiration_date) ? $license_expiration_date : strtotime($license_expiration_date);
    return $timestamp;
  }

  /**
   * Checks if a given string is a valid timestamp.
   *
   * @param string $timestamp
   *   The given Timestamp to validate.
   *
   * @return bool
   *   TRUE if the given string is a timestamp, Otherwise FALSE.
   */
  public function isTimestamp($timestamp = '') {
    if (empty($timestamp)) {
      return FALSE;
    }
    $check = (is_int($timestamp) || is_float($timestamp)) ? $timestamp : (string) (int) $timestamp;
    return ($check === $timestamp) && ((int) $timestamp <= PHP_INT_MAX) && ((int) $timestamp >= ~PHP_INT_MAX);
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipLicenseExpirationDateInDays(License $license = NULL) {
    $date = '';
    $format = 'F j, Y';
    $field_name = 'expiry';
    $field = $license->get($field_name);
    if ($field) {
      $timestamp = $field->getString();
      $date = date($format, $timestamp);
    }
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableMembershipRenewalOnDaysLeft() {
    $config = \Drupal::config('am_net_membership.billing_class_checker_manager');
    $membership_renewal_on_days_left = $config->get('membership_renewal_on_days_left');
    return $membership_renewal_on_days_left ?? 30;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipLicenseStatus(UserInterface $user = NULL) {
    $renew_url = Url::fromRoute('am_net_membership.renewal.page');
    $join_now_url = Url::fromRoute('am_net_membership.application');
    $link = Link::fromTextAndUrl(t('Join Or Renew today'), $join_now_url)->toString();
    $message_membership_application = t("You are not a member. @action_link to take advantage of the many benefits of membership.", ['@action_link' => $link]);
    $membership_status_info = $this->getMembershipStatusInfo($user);
    $membership_status = '';
    /** @var \Drupal\licensing\Entity\LicenseInterface $license */
    $license = $this->getMembershipLicense($user);
    if ($license == FALSE) {
      // The user has not completed the membership payment.
      $membership_status = $message_membership_application;
    }
    else {
      // The user already has a membership license!.
      $license_status = $license->getStatus();

      switch ($license_status) {
        case LICENSE_ACTIVE:
          $expiration_date = $this->getMembershipLicenseExpirationDateInDays($license);
          if ($membership_status_info['is_membership_renewal']) {
            $link = Link::fromTextAndUrl(t('Renew My Membership Now'), $renew_url)->toString();
            $membership_status_msg = t("Your membership expires on @expiration_date. @action_link.", ['@action_link' => $link, '@expiration_date' => $expiration_date]);
          }
          else {
            $membership_status_msg = t("Your membership expires on @expiration_date.", ['@expiration_date' => $expiration_date]);
          }
          $membership_status = $membership_status_msg;
          break;

        case LICENSE_PENDING:
        case LICENSE_CREATED:
          $membership_status = $message_membership_application;
          break;

        case LICENSE_EXPIRED:
        case LICENSE_SUSPENDED:
        case LICENSE_REVOKED:
          $link = Link::fromTextAndUrl(t('Renew My Membership Now'), $renew_url)->toString();
          $membership_status = t("Your membership has expired. @action_link.", ['@action_link' => $link]);
          break;

      }
    }
    return $membership_status;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipLicense(UserInterface $user = NULL) {
    $license = FALSE;
    if (!is_null($user)) {
      $entity = 'license';
      $type = 'membership';
      $user_id = $user->id();
      $query = \Drupal::entityQuery($entity);
      $query->condition('type', $type);
      $query->condition('user_id', $user_id);
      $ids = $query->execute();
      if (!empty($ids)) {
        $id = current($ids);
        $license = License::load($id);
      }
    }
    return $license;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentUser() {
    $user = $this->getOwner();
    if (is_null($user)) {
      $uid = $this->getCurrentUserId();
      if ($uid != 0) {
        $user = User::load($uid);
        $this->setOwner($user);
      }
    }
    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserFullName(UserInterface $user = NULL) {
    $suffix = $this->getTextFieldValue($user, 'field_name_suffix');
    $first_name_or_initial = $this->getTextFieldValue($user, 'field_givenname');
    $middle_name_or_initial = $this->getTextFieldValue($user, 'field_additionalname');
    $last_name = $this->getTextFieldValue($user, 'field_familyname');
    $names = [
      $first_name_or_initial,
      $middle_name_or_initial,
      $last_name,
      $suffix,
    ];
    return implode(' ', $names);
  }

  /**
   * Check if user has appropriate fields values.
   *
   * Check if user has appropriate fields values for create
   * the membership product.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return bool
   *   TRUE if the membership information is in a valid format.
   */
  public function isValidMembershipData(UserInterface $user) {
    // @todo.
    return TRUE;
  }

}
