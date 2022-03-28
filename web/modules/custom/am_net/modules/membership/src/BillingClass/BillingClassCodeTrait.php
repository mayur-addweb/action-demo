<?php

namespace Drupal\am_net_membership\BillingClass;

use Drupal\user\UserInterface;
use Drupal\am_net_membership\MemberStatusCodesInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\node\Entity\Node;

/**
 * The Billing Class Code Helper trait implementation.
 */
trait BillingClassCodeTrait {

  /**
   * Get Text field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param string $field_name
   *   The Field Name.
   *
   * @return string|array|null
   *   The Field Text Values.
   */
  public function getTextFieldValue(UserInterface $user = NULL, $field_name = '') {
    $value = NULL;
    if (!is_null($user) && !empty($field_name) && $user->hasField($field_name)) {
      $field = $user->get($field_name);
      if ($field) {
        $values = $field->getValue();
        if (is_array($values) && !empty($values)) {
          $value = [];
          foreach ($values as $delta => $val) {
            $value[] = is_array($val) ? current($val) : $val;
          }
        }
      }
    }
    $result = $value;
    if (is_array($value)) {
      $result = (count($value) > 1) ? $value : current($value);
    }
    return $result;
  }

  /**
   * Get Address field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param string $field_name
   *   The Field Name.
   *
   * @return array
   *   The Field Address Value.
   */
  public function getAddressFieldValue(UserInterface $user = NULL, $field_name = '') {
    $value = [];
    if (!is_null($user) && !empty($field_name) && $user->hasField($field_name)) {
      $field = $user->get($field_name);
      if ($field) {
        $value = $field->getValue();
        $value = !empty($value) ? current($value) : [];
      }
    }
    return $value;
  }

  /**
   * Get Target ID field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param string $field_name
   *   The Field Name.
   *
   * @return int|array
   *   The Field Target IDs.
   */
  public function getTargetIdValue(UserInterface $user = NULL, $field_name = '') {
    return $this->getTextFieldValue($user, $field_name);
  }

  /**
   * Get field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param string $field_name
   *   The Field Name.
   *
   * @return int|array|null
   *   The Field value.
   */
  public function getFieldValue(UserInterface $user = NULL, $field_name = '') {
    if (!$user->hasField($field_name)) {
      return NULL;
    }
    $field_type = $user->get($field_name)->getFieldDefinition()->getType();
    $value = NULL;
    switch ($field_type) {
      case 'string':
      case 'datetime':
      case 'telephone':
      case 'email':
      case 'list_string':
        $value = $this->getTextFieldValue($user, $field_name);
        break;

      case 'entity_reference':
        $value = $this->getTargetIdValue($user, $field_name);
        break;

      case 'address':
        $value = $this->getAddressFieldValue($user, $field_name);
        break;

      default:
        $value = $this->getTextFieldValue($user, $field_name);
    }
    return $value;
  }

  /**
   * Get Undergraduate College or University field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param bool $label
   *   Flag used to load the location name.
   *
   * @return string
   *   The Undergraduate College or University.
   */
  public function getUndergraduateCollegeOrUniversity(UserInterface $user = NULL, $label = FALSE) {
    $value = $this->getTargetIdValue($user, 'field_undergrad_loc');
    if (!empty($value) && $label) {
      if ($value == '234') {
        // - OTHER - please enter below.
        $value = $this->getTextFieldValue($user, 'field_other_undergraduate');
      }
      else {
        // Load the location name.
        $node = Node::load($value);
        if ($node) {
          $value = $node->label();
        }
      }
    }
    return $value;
  }

  /**
   * Get Graduate College or University field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param bool $label
   *   Flag used to load the location name.
   *
   * @return string
   *   The Undergraduate College or University.
   */
  public function getGraduateCollegeOrUniversity(UserInterface $user = NULL, $label = FALSE) {
    $value = $this->getTargetIdValue($user, 'field_graduate_loc');
    if (!empty($value) && $label) {
      if ($value == '234') {
        // - OTHER - please enter below.
        $value = $this->getTextFieldValue($user, 'field_other_graduate');
      }
      else {
        // Load the location name.
        $node = Node::load($value);
        if ($node) {
          $value = $node->label();
        }
      }
    }
    return $value;
  }

