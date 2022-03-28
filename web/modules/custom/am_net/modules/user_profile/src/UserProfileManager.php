<?php

namespace Drupal\am_net_user_profile;

use Drupal\am_net_membership\BillingClass\BillingClassCodeTrait;
use Drupal\am_net_membership\BillingClass\BillingClassCodeInterface;
use Drupal\am_net_membership\MemberStatusCodesInterface;
use Drupal\am_net_user_profile\Entity\PersonInterface;
use Drupal\am_net_firms\FirmManager as FirmsHelper;
use Drupal\am_net_user_profile\Entity\Person;
use Drupal\am_net_membership\UserSyncTrait;
use Drupal\am_net\PhoneNumberHelper;
use Drupal\licensing\Entity\License;
use Drupal\vscpa_sso\Entity\Email;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;

/**
 * Default implementation of the User Profile Manager.
 */
class UserProfileManager {

  use BillingClassCodeTrait, UserSyncTrait;

  /**
   * The phone Number Helper instance.
   *
   * @var \Drupal\am_net\PhoneNumberHelper
   */
  protected $phoneNumberHelper = NULL;

  /**
   * Send Update Confirmation Email flag.
   *
   * @var array
   */
  protected $sendUpdateConfirmationEmail = [];

  /**
   * Set the flag: Send Update Confirmation Email.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param bool $send
   *   The flag value.
   */
  public function setSendUpdateConfirmationEmail(UserInterface $user = NULL, $send = FALSE) {
    if (!$user) {
      return;
    }
    $id = $user->id();
    $this->sendUpdateConfirmationEmail[$id] = $send;
  }

  /**
   * Check the flag: Send Update Confirmation Email.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   */
  public function sendUpdateConfirmationEmail(UserInterface $user = NULL) {
    if (!am_net_profile_update_email_is_active()) {
      return FALSE;
    }
    if (!$user) {
      return FALSE;
    }
    $id = $user->id();
    $flag_value = isset($this->sendUpdateConfirmationEmail[$id]) ? $this->sendUpdateConfirmationEmail[$id] : FALSE;
    return ($flag_value == TRUE);
  }

  /**
   * Get the phone Number Helper Instance.
   *
   * @return \Drupal\am_net\PhoneNumberHelper
   *   The phone Number Helper instance.
   */
  public function getPhoneNumberHelper() {
    if (is_null($this->phoneNumberHelper)) {
      $this->phoneNumberHelper = new PhoneNumberHelper();
    }
    return $this->phoneNumberHelper;
  }