  /**
   * Get Undergraduate Date field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return string
   *   The Undergraduate Date.
   */
  public function getUndergraduateDate(UserInterface $user = NULL) {
    return $this->getTextFieldValue($user, 'field_undergrad_date');
  }

  /**
   * Get Original Date of Virginia Certification field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return string
   *   The Original Date of Virginia Certification.
   */
  public function getOriginalDateOfVirginiaCertification(UserInterface $user = NULL) {
    return $this->getTextFieldValue($user, 'field_cert_va_date');
  }

  /**
   * Get Original Date of Out-of-State Certification field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return string
   *   The Original Date of Out-of-State Certification.
   */
  public function getOriginalDateOfOutOfStateCertification(UserInterface $user = NULL) {
    return $this->getTextFieldValue($user, 'field_cert_other_date');
  }

  /**
   * Get Membership Status field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return string
   *   The Membership Status.
   */
  public function getMembershipStatus(UserInterface $user = NULL) {
    return $this->getTextFieldValue($user, 'field_member_status');
  }

  /**
   * Get Graduate Date field value.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return string
   *   The Graduate Date.
   */
  public function getGraduateDate(UserInterface $user = NULL) {
    return $this->getTextFieldValue($user, 'field_grad_date');
  }

  /**
   * Check if the given user is licensed.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the licensed in State Only, otherwise FALSE.
   */
  public function isLicensed(UserInterface $user = NULL) {
    $licensed = $this->getTextFieldValue($user, 'field_licensed');
    return ($licensed == 'Y');
  }

  /**
   * Check if licensed In State Only.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the licensed in State Only, otherwise FALSE.
   */
  public function isLicensedInStateOnly(UserInterface $user = NULL) {
    $licensed_in = $this->getTextFieldValue($user, 'field_licensed_in');
    return $licensed_in == BillingClassCodeInterface::LICENSED_IN_STATE_ONLY;
  }

  /**
   * Check if licensed In Out-Of-State Only.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the licensed in Out-Of-State Only, otherwise FALSE.
   */
  public function isLicensedInOutOfStateOnly(UserInterface $user = NULL) {
    $licensed_in = $this->getTextFieldValue($user, 'field_licensed_in');
    return $licensed_in == BillingClassCodeInterface::LICENSED_IN_OUT_OF_STAT_ONLY;
  }

  /**
   * Check if licensed In and Out-Of-State.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the licensed in and Out-Of-State, otherwise FALSE.
   */
  public function isLicensedInAndOutOfState(UserInterface $user = NULL) {
    $licensed_in = $this->getTextFieldValue($user, 'field_licensed_in');
    return $licensed_in == BillingClassCodeInterface::LICENSED_IN_AND_OUT_OF_STATE;
  }

  /**
   * Check if user is Certified.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if has a valid licensed in value, otherwise FALSE.
   */
  public function isCertified(UserInterface $user = NULL) {
    return $this->isLicensedInStateOnly($user) || $this->isLicensedInOutOfStateOnly($user) || $this->isLicensedInAndOutOfState($user);
  }

  /**
   * Check if user is Membership Selection is CPA.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Membership Selection is CPA, otherwise FALSE.
   */
  public function isCertifiedPublicAccountant(UserInterface $user = NULL) {
    $membership_selection = $this->getTextFieldValue($user, 'field_member_select');
    return $membership_selection == BillingClassCodeInterface::MEMBERSHIP_SELECTION_LICENSED_CPA;
  }

  /**
   * Check if user is a unlicensed Professional.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if user is a unlicensed Professional, otherwise FALSE.
   */
  public function isUnlicensedProfessional(UserInterface $user = NULL) {
    $membership_selection = $this->getTextFieldValue($user, 'field_member_select');
    return $membership_selection == BillingClassCodeInterface::MEMBERSHIP_SELECTION_UNLICENSED_PROFESSIONAL;
  }

  /**
   * Check if user is a college student.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if user is a college student, otherwise FALSE.
   */
  public function isCollegeStudent(UserInterface $user = NULL) {
    $membership_selection = $this->getTextFieldValue($user, 'field_member_select');
    return $membership_selection == BillingClassCodeInterface::MEMBERSHIP_SELECTION_COLLEGE_STUDENT;
  }