  /**
   * Format & clean User Fields.
   *
   * Prepare the user fields for the sync.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity passed by reference.
   */
  public function formatUserFields(UserInterface &$user = NULL) {
    // Due that AM.net throw a exception if the phone number do not follow
    // the format: XXX-XXX-XXXX then is necessary ensure tha the Phones fields
    // meet that XXX-XXX-XXXX.
    $phones_fields = [
      'field_home_phone' => 'Home Phone',
      'field_work_phone' => 'Work Phone',
      'field_mobile_phone' => 'Mobile Phone',
      'field_fax' => 'Fax',
    ];
    foreach ($phones_fields as $field_name => $field_label) {
      $phone = $user->get($field_name)->getString();
      if (empty($phone)) {
        continue;
      }
      if (!preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $phone)) {
        $formatted = $this->getPhoneNumberHelper()->format($phone);
        $formatted = str_replace('+1 ', '', $formatted);
        if ($phone != $formatted) {
          $user->set($field_name, $formatted);
        }
      }
    }
  }

  /**
   * Maybe Change Cart Customer Email.
   *
   * @param \Drupal\user\UserInterface|null $user_original
   *   The original customer entity.
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   */
  public function maybeChangeCartCustomerEmail(UserInterface $user_original = NULL, UserInterface $user = NULL) {
    if (!$user || !$user_original) {
      return;
    }
    $original_user_email = $user_original->getEmail();
    $user_email = $user->getEmail();
    $user_email_changed = (strtolower(trim($original_user_email)) != strtolower(trim($user_email)));
    if (!$user_email_changed) {
      return;
    }
    // Get Current user carts.
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    /* @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $cart_provider->getCarts($user);
    if (empty($carts)) {
      return;
    }
    foreach ($carts as $delta => $cart) {
      $cart_customer_email = $cart->getEmail();
      $is_cart_email_different = (strtolower(trim($user_email)) != strtolower(trim($cart_customer_email)));
      if ($is_cart_email_different) {
        // Update Cart Contact Email.
        $cart->setEmail($user_email);
        // Save Changes.
        $cart->save();
      }
    }
  }

  /**
   * Sync a give Drupal user account with a AM.net Person Record.
   *
   * @param string $uid
   *   The User ID or a Valid Name Email.
   * @param string $changeDate
   *   Optional param, The change date.
   * @param bool $verbose
   *   Provides additional details as to what the sync is doing.
   *
   * @return bool|int|array
   *   Int Either SAVED_NEW or SAVED_UPDATED, depending on the operation
   *   performed, array if is in a drush context, otherwise FALSE.
   */
  public function pushUserProfile($uid = NULL, $changeDate = NULL, $verbose = FALSE) {
    $result = FALSE;
    $user = NULL;
    if ($uid) {
      // Check if an email was passed in place of a name ID.
      if (\Drupal::service('email.validator')->isValid($uid)) {
        $user = user_load_by_mail($uid);
      }
      else {
        $user = User::load($uid);
      }
    }
    if ($user) {
      // Update AM.net Person Record from user account.
      $result = $this->updatePersonRecordFormUser($user, $verbose);
    }
    return $result;
  }

  /**
   * Update Person Record form User.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param bool $verbose
   *   Provides additional details as to what the sync is doing.
   *
   * @return bool|array
   *   The responses from the requested operation, otherwise FALSE.
   */
  public function updatePersonRecordFormUser(UserInterface $user = NULL, $verbose = FALSE) {
    $result = FALSE;
    $inject_field_amnet_id = FALSE;
    $info = [];
    if ($user) {
      // User Email.
      $mail = $user->getEmail();
      /** @var \Drupal\am_net_user_profile\Entity\PersonInterface $person */
      // Prepare User info for AM.net.
      $person = Person::create([]);
      if ($this->userIsSynchronized($user)) {
        // Get AM.net ID.
        $am_net_id = $this->getTextFieldValue($user, 'field_amnet_id');
      }
      else {
        // Find a person record id by email.
        $am_net_id = person_load_by_mail($mail, $load_entity = FALSE);
        $inject_field_amnet_id = !empty($am_net_id);
      }
      // AM.net ID.
      if (!empty($am_net_id)) {
        $am_net_id = trim($am_net_id);
        $person->setId($am_net_id);
        $person->enforceIsNew(FALSE);
        $info[] = ['AM.net ID: ', $am_net_id];
      }
      // 1. Properties fields.
      // Update Email.
      $person->setEmail($mail);
      $info[] = ['Email: ', $mail];
      // Update First Name.
      $field_value = $this->getTextFieldValue($user, 'field_givenname');
      $person->setFirstName($field_value);
      $info[] = ['First Name: ', $field_value];
      // Update Last Name.
      $field_value = $this->getTextFieldValue($user, 'field_familyname');
      $person->setLastName($field_value);
      $info[] = ['Last Name: ', $field_value];
      // Update Middle Initial.
      $field_value = $this->getTextFieldValue($user, 'field_additionalname');
      $person->setMiddleInitial($field_value);
      $info[] = ['Middle Initial: ', $field_value];
      // Update Suffix.
      $field_value = $this->getTextFieldValue($user, 'field_name_suffix');
      $person->setSuffix($field_value);
      $info[] = ['Suffix: ', $field_value];
      // Update Gender Code.
      $field_value = $this->getTextFieldValue($user, 'field_gender');
      $field_gender = !empty($field_value) ? $this->loadGenderCodeByGenderCodeTid($field_value) : NULL;
      $person->setGenderCode($field_gender);
      $info[] = ['Gender Code: ', $field_gender];
      // Other Credentials.
      $field_value = $this->getTextFieldValue($user, 'field_name_creds');
      $person->setCredentials($field_value);
      $info[] = ['Other Credentials: ', $field_value];
      // NickName.
      $field_value = $this->getTextFieldValue($user, 'field_nickname');
      $person->setNickName($field_value);
      $info[] = ['NickName: ', $field_value];
      // Date of birth.
      $field_value = $this->getTextFieldValue($user, 'field_dob');
      $field_value = !empty($field_value) ? date('Y-m-d\TH:i:s', strtotime($field_value)) : NULL;
      $person->setBirthDate($field_value);
      $info[] = ['Date of birth: ', $field_value];
      // Set Join date.
      $field_value = $this->getTextFieldValue($user, "field_join_date");
      $field_value = !empty($field_value) ? date('Y-m-d\TH:i:s', strtotime($field_value)) : NULL;
      $person->setJoinDate($field_value);
      $info[] = ['Join Date: ', $field_value];
      // Set Join Date 2.
      $field_value = $this->getTextFieldValue($user, "field_join_date_2");
      $field_value = !empty($field_value) ? date('Y-m-d\TH:i:s', strtotime($field_value)) : NULL;
      $person->setJoinDate2($field_value);
      $info[] = ['Join Date 2: ', $field_value];
      // Job Position.
      $field_value = $this->getTextFieldValue($user, 'field_job_position');
      $field_job_position = !empty($field_value) ? $this->loadPositionCodeByPositionTid($field_value) : NULL;
      $person->setPositionCode($field_job_position);
      $info[] = ['Job Position: ', $field_job_position];
      // Job Title.
      $field_value = $this->getTextFieldValue($user, 'field_job_title');
      $person->setPositionDescription($field_value);
      $info[] = ['Job Title: ', $field_value];
      // Job Title.
      $info_items = [];
      $field_values = $this->getTextFieldValue($user, 'field_job_function');
      $field_values = is_array($field_values) ? $field_values : [$field_values];
      foreach ($field_values as $delta => $job_function_tid) {
        $job_function_code = $this->loadJobFunctionCodeByJobFunctionTid($job_function_tid);
        if ($job_function_code) {
          $info_items[] = $job_function_code;
        }
      }
      $field_job_function = !empty($info_items) ? implode(',', $info_items) : NULL;
      $person->setAreasOfExpertiseCodes($field_job_function);
      $info[] = ['Primary Job Functions: ', $field_job_function];
      // Linked Firm Code.
      $field_value = $this->getTextFieldValue($user, 'field_firm');
      $field_firm = !empty($field_value) ? $this->loadFirmCodeBydFirmTid($field_value) : '#BLANK#';
      if (!is_numeric($field_firm)) {
        $field_firm = '#BLANK#';
      }
      $person->setLinkedFirmCode($field_firm);
      $info[] = ['Linked Firm Code: ', $field_firm];
      // Home Information.
      $field_value = $this->getAddressFieldValue($user, 'field_home_address');
      if ($this->isAddressSuitableForSynch($field_value)) {
        // Home Address Line1.
        $address_line1 = !empty($field_value['address_line1']) ? $field_value['address_line1'] : NULL;
        $person->setHomeAddressLine1($address_line1);
        $info[] = ['Home Address Line1: ', $address_line1];
        // Home Address Line2.
        $address_line2 = !empty($field_value['address_line2']) ? $field_value['address_line2'] : NULL;
        $person->setHomeAddressLine2($address_line2);
        $info[] = ['Home Address Line2: ', $address_line2];
        // Home Address City.
        $locality = !empty($field_value['locality']) ? $field_value['locality'] : NULL;
        $person->setHomeAddressCity($locality);
        $info[] = ['Home Address City: ', $locality];
        // Home Address State Code - Virginia (VA).
        $default_state_code = NULL;
        $administrative_area = !empty($field_value['administrative_area']) ? $field_value['administrative_area'] : $default_state_code;
        $person->setHomeAddressStateCode($administrative_area);
        $info[] = ['Home Address State Code: ', $administrative_area];
        // Home Address Street Zip.
        // Postal Code is a require Field on AM.net.
        // SyncErrorCode: 6 | Zip value missing or blank 24517.
        $default_zip_code = NULL;
        $postal_code = !empty($field_value['postal_code']) ? $field_value['postal_code'] : $default_zip_code;
        $person->setHomeAddressStreetZip($postal_code);
        $info[] = ['Home Address Street Zip: ', $postal_code];
        // Check if a Foreign Address.
        if ($field_value['country_code'] != 'US') {
          // Send Foreign Foreign Country.
          $foreign_country_code = $field_value['country_code'];
          $person->setHomeAddressForeignCountry($foreign_country_code);
          $info[] = ['Home Address Foreign Country Code: ', $foreign_country_code];
          // Send Foreign State as ZZ.
          $foreign_state_code = 'ZZ';
          $person->setHomeAddressStateCode($foreign_state_code);
          $info[] = ['Home Address Foreign State Code: ', $foreign_state_code];
        }
      }
      // Home Phone.
      $field_value = $this->getTextFieldValue($user, 'field_home_phone');
      $field_value = !empty($field_value) ? $field_value : '#BLANK#';
      $person->setHomePhone($field_value);
      $info[] = ['Home Phone: ', $field_value];
      // Work Phone.
      $field_value = $this->getTextFieldValue($user, 'field_work_phone');
      $field_value = !empty($field_value) ? $field_value : '#BLANK#';
      $person->setDirectPhone($field_value);
      $info[] = ['Work Phone: ', $field_value];
      // Mobile Phone.
      $field_value = $this->getTextFieldValue($user, 'field_mobile_phone');
      $field_value = !empty($field_value) ? $field_value : '#BLANK#';
      $person->setMobilePhone($field_value);
      $info[] = ['Mobile Phone: ', $field_value];
      // Fax.
      $field_value = $this->getTextFieldValue($user, 'field_fax');
      $field_value = !empty($field_value) ? $field_value : '#BLANK#';
      $person->setHomeAddressFax($field_value);
      $info[] = ['Fax: ', $field_value];
      // Postal Mail Preference.
      $field_value = $this->getTextFieldValue($user, 'field_contact_pref');
      $person->setGeneralMailPreferenceCode($field_value);
      $info[] = ['Postal Mail Preference: ', $field_value];
      // NASBA Opt-In.
      $field_value = $this->getTextFieldValue($user, 'field_nasba_optin');
      if (!is_null($field_value)) {
        $field_value = !boolval($field_value);
      }
      $person->setNasbaOptOut($field_value);
      $info[] = ['NASBA Opt-In: ', $field_value];
      // NASBA CPE ID#.
      $field_value = $this->getTextFieldValue($user, 'field_nasba_id');
      $person->setNasbaId($field_value);
      $info[] = ['NASBA CPE ID#: ', $field_value];
      // Preferred Chapter.
      $field_value = $this->getTextFieldValue($user, 'field_preferred_chapter');
      $field_preferred_chapter = !empty($field_value) ? $this->loadChapterCodeByChapterTid($field_value) : NULL;
      $person->setPreferredChapterCode($field_preferred_chapter);
      $info[] = ['Preferred Chapter Code:  ', $field_preferred_chapter];
      // Certification/Professional.
      // Field Licensed In - Certification/Professional.
      $field_value = $this->getTextFieldValue($user, 'field_licensed_in');
      $certified_code = $this->loadCertifiedCodeByCertifiedTid($field_value);
      $person->setCertifiedCode($certified_code);
      $info[] = ['Field Licensed In Code: ', $certified_code];
      // Virginia Certification #.
      if ($certified_code == BillingClassCodeInterface::AM_NET_CERTIFIED_OUT_OF_STAT_ONLY) {
        $field_value = '#BLANK#';
      }
      else {
        $field_value = $this->getTextFieldValue($user, 'field_cert_va_no');
      }
      $person->setInStateCertificateNumber($field_value);
      $info[] = ['Virginia Certification #:  ', $field_value];
      // Original Date of Virginia Certification.
      $field_value = $this->getTextFieldValue($user, 'field_cert_va_date');
      $field_value = !empty($field_value) ? date('Y-m-d\TH:i:s', strtotime($field_value)) : NULL;
      $person->setInStateCertificationDate($field_value);
      $info[] = ['Original Date of Virginia Certification: ', $field_value];
      // Out-of-State Certification #.
      if ($certified_code == BillingClassCodeInterface::AM_NET_CERTIFIED_IN_STATE_ONLY) {
        $field_value = '#BLANK#';
      }
      else {
        $field_value = $this->getTextFieldValue($user, 'field_cert_other_no');
      }
      if (strlen($field_value) > 15) {
        $field_value = substr($field_value, 0, 15);
      }
      $person->setOutOfStateCertificateNumber($field_value);
      $info[] = ['Out-of-State Certification #: ', $field_value];
      // Original Date of Out-of-State Certification.
      $field_value = $this->getTextFieldValue($user, 'field_cert_other_date');
      $field_value = !empty($field_value) ? date('Y-m-d\TH:i:s', strtotime($field_value)) : NULL;
      $person->setOutOfStateCertificationDate($field_value);
      $info[] = ['Original Date of Out-of-State Certification: ', $field_value];
      // State of Original Certification (if other than Virginia).
      $field_value = $this->getTextFieldValue($user, 'field_cert_other');
      $field_value = !empty($field_value) ? $this->loadStateCodeByStateTid($field_value) : NULL;
      $field_value = !empty($field_value) ? $field_value : NULL;
      $person->setOutOfStateCertificationStateCode($field_value);
      $info[] = ['State of Original Certification: ', $field_value];
      // Special Needs.
      $info_items = [];
      $field_values = $this->getTextFieldValue($user, 'field_special_needs');
      $field_values = is_array($field_values) ? $field_values : [$field_values];
      foreach ($field_values as $delta => $special_needs_tid) {
        $special_needs_code = $this->loadSpecialNeedsCodeBySpecialNeedsTid($special_needs_tid);
        if ($special_needs_code) {
          $info_items[] = $special_needs_code;
        }
      }
      $field_special_needs = !empty($info_items) ? implode(',', $info_items) : NULL;
      $person->setSpecialNeedsCodes($field_special_needs);
      $info[] = ['Special Needs: ', $field_special_needs];
      // Fields of Interest.
      $info_items = [];
      $field_values = $this->getTextFieldValue($user, 'field_fields_of_interest');
      $field_values = is_array($field_values) ? $field_values : [$field_values];
      foreach ($field_values as $delta => $interest_tid) {
        $interest_code = $this->loadFieldsOfInterestCodeByFieldsOfInterestTid($interest_tid);
        if ($interest_code) {
          $info_items[] = $interest_code;
        }
      }
      $field_fields_of_interest = !empty($info_items) ? implode(',', $info_items) : NULL;
      $person->setFieldsOfInterestCodes($field_fields_of_interest);
      $info[] = ['Fields of Interest: ', $field_fields_of_interest];
      // Member Status.
      $member_status = $this->getTextFieldValue($user, 'field_member_status');
      // VSCPAConnect Email Preferences.
      $email_opt_codes = $this->loadConnectEmailCodes();
      $default_codes = '#BLANK#';
      $email_opt_in_codes = [];
      $field_values = $this->getTextFieldValue($user, 'field_connect_email_prefs');
      $field_values = is_array($field_values) ? $field_values : [$field_values];
      foreach ($field_values as $delta => $interest_tid) {
        $code_item = $email_opt_codes[$interest_tid] ?? NULL;
        if (empty($code_item)) {
          continue;
        }
        $email_opt_in_codes[] = $code_item;
      }
      if ($member_status == 'M') {
        // Ensure that of set opt In: "Member Discounts" and "VSCPA Chapters".
        if (!in_array('VA', $email_opt_in_codes)) {
          $email_opt_in_codes[] = 'VA';
        }
        if (!in_array('CU', $email_opt_in_codes)) {
          $email_opt_in_codes[] = 'CU';
        }
      }
      // Email Opt In Codes.
      $field_email_opt_in_codes = !empty($email_opt_in_codes) ? implode(',', $email_opt_in_codes) : $default_codes;
      $person->setEmailOptInCodes($field_email_opt_in_codes);
      $info[] = ['VSCPAConnect Email Preferences(EmailOptInCodes): ', $field_email_opt_in_codes];
      // Facebook.
      $field_value = $this->getTextFieldValue($user, 'field_facebook_url');
      $person->setFacebook($field_value);
      $info[] = ['Facebook: ', $field_value];
      // LinkedIn.
      $field_value = $this->getTextFieldValue($user, 'field_linkedin_url');
      $person->setLinkedIn($field_value);
      $info[] = ['LinkedIn: ', $field_value];
      // Twitter.
      $field_value = $this->getTextFieldValue($user, 'field_twitter_url');
      $person->setTwitter($field_value);
      $info[] = ['Twitter: ', $field_value];
      // Instagram.
      $field_value = $this->getTextFieldValue($user, 'field_instagram_url');
      $person->setInstagram($field_value);
      $info[] = ['Instagram: ', $field_value];
      // Administrative Fields.
      $field_value = $this->getTextFieldValue($user, 'field_licensed');
      $person->setLicensedCode($field_value);
      $info[] = ['Licensed Code: ', $field_value];
      // Administrative Fields.
      $field_value = $this->getTextFieldValue($user, 'field_amnet_billing_class');
      $person->setBillingClassCode($field_value);
      $info[] = ['Billing Class Code: ', $field_value];
      // 2. User Defined Fields.
      // Membership Qualify.
      $field_value = $this->getTextFieldValue($user, 'field_membership_qualify');
      $field_value = !empty($field_value) ? $field_value : NULL;
      $person->setMembershipQualify($field_value);
      $info[] = ['Membership Qualify: ', $field_value];
      // Felony Conviction.
      $field_value = $this->getTextFieldValue($user, 'field_convicted_felon');
      $field_value = !empty($field_value) ? $field_value : NULL;
      $person->setFelonyConviction($field_value);
      $info[] = ['Felony Conviction: ', $field_value];
      // Employment Status.
      $field_value = $this->getTargetIdValue($user, 'field_job_status');
      $field_job_status = !empty($field_value) ? $this->loadEmploymentStatusCodeByEmploymentStatusTid($field_value) : NULL;
      $person->setEmploymentStatus($field_job_status);
      $info[] = ['Employment Status Code: ', $field_job_status];
      // Political Party Affiliation.
      $field_value = $this->getTextFieldValue($user, 'field_party_affiliation');
      $field_value = !empty($field_value) ? $field_value : NULL;
      $person->setPoliticalPartyAffiliation($field_value);
      $info[] = ['Political Party Affiliation: ', $field_value];
      // Secondary Email.
      $field_value = $this->getTextFieldValue($user, 'field_secondary_emails');
      if (strtolower($mail) == strtolower($field_value)) {
        $field_value = NULL;
      }
      $person->setSecondaryEmail($field_value);
      $info[] = ['Secondary Email: ', $field_value];
      // Un-der-grade date.
      $field_value = $this->getTextFieldValue($user, 'field_undergrad_date');
      $field_value = !empty($field_value) ? date('Y-m-d\TH:i:s', strtotime($field_value)) : NULL;
      $person->setUndergradDegreeRecv($field_value);
      $info[] = ['Un-der-grade date: ', $field_value];
      // Grade date.
      $field_value = $this->getTextFieldValue($user, 'field_grad_date');
      $field_value = !empty($field_value) ? date('Y-m-d\TH:i:s', strtotime($field_value)) : NULL;
      $person->setGraduateDegreeRecv($field_value);
      $info[] = ['Grade date: ', $field_value];
      // 3. User Defined Lists.
      // Text Message Opt-In.
      $field_values = $this->getTextFieldValue($user, 'field_receive_sms');
      $field_values = $field_values ?? [];
      $field_values = !is_array($field_values) ? [$field_values] : $field_values;
      // Call Values.
      $call_values = $this->getTextFieldValue($user, 'field_receive_calls');
      if (empty($call_values)) {
        $message_opt_in = ['#BLANK#'];
      }
      else {
        $call_values = $call_values ?? [];
        $call_values = !is_array($call_values) ? [$call_values] : $call_values;
        if (!empty($call_values)) {
          // Merge Options.
          $message_opt_in = array_merge($field_values, $call_values);
        }
      }
      $person->setTextMessageOptIn($message_opt_in);
      $info[] = ['Text Message Opt-In: ', implode(',', $message_opt_in)];
      // Ethnic Origin.
      $info_items = [];
      $field_values = $this->getTargetIdValue($user, 'field_ethnic_origin');
      $field_values = $field_values ?? [];
      $ethnicity_codes = !is_array($field_values) ? [$field_values] : $field_values;
      foreach ($ethnicity_codes as $ethnicity_tid) {
        $ethnicity_code = $this->loadEthnicityCodeByEthnicityTid($ethnicity_tid);
        if ($ethnicity_code) {
          $info_items[] = $ethnicity_code;
        }
      }
      $person->setEthnicity($info_items);
      $info[] = ['Ethnic Origin: ', implode(', ', $info_items)];
      // Undergraduate College or University.
      $info_items = [];
      $field_undergrad_loc = $this->getTextFieldValue($user, 'field_undergrad_loc');
      $school_affiliation_code = !empty($field_undergrad_loc) ? $this->loadSchoolLocationCodeBySchoolLocationNid($field_undergrad_loc) : NULL;
      if (!empty($school_affiliation_code)) {
        $info_items[] = $school_affiliation_code;
      }
      $info[] = ['Undergraduate College or University: ', $field_undergrad_loc];
      // Graduate College or University.
      $field_graduate_loc = $this->getTextFieldValue($user, 'field_graduate_loc');
      $school_affiliation_code = !empty($field_graduate_loc) ? $this->loadSchoolLocationCodeBySchoolLocationNid($field_graduate_loc) : NULL;
      if (!empty($school_affiliation_code)) {
        $info_items[] = $school_affiliation_code;
      }
      $info[] = ['Graduate College or University: ', implode(',', $info_items)];
      $person->setSchoolAffiliation($info_items);
      // Inject membership fields.
      if ($this->isMembershipStatusInfoAvailableForPush($user)) {
        // Member Status.
        $person->setMemberStatusCode($member_status);
        $info[] = ['Member Status: ', $member_status];
        // Membership Selection.
        $field_value = $this->getTextFieldValue($user, 'field_member_select');
        $person->setMemberTypeCode($field_value);
        $info[] = ['Membership Selection: ', $field_value];
        $this->setMembershipStatusInfoAvailableForPush($user, FALSE);
      }
      // Push Role Changes on Firm Admin.
      $user_roles = $user->getRoles();
      $is_firm_admin = in_array('firm_administrator', $user_roles);
      $person->setIsFirmAdmin($is_firm_admin);
      $info[] = ['Is Firm Admin: ', $is_firm_admin];

      // Send DuesPaidThrough.
      $field_value = $this->getTextFieldValue($user, 'field_amnet_dues_paid_through');
      if (!empty($field_value)) {
        $person->setDuesPaidThrough($field_value);
        $info[] = ['Dues Paid Through: ', $field_value];
      }
      if ($this->sendUpdateConfirmationEmail($user)) {
        // Send Profile Update Confirmation Email.
        $field_value = TRUE;
        $person->setSendProfileUpdateConfirmationEmail($field_value);
        $info[] = ['Send Profile Update Confirmation Email: ', $field_value];
      }

      // Send Sections codes.
      $sections_codes = [];
      $field_value = $this->getTextFieldValue($user, 'field_disclosures_sendto');
      if (!empty($field_value)) {
        $sections_codes[] = $field_value;
      }
      $field_value = $this->getTextFieldValue($user, 'field_cpecatalog_sendto');
      if (!empty($field_value)) {
        $sections_codes[] = $field_value;
      }
      $field_value = $this->getTextFieldValue($user, 'field_receive_offers');
      if (!empty($field_value)) {
        $sections_codes[] = $field_value;
      }
      // Clean section codes.
      if (!empty($sections_codes)) {
        $sections_codes_supported_values = [
          'D1',
          'D2',
          'C1',
          'C2',
          'VP',
          'VM',
          'VO',
        ];
        $clean_sections_codes = [];
        foreach ($sections_codes as $delta => $code) {
          if (in_array($code, $sections_codes_supported_values)) {
            $clean_sections_codes[] = $code;
          }
        }
        $sections_codes = $clean_sections_codes;
      }
      $sections_codes_value = implode(',', $sections_codes);
      $person->setSectionsCodes($sections_codes_value);
      $info[] = ['Sections Codes: ', $sections_codes_value];
      $legislative_contacts_manager = \Drupal::service('am_net_user_profile.legislative_contacts_manager');
      // Set Senate Representative ID.
      $field_value = $legislative_contacts_manager->getLegislativeRelationshipId($user, 'field_pol_senator_relates');
      $person->setSenateRepresentativeId($field_value);
      // Set House Representative ID.
      $field_value = $legislative_contacts_manager->getLegislativeRelationshipId($user, 'field_pol_delegate_relates');
      $person->setHouseRepresentativeId($field_value);
      // Save Changes on the Person entity in AM.net system.
      $result = $person->save();
      if (is_numeric($result) && ($result > SAVED_UPDATED)) {
        $inject_field_amnet_id = TRUE;
        $am_net_id = $result;
        $result = SAVED_NEW;
      }
      // Set entity as synced in the current request.
      am_net_entity_set_synced('user', $user->id(), $synced = TRUE);
      // Inject field: amnet_id.
      if ($inject_field_amnet_id) {
        // Temporarily lock the sync for not push this change.
        $this->lockUserSync($user);
        $user->set('field_amnet_id', $am_net_id);
        $user->save();
        $this->unlockUserSync($user);
      }
      // Save change on the Other Legislative Contact.
      $legislative_contacts_manager->pushLegislativeContacts($user, $am_net_id, $info);
      // AM.net is the authority for updating the fields:
      // 1. MemberStatusCode.
      // 2. MemberTypeCode.
      if ($verbose) {
        $info['result'] = $result;
      }
    }
    return ($verbose) ? $info : $result;
  }

  /**
   * Sync a give AM.net Person Record with a Drupal user account.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool|int
   *   Int Either SAVED_NEW or SAVED_UPDATED, depending on the operation
   *   performed, otherwise FALSE.
   */
  public function pullUserProfileChanges(UserInterface &$user = NULL) {
    if (is_null($user)) {
      return FALSE;
    }
    // Get the AM.net ID.
    $am_net_id = $this->getTextFieldValue($user, 'field_amnet_id');
    if (empty($am_net_id)) {
      // The user is not synced yet.
      return FALSE;
    }
    $am_net_id = trim($am_net_id);
    $person = Person::load($am_net_id);
    if (($person == FALSE) || !($person instanceof PersonInterface)) {
      // None Person was found with that AM.net ID.
      return FALSE;
    }
    // Lock the user's sync so as not to push the changes that come
    // from AM.net.
    $this->lockUserSync($user);
    // Update Drupal Account.
    $result = $this->updateUserFromPersonRecord($user, $person);
    // UnLock the sync for this Drupal Account.
    $this->unlockUserSync($user);
    // Return Result.
    return $result;
  }

  /**
   * Merge two user profiles.
   *
   * @param int $deleted_id
   *   The old AM.net name id.
   * @param int $merge_into_id
   *   The New AM.net name id.
   * @param string $merged_date_time
   *   The merged date time.
   *
   * @return bool
   *   TRUE if the process was successfully completed, otherwise FALSE.
   */
  public function mergeUserProfiles($deleted_id = NULL, $merge_into_id = NULL, $merged_date_time = NULL) {
    if (empty($deleted_id) || empty($merge_into_id)) {
      return FALSE;
    }
    $deleted_id = trim($deleted_id);
    $merge_into_id = trim($merge_into_id);
    // Step #1 Get the old Account by AM.net ID.
    $old_user = $this->getUserByNameId($deleted_id);
    $old_user_gluu_uid = NULL;
    if ($old_user) {
      $old_user_gluu_uid = $old_user->get('field_sso_id')->getString();
      // Step #2 Remove the old account.
      $old_user->delete();
    }
    // Step #3 ensure that old account on Gluu is removed.
    /** @var \Drupal\vscpa_sso\GluuClient $gluuClient */
    $gluuClient = \Drupal::service('gluu.client');
    if (empty($old_user_gluu_uid)) {
      $gluu_account = $gluuClient->getExternalId($deleted_id);
      $old_user_gluu_uid = $gluu_account->id ?? NULL;
    }
    if (!empty($old_user_gluu_uid)) {
      // Remove the old Gluu Account.
      $gluuClient->deleteUser($old_user_gluu_uid);
    }
    // Step #4 Check if the new account exits.
    $new_user = $this->getUserByNameId($merge_into_id);
    if (!$new_user) {
      // Sync use locally.
      $this->syncUserProfile($merge_into_id, $merged_date_time, FALSE, FALSE, TRUE);
    }
    return TRUE;
  }

  /**
   * Sync a give AM.net Person Record with a Drupal user account.
   *
   * @param string $names_id
   *   The AMNet Name ID or a Valid Name Email.
   * @param string $changeDate
   *   Optional param, The change date.
   * @param bool $validate
   *   Check if the user should be sync.
   * @param bool $verbose
   *   Provides additional details as to what the sync is doing.
   * @param bool $gluu_validation
   *   Flag for apply Gluu account validation.
   *
   * @return bool|int|array
   *   Int Either SAVED_NEW or SAVED_UPDATED, depending on the operation
   *   performed, array if is in a drush context, otherwise FALSE.
   */
  public function syncUserProfile($names_id = NULL, $changeDate = NULL, $validate = FALSE, $verbose = FALSE, $gluu_validation = FALSE) {
    if (empty($names_id)) {
      return FALSE;
    }
    else {
      $names_id = trim($names_id);
    }
    // Check if an email was passed in place of a name ID.
    $id_is_email = \Drupal::service('email.validator')->isValid($names_id);
    if ($id_is_email) {
      $person = person_load_by_mail($names_id);
    }
    else {
      $person = Person::load($names_id);
    }
    // Validate that the person record exits.
    if (($person == FALSE) || !($person instanceof PersonInterface)) {
      return FALSE;
    }
    if ($id_is_email) {
      $names_id = $person->id();
    }
    // Email Field is mandatory on Drupal.
    $email = $person->getEmail();
    if (empty($email)) {
      return FALSE;
    }
    // Look up a Drupal user related to the AM.net ID.
    $user = $this->getUserByNameId($names_id);
    // Check if the Person record is suitable for sync in case that
    // does not exists on Drupal.
    $is_suitable = $this->isPersonSuitableForSync($person);
    $is_admin = $user && ($user->hasRole('administrator') || $user->hasRole('vscpa_administrator') || $user->hasRole('amnet_agent'));
    $is_person_suitable_for_sync = $validate ? ($is_suitable || $is_admin) : TRUE;
    if (!$is_person_suitable_for_sync) {
      return FALSE;
    }
    // Create a new Drupal user account if it does not exists on Drupal.
    // Generate a Password.
    $user_password = user_password();
    if (!$user) {
      // Create a new Account.
      $user = User::create();
      // Primary email.
      $user->setEmail($email);
      // The username field is mandatory on Drupal.
      $user->setUsername($names_id);
      // Ensure that the name is not already taken.
      $tmp_user = user_load_by_name($email);
      $name_already_taken = ($tmp_user != FALSE);
      if ($name_already_taken) {
        // Duplicate account, remove the old one.
        $tmp_user->delete();
      }
      $user->enforceIsNew();
      $user->set("init", $email);
      // Set language.
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $user->set("langcode", $language);
      $user->set("preferred_langcode", $language);
      $user->set("preferred_admin_langcode", $language);
      $user->setPassword($user_password);
    }
    // Lock the user sync for prevent pushing any changes that come from AM.net
    // on the current sync.
    $this->lockUserSyncById($names_id);
    // Update user account info from AM.net Person record info.
    $result = $this->updateUserFromPersonRecord($user, $person, $verbose);
    // UnLock the sync for this Record ID.
    $this->unlockUserSyncById($names_id);
    // Ensure that the user exists on Gluu Account.
    if ($gluu_validation) {
      $field_sso_id_value = $user->get('field_sso_id')->getString();
      /** @var \Drupal\vscpa_sso\GluuClient $gluuClient */
      $gluuClient = \Drupal::service('gluu.client');
      // Get the Gluu account tie to this email.
      $gluu_account = $gluuClient->tryGetGluuAccount($user, $email, $names_id);
      $gluu_uid = $gluu_account->id ?? NULL;
      if (!$gluu_account) {
        // The account does no exist on Gluu, add new Gluu Account.
        $data = [
          'mail' => $email,
          'pass' => $user_password,
          'username' => $names_id,
          'nickname' => $person->getFirstName(),
          'familyname' => $person->getFirstName(),
          'givenname' => $person->getLastName(),
          'external_id' => $names_id,
        ];
        $gluuClient->createUserFromPersonData($data);
      }
      else {
        // Update user on Gluu.
        $gluu_account->externalId = $names_id;
        $gluu_account->profileUrl = $names_id;
        $gluu_account->userName = $names_id;
        $gluu_account->active = TRUE;
        $email_object = new Email();
        $email_object->value = strtolower($email);
        $email_object->type = 'other';
        $email_object->primary = TRUE;
        $gluu_account->emails = [$email_object];
        $gluu_object = $gluuClient->updateUser($gluu_uid, $gluu_account);
        $gluu_uid = $gluu_object->id ?? NULL;
      }
      if (empty($field_sso_id_value) && !empty($gluu_uid)) {
        $user->set('field_sso_id', $gluu_uid);
        // Update user account info from AM.net Person record info.
        $this->lockUserSyncById($names_id);
        $user->save();
        $this->unlockUserSyncById($names_id);
      }
    }
    // Return Result.
    return $result;
  }

  /**
   * Update a Drupal user account give AM.net Person Record.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   * @param \Drupal\am_net_user_profile\Entity\PersonInterface $person
   *   Optional AMNet person entity.
   * @param bool $verbose
   *   Provides additional details as to what the sync is doing.
   *
   * @return bool|int|array
   *   Int Either SAVED_NEW or SAVED_UPDATED, depending on the operation
   *   performed, array if is in a drush context, otherwise FALSE.
   */
  public function updateUserFromPersonRecord(UserInterface &$user = NULL, PersonInterface $person = NULL, $verbose = FALSE) {
    $result = FALSE;
    $info = [];
    // Ensure that at least one param is passed.
    $person = (is_null($person) && !is_null($user)) ? person_load_by_mail($user->getEmail()) : $person;
    $user = (is_null($user) && !is_null($person)) ? user_load_by_mail($person->getEmail()) : $user;
    if ($user && $person) {
      // Update Drupal Account.
      $field_amnet_id = $person->id();
      $field_amnet_id = trim($field_amnet_id);
      $user->set('field_amnet_id', $field_amnet_id);
      $info[] = ['AM.net ID: ', $field_amnet_id];
      // Update First Name.
      $field_givenname = $person->getFirstName();
      $user->set('field_givenname', $field_givenname);
      $info[] = ['First Name: ', $field_givenname];
      // Update Last Name.
      $field_familyname = $person->getLastName();
      $user->set('field_familyname', $field_familyname);
      $info[] = ['Last Name: ', $field_familyname];
      // Update Middle Initial.
      $field_additionalname = $person->getMiddleInitial();
      $user->set('field_additionalname', $field_additionalname);
      $info[] = ['Middle Initial: ', $field_additionalname];
      // Update Suffix.
      $field_name_suffix = $person->getSuffix();
      $user->set('field_name_suffix', $field_name_suffix);
      $info[] = ['Suffix: ', $field_name_suffix];
      // Update Gender Code.
      $field_gender = $person->getGender();
      $user->set('field_gender', $field_gender);
      $info[] = ['Gender Code: ', $field_gender];
      // Other Credentials.
      $field_name_creds = $person->getCredentials();
      $user->set('field_name_creds', $field_name_creds);
      $info[] = ['Other Credentials: ', $field_name_creds];
      // NickName.
      $field_nickname = $person->getNickName();
      $user->set('field_nickname', $field_nickname);
      $info[] = ['NickName: ', $field_nickname];
      // Membership Qualify.
      $membership_qualify_value = $person->getMembershipQualify();
      $user->set('field_membership_qualify', $membership_qualify_value);
      $info[] = ['Membership Qualify: ', $membership_qualify_value];
      // Felony Conviction.
      $felony_conviction_value = $person->getFelonyConviction();
      $user->set('field_convicted_felon', $felony_conviction_value);
      $info[] = ['Felony Conviction: ', $felony_conviction_value];
      // Employment Status.
      $employment_status_code = $person->getEmploymentStatus();
      $employment_status_tid = !empty($employment_status_code) ? $this->loadEmploymentStatusTidByEmploymentStatusCode($employment_status_code) : NULL;
      $user->set('field_job_status', $employment_status_tid);
      $info[] = ['Employment Status TID: ', $employment_status_tid];
      // Political Party Affiliation.
      $political_party_value = $person->getPoliticalPartyAffiliation();
      $user->set('field_party_affiliation', $political_party_value);
      $info[] = ['Political Party Affiliation: ', $political_party_value];
      // AICPA Member.
      $is_aicpa_member = $person->isAicpaMember();
      $user->set('field_is_aicpa_member', $is_aicpa_member);
      $info[] = ['Is AICPA Member: ', $is_aicpa_member];
      // Date of birth.
      $birth_date = $person->getBirthDate();
      $birth_date = !empty($birth_date) ? date('Y-m-d', strtotime($birth_date)) : $birth_date;
      $user->set('field_dob', $birth_date);
      $info[] = ['Date of birth: ', $birth_date];
      // Get Join Date.
      $join_date = $person->getJoinDate();
      if (!empty($join_date)) {
        $join_date = date('Y-m-d', strtotime($join_date));
        $user->set("field_join_date", $join_date);
        $info[] = ['Join Date: ', $join_date];
      }
      // Get Join Date 2.
      $join_date = $person->getJoinDate2();
      if (!empty($join_date)) {
        $join_date = date('Y-m-d', strtotime($join_date));
        $user->set("field_join_date_2", $join_date);
        $info[] = ['Join Date 2: ', $join_date];
      }
      // Text Message Opt-In.
      $text_message_opts = $person->getTextMessageOptIn();
      $user->field_receive_sms = $text_message_opts;
      $user->field_receive_calls = $text_message_opts;
      $text_message_opts = is_array($text_message_opts) ? implode(',', $text_message_opts) : NULL;
      $info[] = ['Text Message Opt-In: ', $text_message_opts];
      // Ethnic Origin.
      $info_items = [];
      $ethnicity_list_values = $person->getEthnicity();
      if (!empty($ethnicity_list_values)) {
        foreach ($ethnicity_list_values as $delta => $ethnicity_code) {
          $ethnicity_tid = !empty($ethnicity_code) ? $this->loadEthnicityTidByEthnicityCode($ethnicity_code) : NULL;
          if ($ethnicity_tid) {
            $info_items[] = $ethnicity_tid;
          }
        }
      }
      $user->field_ethnic_origin = $info_items;
      $info[] = ['Ethnic Origin: ', implode(', ', $info_items)];
      // Linked Firm Code.
      $linkedFirmCode = $person->getLinkedFirmCode();
      $linkedFirm = !empty($linkedFirmCode) || ($linkedFirmCode != '#####') ? FirmsHelper::loadFirmTermByFirmCode($linkedFirmCode) : NULL;
      $firm_tid = ($linkedFirm) ? $linkedFirm->id() : NULL;
      $user->set('field_firm', $firm_tid);
      $info[] = ['Linked Firm Code: ', $firm_tid];
      // Work address.
      if ($linkedFirm) {
        $address = $this->getFirmAddress($linkedFirm);
        if (!empty($address)) {
          $user->set('field_work_address', $address);
          $info[] = ['Work address: ', $address];
        }
      }
      // Job Position.
      $positionCode = $person->getPositionCode();
      $position_tid = !empty($positionCode) ? $this->loadPositionTidByPositionCode($positionCode) : NULL;
      $user->set('field_job_position', $position_tid);
      $info[] = ['Job Position: ', $position_tid];
      // Job Title.
      $positionDescription = $person->getPositionDescription();
      $user->set('field_job_title', $positionDescription);
      $info[] = ['Job Title: ', $positionDescription];
      // Primary Job Functions.
      $info_items = [];
      $job_function_items = $this->getFieldCodes($person->getAreasOfExpertiseCodes());
      if (!empty($job_function_items)) {
        foreach ($job_function_items as $delta => $code) {
          $job_function_tid = $this->loadJobFunctionTidByJobFunctionCode($code);
          if ($job_function_tid) {
            $info_items[] = $job_function_tid;
          }
        }
      }
      $user->field_job_function = $info_items;
      $info[] = ['Primary Job Functions: ', implode(', ', $info_items)];
      // Member Status.
      $memberStatusCode = $person->getMemberStatusCode();
      if (!empty($memberStatusCode)) {
        $user->set('field_member_status', $memberStatusCode);
        $info[] = ['Member Status: ', $memberStatusCode];
      }
      // Membership Selection.
      $memberTypeCode = $person->getMemberTypeCode();
      if ($this->isValidMemberTypeCode($memberTypeCode)) {
        $member_type_code = $memberTypeCode;
      }
      else {
        // Just leave the field value blank.
        $member_type_code = NULL;
      }
      if (!empty($member_type_code)) {
        // Do not override the current Membership Selection if none valid value
        // is coming from the API.
        $user->set('field_member_select', $member_type_code);
        $info[] = ['Membership Selection: ', $member_type_code];
      }
      // Secondary Email.
      $secondary_email_value = $person->getSecondaryEmail();
      $user->set('field_secondary_emails', $secondary_email_value);
      $info[] = ['Secondary Email: ', $secondary_email_value];
      // Home Information.
      $line1 = $person->getHomeAddressLine1();
      $line2 = $person->getHomeAddressLine2();
      $city = $person->getHomeAddressCity();
      $stateCode = $person->getHomeAddressStateCode();
      $pobZip = $person->getHomeAddressStreetZip();
      if (empty($pobZip)) {
        $pobZip = $person->getHomeAddressPobZip();
      }
      $field_home_address = [
        'country_code' => 'US',
        'address_line1' => $line1,
        'address_line2' => $line2,
        'locality' => $city,
        'postal_code' => $pobZip,
        'administrative_area' => $stateCode,
      ];
      $user->set('field_home_address', $field_home_address);
      $info[] = ['Home Information: ', $field_home_address];
      // Home Phone.
      $homePhone = $person->getHomePhone();
      $user->set('field_home_phone', $homePhone);
      $info[] = ['Home Phone: ', $homePhone];
      // Work Phone.
      $workPhone = $person->getDirectPhone();
      $user->set('field_work_phone', $workPhone);
      $info[] = ['Work Phone: ', $workPhone];
      // Mobile Phone.
      $mobilePhone = $person->getMobilePhone();
      $user->set('field_mobile_phone', $mobilePhone);
      $info[] = ['Mobile Phone: ', $mobilePhone];
      // Fax.
      $fax = $person->getHomeAddressFax();
      $user->set('field_fax', $fax);
      $info[] = ['Fax: ', $fax];
      // Postal Mail Preference.
      $general_mail_preference_code = $person->getGeneralMailPreferenceCode();
      $user->set('field_contact_pref', $general_mail_preference_code);
      $info[] = ['Postal Mail Preference: ', $general_mail_preference_code];
      // NASBA Opt-In.
      $nasba_optin = $person->getNasbaOptOut();
      $field_nasba_optin = !boolval($nasba_optin);
      $field_nasba_optin = (int) $field_nasba_optin;
      $user->set('field_nasba_optin', $field_nasba_optin);
      $info[] = ['NASBA Opt-In: ', $field_nasba_optin];
      // NASBA CPE ID#.
      $field_nasba_id = $person->getNasbaId();
      $user->set('field_nasba_id', $field_nasba_id);
      $info[] = ['NASBA CPE ID#: ', $field_nasba_id];
      // Preferred Chapter.
      $chapterCode = $person->getPreferredChapterCode();
      $chapter_tid = !empty($chapterCode) ? $this->loadChapterTidByChapterCode($chapterCode) : NULL;
      $user->set('field_preferred_chapter', $chapter_tid);
      $info[] = ['Preferred Chapter TID: ', $chapter_tid];
      // Field Licensed In - Certification/Professional.
      $certifiedCode = $person->getCertifiedCode();
      $field_licensed_in = $this->loadCertifiedTidByCertifiedCode($certifiedCode);

      $user->set('field_licensed_in', $field_licensed_in);
      $info[] = ['Field Licensed In: ', $field_licensed_in];
      // Virginia Certification #.
      $inStateCertificateNumber = $person->getInStateCertificateNumber();
      $inStateCertificationDate = $person->getInStateCertificationDate();
      // Format date to Y-m-d.
      $inStateCertificationDate = !empty($inStateCertificationDate) ? $this->formatDate($inStateCertificationDate) : $inStateCertificationDate;
      $outOfStateCertificateNumber = $person->getOutOfStateCertificateNumber();
      // Format date to Y-m-d.
      $outOfStateCertificationDate = $person->getOutOfStateCertificationDate();
      $outOfStateCertificationDate = !empty($outOfStateCertificationDate) ? $this->formatDate($outOfStateCertificationDate) : $outOfStateCertificationDate;
      $outOfStateCertificationStateCode = $person->getOutOfStateCertificationStateCode();
      $user->set('field_cert_va_no', $inStateCertificateNumber);
      $info[] = ['Virginia Certification #: ', $inStateCertificateNumber];
      // Original Date of Virginia Certification.
      $user->set('field_cert_va_date', $inStateCertificationDate);
      $info[] = ['Original Date of Virginia Certification: ', $inStateCertificationDate];
      // Out-of-State Certification #.
      $user->set('field_cert_other_no', $outOfStateCertificateNumber);
      $info[] = ['Out-of-State Certification #: ', $outOfStateCertificateNumber];
      // Original Date of Out-of-State Certification.
      $user->set('field_cert_other_date', $outOfStateCertificationDate);
      $info[] = ['Original Date of Out-of-State Certification: ', $outOfStateCertificationDate];
      // State of Original Certification (if other than Virginia).
      $state_tid = !empty($outOfStateCertificationStateCode) ? $this->loadStateTidByStateCode($outOfStateCertificationStateCode) : NULL;
      $user->set('field_cert_other', $state_tid);
      $info[] = ['State of Original Certification: ', $state_tid];
      // Special Needs.
      $info_items = [];
      $special_needs_items = $this->getFieldCodes($person->getSpecialNeedsCodes());
      if (!empty($special_needs_items)) {
        foreach ($special_needs_items as $delta => $code) {
          $special_needs_tid = $this->loadSpecialNeedsTidBySpecialNeedsCode($code);
          if ($special_needs_tid) {
            $info_items[] = $special_needs_tid;
          }
        }
      }
      // Can work with multi value fields like an array.
      $user->field_special_needs = $info_items;
      $info[] = ['Special Needs: ', implode(', ', $info_items)];
      // Fields of Interest.
      $info_items = [];
      $fields_of_interest_items = $this->getFieldCodes($person->getFieldsOfInterestCodes());
      if (!empty($fields_of_interest_items)) {
        foreach ($fields_of_interest_items as $delta => $code) {
          $fields_of_interest_tid = $this->loadFieldsOfInterestTidByFieldsOfInterestCode($code);
          if ($fields_of_interest_tid) {
            $info_items[] = $fields_of_interest_tid;
          }
        }
      }
      $user->field_fields_of_interest = $info_items;
      $info[] = ['Fields of Interest: ', implode(', ', $info_items)];
      // VSCPAConnect Email Preferences.
      $info_items = [];
      $email_optin_items = $this->getFieldCodes($person->getEmailOptInCodes());
      if (!empty($email_optin_items)) {
        foreach ($email_optin_items as $delta => $code) {
          $connect_email_tid = $this->loadConnectEmailTidByConnectEmailCode($code);
          if ($connect_email_tid) {
            $info_items[] = $connect_email_tid;
          }
        }
      }
      if ($memberStatusCode == 'M') {
        // Ensure that of set opt In: "Member Discounts" and "VSCPA Chapters".
        if (!in_array('134', $info_items)) {
          $info_items[] = '134';
        }
        if (!in_array('136', $info_items)) {
          $info_items[] = '136';
        }
      }
      $user->field_connect_email_prefs = $info_items;
      $info[] = ['VSCPAConnect Email Preferences: ', implode(', ', $info_items)];
      // Social Networks Fields.
      // Facebook.
      $facebook = $person->getFacebook();
      $uri_parts = parse_url($facebook);
      $is_invalid_url = ($uri_parts === FALSE) || ((count($uri_parts) == 1) && isset($uri_parts['query']));
      if ($is_invalid_url) {
        $facebook_url = NULL;
      }
      else {
        $facebook = $this->verifyUrlScheme($facebook, 'https://');
        $facebook_url = !empty($facebook) ? ['uri' => $facebook] : NULL;
      }
      $user->set('field_facebook_url', $facebook_url);
      $info[] = ['Facebook: ', $facebook];
      // LinkedIn.
      $linkedin = $person->getLinkedIn();
      $uri_parts = parse_url($linkedin);
      $is_invalid_url = ($uri_parts === FALSE) || ((count($uri_parts) == 1) && isset($uri_parts['query']));
      if ($is_invalid_url) {
        $linkedin_url = NULL;
      }
      else {
        $linkedin = $this->verifyUrlScheme($linkedin, 'https://');
        $linkedin_url = !empty($linkedin) ? ['uri' => $linkedin] : NULL;
      }
      $user->set('field_linkedin_url', $linkedin_url);
      $info[] = ['LinkedIn: ', $linkedin];
      // Twitter.
      $field_twitter_url = $person->getTwitter();
      $user->set('field_twitter_url', $field_twitter_url);
      $info[] = ['Twitter: ', $field_twitter_url];
      // Instagram.
      $field_instagram_url = $person->getInstagram();
      $user->set('field_instagram_url', $person->getInstagram());
      $info[] = ['Instagram: ', $field_instagram_url];
      // Undergraduate College or University.
      $school_affiliations = $person->getSchoolAffiliation();
      $field_undergrad_loc = NULL;
      $field_graduate_loc = NULL;
      if (!empty($school_affiliations)) {
        foreach ($school_affiliations as $delta => $school_affiliation_code) {
          $school_affiliation_nid = !empty($school_affiliation_code) ? $this->loadSchoolLocationNidBySchoolCode($school_affiliation_code) : NULL;
          if (!empty($school_affiliation_nid)) {
            if (is_null($field_undergrad_loc)) {
              $field_undergrad_loc = $school_affiliation_nid;
            }
            elseif (is_null($field_graduate_loc)) {
              $field_graduate_loc = $school_affiliation_nid;
            }
            else {
              break;
            }
          }
        }
      }
      $user->set('field_undergrad_loc', $field_undergrad_loc);
      $info[] = ['Undergraduate College or University: ', $field_undergrad_loc];
      // Graduate College or University.
      $user->set('field_graduate_loc', $field_graduate_loc);
      $info[] = ['Graduate College or University: ', $field_graduate_loc];
      // Un-der-grade date.
      $under_grade_value = $person->getUndergradDegreeRecv();
      $under_grade_value = !empty($under_grade_value) ? date('Y-m-d', strtotime($under_grade_value)) : $under_grade_value;
      $user->set('field_undergrad_date', $under_grade_value);
      $info[] = ['Un-der-grade date: ', $under_grade_value];
      // Grade date.
      $grade_value = $person->getGraduateDegreeRecv();
      $grade_value = !empty($grade_value) ? date('Y-m-d', strtotime($grade_value)) : $grade_value;
      $user->set('field_grad_date', $grade_value);
      $info[] = ['Grade date: ', $grade_value];
      // Check the Firm Admin Role.
      $is_firm_admin = $person->isFirmAdmin();
      if (!$is_firm_admin) {
        // Remove Member Role.
        $user->removeRole(MemberStatusCodesInterface::ROLE_ID_FIRM_ADMINISTRATOR);
      }
      elseif (!$user->hasRole(MemberStatusCodesInterface::ROLE_ID_FIRM_ADMINISTRATOR)) {
        // Grant Firm Admin Role.
        $user->addRole(MemberStatusCodesInterface::ROLE_ID_FIRM_ADMINISTRATOR);
      }
      $info[] = ['Is Firm Admin: ', $is_firm_admin];
      // Add Rol.
      // User settings.
      $user->activate();
      // Handle changes on the user email.
      $current_user_email = $user->getEmail();
      $person_email = $person->getEmail();
      $user_email_changed = (strtolower(trim($current_user_email)) != strtolower(trim($person_email)));
      if ($user_email_changed) {
        // Change user Email.
        $user->setEmail($person_email);
        $info[] = ['Previous Email Address: ', $current_user_email];
        $info[] = ['New Email Address: ', $person_email];
      }
      // Ensure that the username is a email.
      $user_name = $user->getUsername();
      if ($user_name != $field_amnet_id) {
        // Set name ID as username.
        $user->setUsername($field_amnet_id);
      }
      // LicensedCode.
      $licensed_code = $person->getLicensedCode();
      $licensed_code = !empty($licensed_code) ? $licensed_code : NULL;
      $user->set('field_licensed', $licensed_code);
      $info[] = ['Licensed Code: ', $licensed_code];
      // Administrative Fields.
      // Billing Class Code.
      $billing_class_code = $person->getBillingClassCode();
      $billing_class_code = is_numeric($billing_class_code) ? $billing_class_code : NULL;
      $current_billing_class_code = $user->get('field_amnet_billing_class')->getString();
      $is_member = $user->hasRole(MemberStatusCodesInterface::ROLE_ID_MEMBER);
      if ($is_member) {
        $override_billing_class_code = TRUE;
      }
      else {
        $override_billing_class_code = empty($current_billing_class_code) || !empty($billing_class_code);
      }
      if ($override_billing_class_code) {
        $user->set('field_amnet_billing_class', $billing_class_code);
        $info[] = ['Billing Class Code: ', $billing_class_code];
      }
      // Dues Paid Through Date.
      $dues_paid_through = $person->getDuesPaidThrough();
      $dues_paid_through = !empty($dues_paid_through) ? date('Y-m-d', strtotime($dues_paid_through)) : $dues_paid_through;
      $user->set('field_amnet_dues_paid_through', $dues_paid_through);
      $info[] = ['Dues Paid Through Date: ', $dues_paid_through];

      if (empty($dues_paid_through)) {
        // Check Special cases of non-members with expired licenses.
        $member_status_description = $person->getMemberStatusDescription();
        $is_non_member = (!empty($member_status_description) && (strtolower($member_status_description) == 'nonmember'));
        if ($is_non_member) {
          // Delete user's License.
          $this->removeMembershipLicense($user);
        }
      }

      // Publications preferences.
      $sections_codes = [];
      $sections_codes_str = $person->getSectionsCodes();
      if (!empty($sections_codes_str) && !is_array($sections_codes_str)) {
        $sections_codes = explode(',', trim($sections_codes_str));
      }

      // Set Disclosures Bimonthly magazine.
      $disclosure = NULL;
      // Set CPE catalog.
      $cpe_catalog = NULL;
      // Set VSCPA partners.
      $vscpa_partners = NULL;
      if (!empty($sections_codes)) {
        if (in_array('D1', $sections_codes)) {
          $disclosure = 'D1';
        }
        elseif (in_array('D2', $sections_codes)) {
          $disclosure = 'D2';
        }
        if (in_array('C1', $sections_codes)) {
          $cpe_catalog = 'C1';
        }
        elseif (in_array('C2', $sections_codes)) {
          $cpe_catalog = 'C2';
        }
        if (in_array('VP', $sections_codes)) {
          $vscpa_partners = 'VP';
        }
        elseif (in_array('VM', $sections_codes)) {
          $vscpa_partners = 'VM';
        }
        elseif (in_array('VO', $sections_codes)) {
          $vscpa_partners = 'VO';
        }
      }
      $user->set('field_disclosures_sendto', $disclosure);
      $user->set('field_cpecatalog_sendto', $cpe_catalog);
      $user->set('field_receive_offers', $vscpa_partners);
      $info[] = ['Sections Codes: ', $sections_codes_str];

      // Pull Political relations.
      $senate_representative_id = $person->getSenateRepresentativeId();
      $house_representative_id = $person->getHouseRepresentativeId();
      \Drupal::service('am_net_user_profile.legislative_contacts_manager')->pullLegislativeContacts($user, $person->id(), $senate_representative_id, $house_representative_id, $info);
      // Save Changes on the User account.
      $result = $user->save();
      if ($verbose) {
        $info['result'] = $result;
      }
    }
    return ($verbose) ? $info : $result;
  }

  /**
   * Check if a Person is suitable for Sync.
   *
   * @param \Drupal\am_net_user_profile\Entity\PersonInterface $person
   *   Optional AMNet person entity.
   *
   * @return bool
   *   TRUE if the person record is suitable for Sync, otherwise FALSE.
   */
  public function isPersonSuitableForSync(PersonInterface $person = NULL) {
    if (is_null($person)) {
      return FALSE;
    }
    // Member Status.
    $memberStatusCode = $person->getMemberStatusCode();
    if ($this->isMemberCode($memberStatusCode)) {
      return TRUE;
    }
    // Check Populate Website flag.
    return $person->getPopulateWebsiteUserProfile();
  }

  /**
   * Fetches all AM.net Persons.
   *
   * @return array
   *   List of AM.net Persons Ids.
   */
  public function getAllUserProfiles() {
    return \Drupal::service('am_net.client')->getAllUserProfiles();
  }

  /**
   * Fetches a AM.net User Profiles object by Date.
   *
   * @param string $date
   *   The given date since.
   *
   * @return array|bool
   *   List of AM.net User Profiles IDs.
   */
  public function getAllUserProfilesByDate($date) {
    return \Drupal::service('am_net.client')->getAllUserProfilesByDate($date);
  }

  /**
   * Check if user is Synchronized with AM.net.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The customer entity.
   *
   * @return bool
   *   TRUE if the user is Synchronized with AM.net, otherwise FALSE.
   */
  public function userIsSynchronized(UserInterface &$user = NULL) {
    // AM.net ID.
    $am_net_id = $this->getTextFieldValue($user, 'field_amnet_id');
    return ($am_net_id != FALSE);
  }

  /**
   * Get Field Codes.
   *
   * @param string|null $codes
   *   The codes string.
   *
   * @return array
   *   The array list of codes.
   */
  public function getFieldCodes($codes = NULL) {
    $items = [];
    if (!empty($codes)) {
      if (strpos($codes, ',') !== FALSE) {
        $items = explode(',', $codes);
      }
      else {
        $items[] = $codes;
      }
    }
    return $items;
  }

  /**
   * Load Position Term ID By Position Code.
   *
   * @param string $position_code
   *   Required param, The Position Code ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadPositionTidByPositionCode($position_code = NULL) {
    return $this->loadTidByFieldValue('job_position', 'field_amnet_position_code', $position_code);
  }

  /**
   * Load Position Code By Position Term ID.
   *
   * @param string $tid
   *   Required param, The Position Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadPositionCodeByPositionTid($tid = NULL) {
    return $this->loadFieldValueByTid('job_position', $tid, 'field_amnet_position_code');
  }

  /**
   * Load State Term ID By State Code.
   *
   * @param string $state_code
   *   Required param, The State Code ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadStateTidByStateCode($state_code = NULL) {
    return $this->loadTidByFieldValue('us_state', 'field_state_code', $state_code);
  }

  /**
   * Load Ethnicity Term ID By Ethnicity Code.
   *
   * @param string $ethnicity_code
   *   Required param, The Ethnicity Code ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadEthnicityTidByEthnicityCode($ethnicity_code = NULL) {
    return $this->loadTidByFieldValue('ethnic_origin', 'field_amnet_ethnicity_code', $ethnicity_code);
  }

  /**
   * Load Ethnicity Code By Ethnicity Term ID.
   *
   * @param string $tid
   *   Required param, The Ethnicity Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadEthnicityCodeByEthnicityTid($tid = NULL) {
    return $this->loadFieldValueByTid('ethnic_origin', $tid, 'field_amnet_ethnicity_code');
  }

  /**
   * Load Job Firm Code By Job Firm Term ID.
   *
   * @param string $tid
   *   Required param, The Job Firm Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadFirmCodeBydFirmTid($tid = NULL) {
    return $this->loadFieldValueByTid('firm', $tid, 'field_amnet_id');
  }

  /**
   * Load Connect Email Term ID By Connect Email Code.
   *
   * @param string $code
   *   Required param, The Connect Email ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadConnectEmailTidByConnectEmailCode($code = NULL) {
    return $this->loadTidByFieldValue('connect_comm', 'field_amnet_email_codes', $code);
  }

  /**
   * Load Connect Email Code By Connect Email Term ID.
   *
   * @param string $tid
   *   Required param, The Connect Email Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadConnectEmailCodeByConnectEmailTid($tid = NULL) {
    return $this->loadFieldValueByTid('connect_comm', $tid, 'field_amnet_email_codes');
  }

  /**
   * Load Employment Status Term ID By Employment Status Code.
   *
   * @param string $code
   *   Required param, The Employment Status ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadEmploymentStatusTidByEmploymentStatusCode($code = NULL) {
    return $this->loadTidByFieldValue('job_status', 'field_amnet_job_status_code', $code);
  }

  /**
   * Load Employment Status Code By Employment Status Term ID.
   *
   * @param string $tid
   *   Required param, The Employment Status Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadEmploymentStatusCodeByEmploymentStatusTid($tid = NULL) {
    return $this->loadFieldValueByTid('job_status', $tid, 'field_amnet_job_status_code');
  }

  /**
   * Load Chapter Term ID By Chapter Code.
   *
   * @param string $chapter_code
   *   Required param, The Chapter Code ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadChapterTidByChapterCode($chapter_code = NULL) {
    return $this->loadTidByFieldValue('chapter', 'field_amnet_chapte_code', $chapter_code);
  }

  /**
   * Load Chapter Code By Chapter Term ID.
   *
   * @param string $tid
   *   Required param, The Chapter Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadChapterCodeByChapterTid($tid = NULL) {
    return $this->loadFieldValueByTid('chapter', $tid, 'field_amnet_chapte_code');
  }

  /**
   * Load Special Needs Term ID By Special Needs Code.
   *
   * @param string $code
   *   Required param, The Special Needs Code ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadSpecialNeedsTidBySpecialNeedsCode($code = NULL) {
    return $this->loadTidByFieldValue('special_needs', 'field_amnet_special_needs_code', $code);
  }

  /**
   * Load Special Needs Code By Special Needs Term ID.
   *
   * @param string $tid
   *   Required param, The Special Needs Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadSpecialNeedsCodeBySpecialNeedsTid($tid = NULL) {
    return $this->loadFieldValueByTid('special_needs', $tid, 'field_amnet_special_needs_code');
  }

  /**
   * Load Fields Of Interest Term ID By Fields Of Interest Code.
   *
   * @param string $code
   *   Required param, The Fields Of Interest Code ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadFieldsOfInterestTidByFieldsOfInterestCode($code = NULL) {
    return $this->loadTidByFieldValue('interest', 'field_amnet_interest_code', $code);
  }

  /**
   * Load Fields Of Interest Code By Fields Of Interest Term ID.
   *
   * @param string $tid
   *   Required param, The Fields Of Interest Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadFieldsOfInterestCodeByFieldsOfInterestTid($tid = NULL) {
    return $this->loadFieldValueByTid('interest', $tid, 'field_amnet_interest_code');
  }

  /**
   * Load Job Function Term ID By Job Function Code.
   *
   * @param string $code
   *   Required param, The Job Function Code ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadJobFunctionTidByJobFunctionCode($code = NULL) {
    return $this->loadTidByFieldValue('job_func', 'field_amnet_job_function_code', $code);
  }

  /**
   * Load Job Function Code By Job Function Term ID.
   *
   * @param string $tid
   *   Required param, The Job Function Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadJobFunctionCodeByJobFunctionTid($tid = NULL) {
    return $this->loadFieldValueByTid('job_func', $tid, 'field_amnet_job_function_code');
  }

  /**
   * Load State Code By State Term ID.
   *
   * @param string $tid
   *   Required param, The State Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadStateCodeByStateTid($tid = NULL) {
    return $this->loadFieldValueByTid('us_state', $tid, 'field_state_code');
  }

  /**
   * Load School Location Node ID By School Code.
   *
   * @param string $code
   *   Required param, The School Code ID.
   *
   * @return null|int
   *   Node ID when the operation was successfully completed, otherwise NULL
   */
  public function loadSchoolLocationNidBySchoolCode($code = NULL) {
    $fields = [
      'field_amnet_id.value' => $code,
      'field_loc_type.target_id' => AM_NET_USER_PROFILE_LOCATION_TYPE_EDUCATIONAL_FACILITY_TID,
    ];
    return $this->loadNidByFieldValues('location', $fields);
  }

  /**
   * Load School Location Code By School Location Node ID.
   *
   * @param string $nid
   *   Required param, The School Location Node ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadSchoolLocationCodeBySchoolLocationNid($nid = NULL) {
    return $this->loadFieldValueByNid('location', $nid, 'field_amnet_id');
  }

  /**
   * Load Drupal Certified Tid by AM.net Certified Code.
   *
   * @param string $code
   *   Required param, The AM.net Certified code.
   *
   * @return null|int
   *   The Drupal Certified Tid, otherwise NULL.
   */
  public function loadCertifiedTidByCertifiedCode($code = NULL) {
    if (is_null($code)) {
      return NULL;
    }
    $tid = NULL;
    switch ($code) {
      case BillingClassCodeInterface::AM_NET_CERTIFIED_IN_AND_OUT_OF_STATE:
        $tid = BillingClassCodeInterface::LICENSED_IN_AND_OUT_OF_STATE;
        break;

      case BillingClassCodeInterface::AM_NET_CERTIFIED_IN_STATE_ONLY:
        $tid = BillingClassCodeInterface::LICENSED_IN_STATE_ONLY;
        break;

      case BillingClassCodeInterface::AM_NET_CERTIFIED_OUT_OF_STAT_ONLY:
        $tid = BillingClassCodeInterface::LICENSED_IN_OUT_OF_STAT_ONLY;
        break;

    }
    return $tid;
  }

  /**
   * Load AM.net Certified Code by Drupal Certified Tid.
   *
   * @param string $tid
   *   Required param, The Drupal Certified Tid.
   *
   * @return null|string
   *   The AM.net Certified code, otherwise empty string.
   */
  public function loadCertifiedCodeByCertifiedTid($tid = NULL) {
    if (is_null($tid)) {
      return '';
    }
    $code = '';
    switch ($tid) {
      case BillingClassCodeInterface::LICENSED_IN_AND_OUT_OF_STATE:
        $code = BillingClassCodeInterface::AM_NET_CERTIFIED_IN_AND_OUT_OF_STATE;
        break;

      case BillingClassCodeInterface::LICENSED_IN_STATE_ONLY:
        $code = BillingClassCodeInterface::AM_NET_CERTIFIED_IN_STATE_ONLY;
        break;

      case BillingClassCodeInterface::LICENSED_IN_OUT_OF_STAT_ONLY:
        $code = BillingClassCodeInterface::AM_NET_CERTIFIED_OUT_OF_STAT_ONLY;
        break;

    }
    return $code;
  }

  /**
   * Format a given date string into a a given format.
   *
   * @param string $date_string
   *   Optional param, The date string.
   * @param string $format
   *   Optional param, The date format.
   *
   * @return string
   *   The date formatted.
   */
  public function formatDate($date_string = '', $format = 'Y-m-d') {
    if (empty($date_string)) {
      return date($format);
    }
    return date($format, strtotime($date_string));
  }

  /**
   * Load AM.net Gender code by Gender Term ID.
   *
   * @param string $tid
   *   Required param, The AM.net Gender code.
   *
   * @return null|int
   *   The AM.net Gender code, otherwise NULL.
   */
  public function loadGenderCodeByGenderCodeTid($tid = NULL) {
    $code = NULL;
    if (!is_null($tid)) {
      switch ($tid) {
        case Person::GENDER_FEMALE_TID:
          $code = Person::GENDER_FEMALE_CODE;
          break;

        case Person::GENDER_MALE_TID:
          $code = Person::GENDER_MALE_CODE;
          break;

        case Person::GENDER_UNSPECIFIED_TID:
          $code = Person::GENDER_UNSPECIFIED_CODE;
          break;

      }
    }
    return $code;
  }

  /**
   * Load Connect Email Code.
   *
   * @return array
   *   The list of Connect Emails codes indexed by Term ID
   */
  public function loadConnectEmailCodes() {
    $query = \Drupal::database()->select('taxonomy_term__field_amnet_email_codes', 'e');
    $query->fields('e', ['entity_id', 'field_amnet_email_codes_value']);
    $query->condition('bundle', 'connect_comm');
    $items = $query->execute()->fetchAll();
    if (empty($items)) {
      return [];
    }
    $codes = [];
    foreach ($items as $item) {
      $key = $item->entity_id ?? NULL;
      $value = $item->field_amnet_email_codes_value ?? NULL;
      if (empty($key) || empty($value)) {
        continue;
      }
      $codes[$key] = $value;
    }
    return $codes;
  }

  /**
   * Load Term ID By Field Value.
   *
   * @param string $vid
   *   Required param, The vocabulary ID.
   * @param string $field_name
   *   Required param, The field name.
   * @param string $field_value
   *   Required param, The field value.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadTidByFieldValue($vid = NULL, $field_name = NULL, $field_value = NULL) {
    $tid = NULL;
    if (!is_null($vid) && !is_null($field_name) && !is_null($field_value)) {
      $query = \Drupal::entityQuery('taxonomy_term');
      $query->condition('vid', $vid);
      $query->condition($field_name, $field_value);
      $terms = $query->execute();
      $tid = !empty($terms) ? current($terms) : NULL;
    }
    return $tid;
  }

  /**
   * Load Field Value By Term ID.
   *
   * @param string $vid
   *   Required param, The vocabulary ID.
   * @param string $tid
   *   Required param, The Term ID.
   * @param string $field_name
   *   Required param, The field name.
   *
   * @return null|string
   *   The Field Value when the operation was successfully completed,
   *   otherwise NULL
   */
  public function loadFieldValueByTid($vid = NULL, $tid = NULL, $field_name = NULL) {
    $field_value = NULL;
    if (!is_null($vid) && !is_null($tid) && !is_null($field_name)) {
      $query = \Drupal::database()->select('taxonomy_term__' . $field_name, 't');
      $query->fields('t', [$field_name . '_value']);
      $query->condition('entity_id', $tid);
      $query->condition('bundle', $vid);
      $query->range(0, 1);
      $field_value = $query->execute()->fetchField();
    }
    return $field_value;
  }

  /**
   * Load Field Value By Node ID.
   *
   * @param string $bundle
   *   Required param, The bundle ID.
   * @param string $tid
   *   Required param, The Node ID.
   * @param string $field_name
   *   Required param, The field name.
   *
   * @return bool|string
   *   The Field Value when the operation was successfully completed,
   *   otherwise FALSE
   */
  public function loadFieldValueByNid($bundle = NULL, $tid = NULL, $field_name = NULL) {
    $field_value = NULL;
    if (!is_null($bundle) && !is_null($tid) && !is_null($field_name)) {
      $query = \Drupal::database()->select('node__' . $field_name, 'n');
      $query->fields('n', [$field_name . '_value']);
      $query->condition('entity_id', $tid);
      $query->condition('bundle', $bundle);
      $query->range(0, 1);
      $field_value = $query->execute()->fetchField();
    }
    return $field_value;
  }

  /**
   * Load Node ID By Field Value.
   *
   * @param string $type
   *   Required param, The Node Type.
   * @param array $fields
   *   Required param, The array of field values.
   *
   * @return null|int
   *   Node ID when the operation was successfully completed, otherwise NULL
   */
  public function loadNidByFieldValues($type = NULL, array $fields = []) {
    $nid = NULL;
    if (!is_null($type) && !empty($fields)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', $type);
      foreach ($fields as $field_name => $field_value) {
        $query->condition($field_name, $field_value);
      }
      $nodes = $query->execute();
      $nid = !empty($nodes) ? current($nodes) : NULL;
    }
    return $nid;
  }

  /**
   * Cleans up username.
   *
   * Run username sanitation, e.g.:
   *     Replace two or more spaces with a single underscore
   *     Strip illegal characters.
   *
   * @param string $name
   *   The username to be cleaned up.
   *
   * @return string
   *   Cleaned up username.
   */
  public function cleanupUsername($name) {

    // Strip leading and trailing spaces.
    $name = trim($name);

    // Convert any other series of spaces to a single underscore.
    $name = preg_replace('/\s+/', '_', $name);

    // Converting all to lowercase.
    $name = strtolower($name);

    // Truncate to a reasonable size.
    $name = (mb_strlen($name) > (UserInterface::USERNAME_MAX_LENGTH - 10)) ? mb_substr($name, 0, UserInterface::USERNAME_MAX_LENGTH - 11) : $name;
    return $name;
  }

  /**
   * Verify Url Scheme, Run url sanitized.
   *
   * @param string $uri
   *   The uri to be sanitation.
   * @param string $scheme
   *   The scheme of the url.
   *
   * @return string|null
   *   Sanitized url, otherwise NULL.
   */
  public function verifyUrlScheme($uri = NULL, $scheme = 'http://') {
    if (empty($uri)) {
      return NULL;
    }
    $uri = strtolower($uri);
    // Remove any empty espace.
    $uri = trim($uri);
    // Normalize cases like: //google.com.
    $uri = ltrim($uri, '/');
    // Parse url and add scheme if apply.
    $url = parse_url($uri, PHP_URL_SCHEME) === NULL ? $scheme . $uri : $uri;
    // Return result.
    return $url;
  }

  /**
   * Check if user already exist.
   *
   * @param mixed $user
   *   The user object.
   * @param string $target_uid
   *   The target UID user to compare.
   *
   * @return bool
   *   TRUE if the user already exist, otherwise false.
   */
  public function checkUserAlreadyExist($user = NULL, $target_uid = NULL) {
    if (!$user || !($user instanceof UserInterface)) {
      return FALSE;
    }
    if (!empty($target_uid)) {
      return ($user->id() != $target_uid);
    }
    return TRUE;
  }

  /**
   * Get All the Drupal Names IDs.
   *
   * @return array
   *   The list of Names IDs
   */
  public function getAllDrupalNamesIds() {
    $connection = \Drupal::database();
    $query = $connection->select('user__field_amnet_id', 'field_amnet');
    $query->fields('field_amnet', ['field_amnet_id_value']);
    $result = $query->execute();
    $items = $result->fetchAllKeyed(0, 0);
    if (empty($items)) {
      return [];
    }
    $names = [];
    foreach ($items as $key => $name_id) {
      $names[] = ['NamesID' => $name_id, 'ChangeDate' => NULL];
    }
    return $names;
  }

  /**
   * Get All the Drupal Content Persons - Names IDs.
   *
   * @param string $bundle
   *   The bundle name.
   *
   * @return array
   *   The list of Names IDs
   */
  public function getAllDrupalContentPersons($bundle = 'person') {
    $connection = \Drupal::database();
    $query = $connection->select('node__field_amnet_id', 'field_amnet');
    $query->condition('bundle', $bundle);
    $query->fields('field_amnet', ['field_amnet_id_value']);
    $result = $query->execute();
    $items = $result->fetchAllKeyed(0, 0);
    if (empty($items)) {
      return [];
    }
    $names = [];
    foreach ($items as $key => $name_id) {
      $names[] = ['NamesID' => $name_id, 'ChangeDate' => NULL];
    }
    return $names;
  }

  /**
   * Remove Membership License.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   */
  public function removeMembershipLicense(UserInterface $user = NULL) {
    if (!$user) {
      return NULL;
    }
    $entity = 'license';
    $type = 'membership';
    $user_id = $user->id();
    $query = \Drupal::entityQuery($entity);
    $query->condition('type', $type);
    $query->condition('user_id', $user_id);
    $ids = $query->execute();
    if (empty($ids)) {
      return NULL;
    }
    $id = current($ids);
    $license = License::load($id);
    if (!$license) {
      return NULL;
    }
    $license->delete();
  }

  /**
   * Check if address is suitable to be synced with AM.net.
   *
   * @param array $address
   *   The user entity.
   *
   * @return bool
   *   TRUE if the address is suitable for sync otherwise FALSE.
   */
  public function isAddressSuitableForSynch(array $address = []) {
    if (empty($address)) {
      return FALSE;
    }
    $country_code = $address['country_code'] ?? NULL;
    if (empty($country_code)) {
      return FALSE;
    }
    if ($country_code == 'US') {
      // Check if US address is suitable for sync.
      return $this->isUsaAddressSuitableForSync($address);
    }
    else {
      // Check if Foreign address is suitable for sync.
      return $this->isForeignAddressSuitableForSync($address);
    }
  }

  /**
   * Check if is a given Foreign address is suitable to be synced with AM.net.
   *
   * @param array $address
   *   The user entity.
   *
   * @return bool
   *   TRUE if the address is suitable for sync otherwise FALSE.
   */
  public function isForeignAddressSuitableForSync(array $address = []) {
    // Home Address City.
    $locality = !empty($address['locality']) ? $address['locality'] : NULL;
    if (empty($locality)) {
      return FALSE;
    }
    // Home Address Line1.
    $address_line1 = !empty($address['address_line1']) ? $address['address_line1'] : NULL;
    // Home Address Line2.
    $address_line2 = !empty($address['address_line2']) ? $address['address_line2'] : NULL;
    if (empty($address_line1) && empty($address_line2)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Check if is a given USA address is suitable to be synced with AM.net.
   *
   * @param array $address
   *   The user entity.
   *
   * @return bool
   *   TRUE if the address is suitable for sync otherwise FALSE.
   */
  public function isUsaAddressSuitableForSync(array $address = []) {
    // Home Address City.
    $locality = !empty($address['locality']) ? $address['locality'] : NULL;
    if (empty($locality)) {
      return FALSE;
    }
    // Home Address State Code.
    $administrative_area = !empty($address['administrative_area']) ? $address['administrative_area'] : NULL;
    if (empty($administrative_area)) {
      return FALSE;
    }
    // Home Address Street Zip.
    $postal_code = !empty($address['postal_code']) ? $address['postal_code'] : NULL;
    if (empty($postal_code)) {
      return FALSE;
    }
    // Home Address Line1.
    $address_line1 = !empty($address['address_line1']) ? $address['address_line1'] : NULL;
    // Home Address Line2.
    $address_line2 = !empty($address['address_line2']) ? $address['address_line2'] : NULL;
    if (empty($address_line1) && empty($address_line2)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get Drupal user account by name ID.
   *
   * @param string $names_id
   *   The AMNet Name ID or a Valid Name Email..
   *
   * @return \Drupal\user\UserInterface|null
   *   The Drupal user account, otherwise false.
   */
  public function getUserByNameId($names_id = NULL) {
    if (empty($names_id)) {
      return NULL;
    }
    $names_id = trim($names_id);
    $user = NULL;
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $users = $user_storage->loadByProperties(['field_amnet_id' => $names_id]);
    if (empty($users)) {
      // Is possible that empty spaces on AM.net is causing the issue.
      // Add and space at the beginning of the name ID.
      $users = $user_storage->loadByProperties(['field_amnet_id' => " {$names_id}"]);
    }
    if (!empty($users)) {
      /* @var \Drupal\user\UserInterface $user */
      $user = reset($users);
    }
    return $user;
  }

}