  /**
   * Check if user is no active working.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user is no active working, otherwise FALSE.
   */
  public function isNoActiveWorking(UserInterface $user = NULL) {
    if (empty($user)) {
      return FALSE;
    }
    $is_retired = $this->isEmploymentStatusRetired($user);
    $is_leave_of_absence = $this->isEmploymentStatusLeaveOfAbsence($user);
    $is_un_employed = $this->isEmploymentStatusUnemployed($user);
    return ($is_retired || $is_leave_of_absence || $is_un_employed);
  }

  /**
   * Check if user is Firm Administrator including Membership Qualify.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if user Membership Qualify is Firm Administrator, otherwise FALSE.
   */
  public function isFirmAdministrator(UserInterface $user = NULL) {
    $membership_qualify = $this->getTextFieldValue($user, 'field_membership_qualify');
    return ($membership_qualify == BillingClassCodeInterface::MEMBERSHIP_QUALIFY_FIRM_ADMINISTRATOR) && $user->hasRole(BillingClassCodeInterface::ROL_FIRM_ADMINISTRATOR);
  }

  /**
   * Check if user Membership Qualify is Firm Administrator.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if user Membership Qualify is Firm Administrator, otherwise FALSE.
   */
  public function isMembershipQualificationFirmAdministrator(UserInterface $user = NULL) {
    $membership_qualify = $this->getTextFieldValue($user, 'field_membership_qualify');
    return ($membership_qualify == BillingClassCodeInterface::MEMBERSHIP_QUALIFY_FIRM_ADMINISTRATOR);
  }

  /**
   * Check if user Membership Qualify is Pursuing a CPA license.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if user Membership Qualify is Pursuing a CPA license,
   *   otherwise FALSE.
   */
  public function isPursuingCertifiedPublicAccountantLicense(UserInterface $user = NULL) {
    $membership_qualify = $this->getTextFieldValue($user, 'field_membership_qualify');
    return $membership_qualify == BillingClassCodeInterface::MEMBERSHIP_QUALIFY_PURSUING_CERTIFIED_PUBLIC_ACCOUNTANT_LICENSE;
  }

  /**
   * Check if user Membership Qualify is Employed by a CPA.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if user Membership Qualify is Employed by a CPA,
   *   otherwise FALSE.
   */
  public function isEmployedByaCertifiedPublicAccountant(UserInterface $user = NULL) {
    $membership_qualify = $this->getTextFieldValue($user, 'field_membership_qualify');
    return $membership_qualify == BillingClassCodeInterface::MEMBERSHIP_QUALIFY_EMPLOYED_BY_A_CPA;
  }

  /**
   * Check if user Membership Qualify is Non-CPA owner of CPA firm.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if user Membership Qualify is Non-CPA owner of CPA firm,
   *   otherwise FALSE.
   */
  public function isNonCertifiedPublicAccountantOwnerOfFirm(UserInterface $user = NULL) {
    $membership_qualify = $this->getTextFieldValue($user, 'field_membership_qualify');
    return $membership_qualify == BillingClassCodeInterface::MEMBERSHIP_QUALIFY_NON_CPA_OWNER_OF_CPA_FIRM;
  }

  /**
   * Check if the user's employment status is leave of absence.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Employment Status is Leave of Absence, otherwise FALSE.
   */
  public function isEmploymentStatusLeaveOfAbsence(UserInterface $user = NULL) {
    $job_status = $this->getTargetIdValue($user, 'field_job_status');
    return $job_status == BillingClassCodeInterface::EMPLOYMENT_STATUS_LEAVE_OF_ABSENCE;
  }

  /**
   * Check if the user's employment status is Part-time.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Employment Status is Part-time, otherwise FALSE.
   */
  public function isEmploymentStatusPartTime(UserInterface $user = NULL) {
    $job_status = $this->getTargetIdValue($user, 'field_job_status');
    return $job_status == BillingClassCodeInterface::EMPLOYMENT_STATUS_PART_TIME;
  }

  /**
   * Check if the user's employment status is Seasonal.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Employment Status is Seasonal, otherwise FALSE.
   */
  public function isEmploymentStatusSeasonal(UserInterface $user = NULL) {
    $job_status = $this->getTargetIdValue($user, 'field_job_status');
    return $job_status == BillingClassCodeInterface::EMPLOYMENT_STATUS_SEASONAL;
  }

  /**
   * Check if the user's employment status is Unemployed.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Employment Status is Unemployed, otherwise FALSE.
   */
  public function isEmploymentStatusUnemployed(UserInterface $user = NULL) {
    $job_status = $this->getTargetIdValue($user, 'field_job_status');
    return $job_status == BillingClassCodeInterface::EMPLOYMENT_STATUS_UNEMPLOYED;
  }

  /**
   * Check if the user's employment status is Retired.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Employment Status is Retired, otherwise FALSE.
   */
  public function isEmploymentStatusRetired(UserInterface $user = NULL) {
    $job_status = $this->getTargetIdValue($user, 'field_job_status');
    return $job_status == BillingClassCodeInterface::EMPLOYMENT_STATUS_RETIRED;
  }

  /**
   * Check if the user's employment status is Full-time.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Employment Status is Full-time, otherwise FALSE.
   */
  public function isEmploymentStatusFullTime(UserInterface $user = NULL) {
    $job_status = $this->getTargetIdValue($user, 'field_job_status');
    return $job_status == BillingClassCodeInterface::EMPLOYMENT_STATUS_FULL_TIME;
  }

  /**
   * Check if the user's job position is Educator.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user's job position is Educator, otherwise FALSE.
   */
  public function isEducator(UserInterface $user = NULL) {
    $job_position = $this->getTargetIdValue($user, 'field_job_position');
    return $job_position == BillingClassCodeInterface::JOB_POSITION_EDUCATOR;
  }

  /**
   * Check the user's Membership Qualify.
   *
   * Check if the user's Membership Qualify is Employed in
   * an accounting position.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Membership Qualify is Employed in an accounting position,
   *   otherwise FALSE.
   */
  public function isMembershipQualifyEmployedInAnAccountingPosition(UserInterface $user = NULL) {
    $job_status = $this->getTargetIdValue($user, 'field_membership_qualify');
    return $job_status == BillingClassCodeInterface::MEMBERSHIP_QUALIFY_EMPLOYED_IN_AN_ACCOUNTING_POSITION;
  }

  /**
   * Check if give member status code is a Member in good standing.
   *
   * @param string $memberStatusCode
   *   The member status code.
   *
   * @return bool
   *   TRUE if the give member status code is a Member in good standing,
   *   otherwise FALSE.
   */
  public function isMemberCode($memberStatusCode = NULL) {
    if (empty($memberStatusCode)) {
      return FALSE;
    }
    $members = [
      MemberStatusCodesInterface::MEMBER_IN_GOOD_STANDING,
      MemberStatusCodesInterface::MEMBER_WITH_A_DUES_BALANCE,
    ];
    return in_array($memberStatusCode, $members);
  }

  /**
   * Check if the user is an suspended member.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Current user is an suspended member otherwise FALSE.
   */
  public function isSuspendedMember(UserInterface $user = NULL) {
    $member_status = $this->getTextFieldValue($user, 'field_member_status');
    return $member_status == MemberStatusCodesInterface::SUSPENDED_MEMBER;
  }

  /**
   * Check if the user is an prospective member.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Current user is an prospective member, otherwise FALSE.
   */
  public function isProspectiveMember(UserInterface $user = NULL) {
    $member_status = $this->getTextFieldValue($user, 'field_member_status');
    return $member_status == MemberStatusCodesInterface::APPLICANT_FOR_MEMBERSHIP;
  }

  /**
   * Check if the user is an good standing member user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Current user is good standing member user, otherwise FALSE.
   */
  public function isMemberInGoodStanding(UserInterface $user = NULL) {
    $member_status = $this->getTextFieldValue($user, 'field_member_status');
    return $member_status == MemberStatusCodesInterface::MEMBER_IN_GOOD_STANDING;
  }

  /**
   * Check if the member with dues balance user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the member with dues balance user, otherwise FALSE.
   */
  public function isMemberWithDuesBalance(UserInterface $user = NULL) {
    $member_status = $this->getTextFieldValue($user, 'field_member_status');
    return $member_status == MemberStatusCodesInterface::MEMBER_WITH_A_DUES_BALANCE;
  }

  /**
   * Check if the user is an terminated member.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Current user is an terminated member, otherwise FALSE.
   */
  public function isTerminatedMember(UserInterface $user = NULL) {
    $member_status = $this->getTextFieldValue($user, 'field_member_status');
    return $member_status == MemberStatusCodesInterface::TERMINATED;
  }

  /**
   * Check if the user home address is in Virginia.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user home address is in Virginia, otherwise FALSE.
   */
  public function isHomeAddressInVirginia(UserInterface $user = NULL) {
    $address = $this->getAddressFieldValue($user, 'field_home_address');
    return (isset($address['administrative_area']) && ($address['administrative_area'] == 'VA'));
  }

  /**
   * Check if the user work address is in Virginia.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user work address is in Virginia, otherwise FALSE.
   */
  public function isWorkAddressInVirginia(UserInterface $user = NULL) {
    $address = $this->getAddressFieldValue($user, 'field_work_address');
    return (isset($address['administrative_area']) && ($address['administrative_area'] == 'VA'));
  }

  /**
   * Check if the user has Virginia Certification Number defined.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user has Virginia Certification Number, otherwise FALSE.
   */
  public function hasVirginiaCertificationNumber(UserInterface $user = NULL) {
    $cert_va_no = $this->getAddressFieldValue($user, 'field_cert_va_no');
    return !empty($cert_va_no);
  }

  /**
   * Check if the user has billing class defined.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user has billing class defined, otherwise FALSE.
   */
  public function hasBillingClassDefined(UserInterface $user = NULL) {
    $billing_class = $this->getTextFieldValue($user, 'field_amnet_billing_class');
    return $billing_class != FALSE;
  }

  /**
   * Get the Billing Class Code relate to the user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return string|bool
   *   TRUE The billing class code, otherwise FALSE.
   */
  public function getBillingClassCode(UserInterface $user = NULL) {
    return $this->getTextFieldValue($user, 'field_amnet_billing_class');
  }

  /**
   * Get the Dues Paid Through Date relate to the user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return string|bool
   *   TRUE The billing class code, otherwise FALSE.
   */
  public function getDuesPaidThroughDate(UserInterface $user = NULL) {
    return $this->getTextFieldValue($user, 'field_amnet_dues_paid_through');
  }

  /**
   * Check if the user has dues value defined.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user has dues value defined, otherwise FALSE.
   */
  public function hasDuesValueDefined(UserInterface $user = NULL) {
    $dues_lookup = $this->getTextFieldValue($user, 'field_amnet_dues_lookup');
    return $dues_lookup != FALSE;
  }

  /**
   * Get the three Member Type Codes.
   *
   * @return array
   *   The array List of the Member Type Codes.
   */
  public function getMemberTypeCodes() {
    return [
      BillingClassCodeInterface::MEMBERSHIP_SELECTION_LICENSED_CPA,
      BillingClassCodeInterface::MEMBERSHIP_SELECTION_UNLICENSED_PROFESSIONAL,
      BillingClassCodeInterface::MEMBERSHIP_SELECTION_COLLEGE_STUDENT,
    ];
  }

  /**
   * Get the three Member Type Codes.
   *
   * @param string $member_type_code
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the Member Type Code is valid, otherwise FALSE.
   */
  public function isValidMemberTypeCode($member_type_code = NULL) {
    $is_valid = FALSE;
    if (!empty($member_type_code)) {
      $is_valid = in_array($member_type_code, $this->getMemberTypeCodes());
    }
    return $is_valid;
  }

  /**
   * Get Firm Address.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The term entity.
   *
   * @return array
   *   The Firm Address Value.
   */
  public function getFirmAddress(TermInterface $firm = NULL) {
    $field_name = 'field_address';
    $value = [];
    if (!is_null($firm) && !empty($field_name)) {
      $field = $firm->get($field_name);
      if ($field) {
        $value = $field->getValue();
        $value = !empty($value) ? current($value) : [];
      }
    }
    return $value;
  }

}
