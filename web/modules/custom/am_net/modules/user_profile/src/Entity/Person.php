<?php

namespace Drupal\am_net_user_profile\Entity;

use Drupal\am_net\Entity\AMNetEntity;

/**
 * Defines object that represent AM.Net user profile or person entity.
 */
class Person extends AMNetEntity implements PersonInterface {

  /**
   * Flag that describes the Gender female TID.
   */
  const GENDER_FEMALE_TID = 4;

  /**
   * Flag that describes the Gender male TID.
   */
  const GENDER_MALE_TID = 5;

  /**
   * Flag that describes the Gender Unspecified TID.
   */
  const GENDER_UNSPECIFIED_TID = 16071;

  /**
   * Flag that describes the AM.net Gender female Code.
   */
  const GENDER_FEMALE_CODE = 'F';

  /**
   * Flag that describes the AM.net Gender male Code.
   */
  const GENDER_MALE_CODE = 'M';

  /**
   * Flag that describes the AM.net Gender Unspecified Code.
   */
  const GENDER_UNSPECIFIED_CODE = 'U';

  /**
   * User Defined Field: Membership Qualify.
   */
  const UDF_MEMBERSHIP_QUALIFY = 'ud4';

  /**
   * User Defined Field: Felony Conviction.
   */
  const UDF_FELONY_CONVICTION = 'ud5';

  /**
   * User Defined Field: Political Party.
   */
  const UDF_POLITICAL_PARTY = 'ud8';

  /**
   * User Defined Field: Secondary Email.
   */
  const UDF_SECONDARY_EMAIL = 'ud21';

  /**
   * User Defined Field: Employment Status.
   */
  const UDF_EMPLOYMENT_STATUS = 'ud30';

  /**
   * User Defined Field: Undergrad Date.
   */
  const UDF_UNDERGRAD_DATE = 'ud14';

  /**
   * User Defined Field: Grad Date.
   */
  const UDF_GRAD_DATE = 'ud23';

  /**
   * User Defined Field: Populate website user profile.
   */
  const UDF_POPULATE_WEBSITE_USER_PROFILE = 'ud33';

  /**
   * User Defined List: Ethnicity.
   */
  const UDL_ETHNICITY_FIELD = 'udflist3';

  /**
   * User Defined List Key: Ethnicity.
   */
  const UDL_ETHNICITY_LIST_KEY = 'CUMN';

  /**
   * User Defined List: Text Message Opt-In.
   */
  const UDL_TEXT_MESSAGE_OPT_IN_FIELD = 'udflist2';

  /**
   * User Defined List Key: Text Message Opt-In.
   */
  const UDL_TEXT_MESSAGE_OPT_IN_LIST_KEY = 'CUBB';

  /**
   * User Defined List: School Affiliation.
   */
  const UDL_SCHOOL_AFFILIATION_FIELD = 'udflist4';

  /**
   * User Defined List Key: School Affiliation.
   */
  const UDL_SCHOOL_AFFILIATION_LIST_KEY = 'CUCC';

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The flag send profile update confirmation email.
   *
   * @var string
   */
  protected $sendProfileUpdateConfirmationEmail;

  /**
   * The flag senate district code.
   *
   * @var string
   */
  protected $senateDistrictCode;

  /**
   * The flag house district code.
   *
   * @var string
   */
  protected $houseDistrictCode;

  /**
   * The flag senate representative ID.
   *
   * @var string
   */
  protected $senateRepresentativeID;

  /**
   * The flag house representative ID.
   *
   * @var string
   */
  protected $houseRepresentativeID;

  /**
   * The First Name.
   *
   * @var string
   */
  protected $firstName;

  /**
   * The Last Name.
   *
   * @var string
   */
  protected $lastName;

  /**
   * The Middle Initial.
   *
   * @var string
   */
  protected $middleInitial;

  /**
   * The Suffix.
   *
   * @var string
   */
  protected $suffix;

  /**
   * The Gender Code.
   *
   * @var string
   */
  protected $genderCode;

  /**
   * The Member Type Code.
   *
   * @var string
   */
  protected $memberTypeCode;

  /**
   * The Member Status Description.
   *
   * @var string
   */
  protected $memberStatusDescription;

  /**
   * The Prospective Member Source Codes.
   *
   * @var string
   */
  protected $prospectiveMemberSourceCodes;

  /**
   * The Firm Name 1.
   *
   * @var string
   */
  protected $firmName1;

  /**
   * The Firm Name 2.
   *
   * @var string
   */
  protected $firmName2;

  /**
   * The Home Address Line 1.
   *
   * @var string
   */
  protected $homeAddressLine1;

  /**
   * The Home Address Line 2.
   *
   * @var string
   */
  protected $homeAddressLine2;

  /**
   * The Home Address City.
   *
   * @var string
   */
  protected $homeAddressCity;

  /**
   * The Home Address State Code.
   *
   * @var string
   */
  protected $homeAddressStateCode;

  /**
   * The Home Address Street Zip.
   *
   * @var string
   */
  protected $homeAddressStreetZip;

  /**
   * The Home Address Pob Zip.
   *
   * @var string
   */
  protected $homeAddressPobZip;

  /**
   * The Home Address Foreign Country.
   *
   * @var string
   */
  protected $homeAddressForeignCountry;

  /**
   * The Firm Address Line 1.
   *
   * @var string
   */
  protected $firmAddressLine1;

  /**
   * The Firm Address Line 2.
   *
   * @var string
   */
  protected $firmAddressLine2;

  /**
   * The Firm Address City.
   *
   * @var string
   */
  protected $firmAddressCity;

  /**
   * The Firm Address State Code.
   *
   * @var string
   */
  protected $firmAddressStateCode;

  /**
   * The Firm Address Street Zip.
   *
   * @var string
   */
  protected $firmAddressStreetZip;

  /**
   * The Firm Address Pob Zip.
   *
   * @var string
   */
  protected $firmAddressPobZip;

  /**
   * The Firm Address Foreign Country.
   *
   * @var string
   */
  protected $firmAddressForeignCountry;

  /**
   * The Email.
   *
   * @var string
   */
  protected $email;

  /**
   * The In State Certificate Number.
   *
   * @var string
   */
  protected $inStateCertificateNumber;

  /**
   * The In State Certification Date.
   *
   * @var string
   */
  protected $inStateCertificationDate;

  /**
   * The Out Of State Certification Date.
   *
   * @var string
   */
  protected $outOfStateCertificationDate;

  /**
   * The Out Of State Certification State Code.
   *
   * @var string
   */
  protected $outOfStateCertificationStateCode;

  /**
   * The Out Of State Certificate Number.
   *
   * @var string
   */
  protected $outOfStateCertificateNumber;

  /**
   * The Linked Firm Code.
   *
   * @var string
   */
  protected $linkedFirmCode;

  /**
   * The 'Is Firm Admin' Flag.
   *
   * @var bool
   */
  protected $isFirmAdmin;

  /**
   * The 'Is Legislator' Flag.
   *
   * @var bool
   */
  protected $isLegislator;

  /**
   * The 'Is Speaker' Flag.
   *
   * @var bool
   */
  protected $isSpeaker;

  /**
   * The 'Leader Bio' Flag.
   *
   * @var string
   */
  protected $leaderBio;

  /**
   * The Secondary Linked Firm Code.
   *
   * @var string
   */
  protected $secondaryLinkedFirmCode;

  /**
   * The General Mail Preference Code.
   *
   * @var string
   */
  protected $generalMailPreferenceCode;

  /**
   * The CPE Mail Preference Code.
   *
   * @var string
   */
  protected $cpeMailPreferenceCode;

  /**
   * The Position Description.
   *
   * @var string
   */
  protected $positionDescription;

  /**
   * The Position Code.
   *
   * @var string
   */
  protected $positionCode;

  /**
   * The Home Phone.
   *
   * @var string
   */
  protected $homePhone;

  /**
   * The Direct Phone.
   *
   * @var string
   */
  protected $directPhone;

  /**
   * The Direct Fax.
   *
   * @var string
   */
  protected $directFax;

  /**
   * The Nick Name.
   *
   * @var string
   */
  protected $nickName;

  /**
   * The Credentials.
   *
   * @var string
   */
  protected $credentials;

  /**
   * The Certified Code.
   *
   * @var string
   */
  protected $certifiedCode;

  /**
   * The Title.
   *
   * @var string
   */
  protected $title;

  /**
   * The Birth Date.
   *
   * @var string
   */
  protected $birthDate;

  /**
   * The Preferred Chapter Code.
   *
   * @var string
   */
  protected $preferredChapterCode;

  /**
   * The Actual Chapter Code.
   *
   * @var string
   */
  protected $actualChapterCode;

  /**
   * The Spouse.
   *
   * @var string
   */
  protected $spouse;

  /**
   * The Mobile Phone.
   *
   * @var string
   */
  protected $mobilePhone;

  /**
   * The Confirm Registration Method Code.
   *
   * @var string
   */
  protected $confirmRegistrationMethodCode;

  /**
   * The Member Status Code.
   *
   * @var string
   */
  protected $memberStatusCode;

  /**
   * The In House Mail Exclusion Code.
   *
   * @var string
   */
  protected $inHouseMailExclusionCode;

  /**
   * The Third Party Mail Exclusion Code.
   *
   * @var string
   */
  protected $thirdPartyMailExclusionCode;

  /**
   * The Fax Exclusion Code.
   *
   * @var string
   */
  protected $faxExclusionCode;

  /**
   * The Email Exclusion Code.
   *
   * @var string
   */
  protected $emailExclusionCode;

  /**
   * The Text Message Exclusion Code.
   *
   * @var string
   */
  protected $textMessageExclusionCode;

  /**
   * The Minority Group Code.
   *
   * @var string
   */
  protected $minorityGroupCode;

  /**
   * The Member Solicitation Code.
   *
   * @var string
   */
  protected $memberSolicitationCode;

  /**
   * The City Code.
   *
   * @var string
   */
  protected $cityCode;

  /**
   * The County Code.
   *
   * @var string
   */
  protected $countyCode;

  /**
   * The Internet Exclusion Code.
   *
   * @var string
   */
  protected $internetExclusionCode;

  /**
   * The Home Address Fax.
   *
   * @var string
   */
  protected $homeAddressFax;

  /**
   * The Office Phone Extension.
   *
   * @var string
   */
  protected $officePhoneExtension;

  /**
   * The Join Date.
   *
   * @var string
   */
  protected $joinDate;

  /**
   * The Join Date 2.
   *
   * @var string
   */
  protected $joinDate2;

  /**
   * The Billing Class Code.
   *
   * @var string
   */
  protected $billingClassCode;

  /**
   * The List Codes.
   *
   * @var string
   */
  protected $listCodes;

  /**
   * The Dues Paid Through.
   *
   * @var string
   */
  protected $duesPaidThrough;

  /**
   * The Aicpa Number.
   *
   * @var string
   */
  protected $aicpaNumber;

  /**
   * The Aicpa Member.
   *
   * @var bool
   */
  protected $aicpaMember;

  /**
   * The Licensed Code.
   *
   * @var string
   */
  protected $licensedCode;

  /**
   * The Group Membership Codes.
   *
   * @var string
   */
  protected $groupMembershipCodes;

  /**
   * The Fields Of Interest Codes.
   *
   * @var string
   */
  protected $fieldsOfInterestCodes;

  /**
   * The Special Needs Codes.
   *
   * @var string
   */
  protected $specialNeedsCodes;

  /**
   * The Areas Of Expertise Codes.
   *
   * @var string
   */
  protected $areasOfExpertiseCodes;

  /**
   * The Sections Codes.
   *
   * @var string
   */
  protected $sectionsCodes;

  /**
   * The Mail Stop.
   *
   * @var string
   */
  protected $mailstop;

  /**
   * The Facebook.
   *
   * @var string
   */
  protected $facebook;

  /**
   * The Linked In.
   *
   * @var string
   */
  protected $linkedIn;

  /**
   * The Twitter.
   *
   * @var string
   */
  protected $twitter;

  /**
   * The Instagram.
   *
   * @var string
   */
  protected $instagram;

  /**
   * The Email Opt In Codes.
   *
   * @var string
   */
  protected $emailOptInCodes;

  /**
   * The Email Opt Out Codes.
   *
   * @var string
   */
  protected $emailOptOutCodes;

  /**
   * The Nasba ID.
   *
   * @var string
   */
  protected $nasbaID;

  /**
   * The Nasba Opt Out.
   *
   * @var string
   */
  protected $nasbaOptOut;

  /**
   * The Email Address Type Code.
   *
   * @var string
   */
  protected $emailAddressTypeCode;

  /**
   * The Email Address 2.
   *
   * @var string
   */
  protected $emailAddress2;

  /**
   * The Email Address 2 Type Code.
   *
   * @var string
   */
  protected $emailAddress2TypeCode;

  /**
   * The Updated By.
   *
   * @var string
   */
  protected $updatedBy;

  /**
   * The Last Update.
   *
   * @var string
   */
  protected $lastUpdate;

  /**
   * The User Defined Fields.
   *
   * @var array
   */
  protected $userDefinedFields;

  /**
   * The User Defined Lists.
   *
   * @var array
   */
  protected $userDefinedLists;

  /**
   * User Defined Field: Secondary Email.
   *
   * @var string
   */
  protected $ud21;

  /**
   * User Defined Field: Employment Status.
   *
   * @var string
   */
  protected $ud30;

  /**
   * User Defined Field: Membership Qualify.
   *
   * @var string
   */
  protected $ud4;

  /**
   * User Defined Field: Felony Conviction.
   *
   * @var string
   */
  protected $ud5;

  /**
   * User Defined Field: Political Party.
   *
   * @var string
   */
  protected $ud8;

  /**
   * User Defined Field: Undergrad Date.
   *
   * @var string
   */
  protected $ud14;

  /**
   * User Defined Field: Grad Date.
   *
   * @var string
   */
  protected $ud23;

  /**
   * User Defined List: Ethnicity.
   *
   * @var array
   */
  protected $udflist3;

  /**
   * User Defined List: School Affiliation.
   *
   * @var array
   */
  protected $udflist4 = NULL;

  /**
   * User Defined List: Text Message Opt-In.
   *
   * @var array
   */
  protected $udflist2;

  /**
   * The AM.net API endpoint for handle the CREATION of the concrete entity.
   *
   * @var string
   */
  protected static $createEntityApiEndPoint = 'NamesServer';

  /**
   * The AM.net API endpoint for UPDATES and GET operations.
   *
   * @var string
   */
  protected static $getEntityApiEndPoint = 'Person';

  /**
   * {@inheritdoc}
   */
  public static function getApiEndPoint() {
    return self::$getEntityApiEndPoint;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    return $this->firstName;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstName($firstName) {
    $this->firstName = $firstName;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSendProfileUpdateConfirmationEmail() {
    return $this->sendProfileUpdateConfirmationEmail;
  }

  /**
   * {@inheritdoc}
   */
  public function getSenateDistrictCode() {
    return $this->senateDistrictCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenateDistrictCode($senateDistrictCode) {
    $this->senateDistrictCode = $senateDistrictCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHouseDistrictCode() {
    return $this->senateDistrictCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setHouseDistrictCode($houseDistrictCode) {
    $this->houseDistrictCode = $houseDistrictCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSenateRepresentativeId() {
    return $this->senateRepresentativeID;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenateRepresentativeId($senateRepresentativeId) {
    $this->senateRepresentativeID = $senateRepresentativeId;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHouseRepresentativeId() {
    return $this->houseRepresentativeID;
  }

  /**
   * {@inheritdoc}
   */
  public function setHouseRepresentativeId($houseRepresentativeId) {
    $this->houseRepresentativeID = $houseRepresentativeId;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSendProfileUpdateConfirmationEmail($sendProfileUpdateConfirmationEmail) {
    $this->sendProfileUpdateConfirmationEmail = $sendProfileUpdateConfirmationEmail;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    return $this->lastName;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastName($lastName) {
    $this->lastName = $lastName;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMiddleInitial() {
    return $this->middleInitial;
  }

  /**
   * {@inheritdoc}
   */
  public function setMiddleInitial($middleInitial) {
    $this->middleInitial = $middleInitial;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuffix() {
    return $this->suffix;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuffix($suffix) {
    $this->suffix = $suffix;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGenderCode() {
    return $this->genderCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getGender() {
    $gender = NULL;
    switch ($this->genderCode) {
      case 'F':
        $gender = Person::GENDER_FEMALE_TID;
        break;

      case 'M':
        $gender = Person::GENDER_MALE_TID;
        break;

      case 'U':
        $gender = Person::GENDER_UNSPECIFIED_TID;
        break;
    }
    return $gender;
  }

  /**
   * {@inheritdoc}
   */
  public function setGenderCode($genderCode) {
    $this->genderCode = $genderCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberTypeCode() {
    return $this->memberTypeCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberTypeCode($memberTypeCode) {
    $this->memberTypeCode = $memberTypeCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberStatusDescription() {
    return $this->memberStatusDescription;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberStatusDescription($memberStatusDescription) {
    $this->memberStatusDescription = $memberStatusDescription;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMembershipQualify($code = NULL) {
    $this->setUserDefinedFieldValue($code, Person::UDF_MEMBERSHIP_QUALIFY, 'Assoc. Qualifications');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipQualify() {
    $user_defined_fields = $this->getUserDefinedFields($indexed = TRUE);
    $membership_qualify_field = isset($user_defined_fields[Person::UDF_MEMBERSHIP_QUALIFY]) ? $user_defined_fields[Person::UDF_MEMBERSHIP_QUALIFY] : NULL;
    return isset($membership_qualify_field['Value']) ? $membership_qualify_field['Value'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFelonyConviction($code = NULL) {
    $this->setUserDefinedFieldValue($code, Person::UDF_FELONY_CONVICTION, 'Felony Conviction');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFelonyConviction() {
    $udf = $this->getUserDefinedFields($indexed = TRUE);
    $field = isset($udf[Person::UDF_FELONY_CONVICTION]) ? $udf[Person::UDF_FELONY_CONVICTION] : NULL;
    return isset($field['Value']) ? $field['Value'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmploymentStatus($code = NULL) {
    $this->setUserDefinedFieldValue($code, Person::UDF_EMPLOYMENT_STATUS, 'Employment Status');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUndergradDegreeRecv($code = NULL) {
    $this->setUserDefinedFieldValue($code, Person::UDF_UNDERGRAD_DATE, 'Undergrad Degree Recv.');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUndergradDegreeRecv() {
    $udf = $this->getUserDefinedFields($indexed = TRUE);
    $field = isset($udf[Person::UDF_UNDERGRAD_DATE]) ? $udf[Person::UDF_UNDERGRAD_DATE] : NULL;
    return isset($field['Value']) ? $field['Value'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setPoliticalPartyAffiliation($code = NULL) {
    $this->setUserDefinedFieldValue($code, Person::UDF_POLITICAL_PARTY, 'Political Party');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPoliticalPartyAffiliation() {
    $udf = $this->getUserDefinedFields($indexed = TRUE);
    $field = isset($udf[Person::UDF_POLITICAL_PARTY]) ? $udf[Person::UDF_POLITICAL_PARTY] : NULL;
    return isset($field['Value']) ? $field['Value'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmploymentStatus() {
    $udf = $this->getUserDefinedFields($indexed = TRUE);
    $field = isset($udf[Person::UDF_EMPLOYMENT_STATUS]) ? $udf[Person::UDF_EMPLOYMENT_STATUS] : NULL;
    return isset($field['Value']) ? $field['Value'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProspectiveMemberSourceCodes() {
    return $this->prospectiveMemberSourceCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setProspectiveMemberSourceCodes($prospectiveMemberSourceCodes) {
    $this->prospectiveMemberSourceCodes = $prospectiveMemberSourceCodes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmName1() {
    return $this->firmName1;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmName1($firmName1) {
    $this->firmName1 = $firmName1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEthnicity(array $codes = []) {
    if (!empty($codes)) {
      $this->setUserDefinedListValue($codes, Person::UDL_ETHNICITY_FIELD, Person::UDL_ETHNICITY_LIST_KEY, 'Ethnicity');
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEthnicity() {
    return $this->getUserDefinedListValue(Person::UDL_ETHNICITY_FIELD);
  }

  /**
   * {@inheritdoc}
   */
  public function setTextMessageOptIn(array $codes = []) {
    if (!empty($codes)) {
      $this->setUserDefinedListValue($codes, Person::UDL_TEXT_MESSAGE_OPT_IN_FIELD, Person::UDL_TEXT_MESSAGE_OPT_IN_LIST_KEY, 'Text Message Opt-In');
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTextMessageOptIn() {
    return $this->getUserDefinedListValue(Person::UDL_TEXT_MESSAGE_OPT_IN_FIELD);
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmName2() {
    return $this->firmName2;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmName2($firmName2) {
    $this->firmName2 = $firmName2;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomeAddressLine1() {
    return $this->homeAddressLine1;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomeAddressLine1($homeAddressLine1) {
    $this->homeAddressLine1 = $homeAddressLine1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomeAddressLine2() {
    return $this->homeAddressLine2;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomeAddressLine2($homeAddressLine2) {
    $this->homeAddressLine2 = $homeAddressLine2;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomeAddressCity() {
    return $this->homeAddressCity;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomeAddressCity($homeAddressCity) {
    $this->homeAddressCity = $homeAddressCity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomeAddressStateCode() {
    return $this->homeAddressStateCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomeAddressStateCode($homeAddressStateCode) {
    $this->homeAddressStateCode = $homeAddressStateCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomeAddressStreetZip() {
    return $this->homeAddressStreetZip;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomeAddressStreetZip($homeAddressStreetZip) {
    $this->homeAddressStreetZip = $homeAddressStreetZip;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomeAddressPobZip() {
    return $this->homeAddressPobZip;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomeAddressPobZip($homeAddressPobZip) {
    $this->homeAddressPobZip = $homeAddressPobZip;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomeAddressForeignCountry() {
    return $this->homeAddressForeignCountry;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomeAddressForeignCountry($homeAddressForeignCountry) {
    $this->homeAddressForeignCountry = $homeAddressForeignCountry;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine1() {
    return $this->getHomeAddressLine1();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressLine1($homeAddressLine1) {
    $this->setHomeAddressLine1($homeAddressLine1);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine2() {
    return $this->getHomeAddressLine2();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressLine2($homeAddressLine2) {
    $this->setHomeAddressLine2($homeAddressLine2);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressCity() {
    return $this->getHomeAddressCity();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressCity($homeAddressCity) {
    $this->setHomeAddressCity($homeAddressCity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressStateCode() {
    return $this->getHomeAddressStateCode();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressStateCode($homeAddressStateCode) {
    $this->setHomeAddressStateCode($homeAddressStateCode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressStreetZip() {
    return $this->getHomeAddressStreetZip();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressStreetZip($homeAddressStreetZip) {
    $this->setHomeAddressStreetZip($homeAddressStreetZip);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressPobZip() {
    return $this->getHomeAddressPobZip();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressPobZip($homeAddressPobZip) {
    $this->setHomeAddressPobZip($homeAddressPobZip);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressForeignCountry() {
    return $this->getHomeAddressForeignCountry();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressForeignCountry($homeAddressForeignCountry) {
    $this->setHomeAddressForeignCountry($homeAddressForeignCountry);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmAddressLine1() {
    return $this->firmAddressLine1;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmAddressLine1($firmAddressLine1) {
    $this->firmAddressLine1 = $firmAddressLine1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmAddressLine2() {
    return $this->firmAddressLine2;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmAddressLine2($firmAddressLine2) {
    $this->firmAddressLine2 = $firmAddressLine2;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmAddressCity() {
    return $this->firmAddressCity;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmAddressCity($firmAddressCity) {
    $this->firmAddressCity = $firmAddressCity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmAddressStateCode() {
    return $this->firmAddressStateCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmAddressStateCode($firmAddressStateCode) {
    $this->firmAddressStateCode = $firmAddressStateCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmAddressStreetZip() {
    return $this->firmAddressStreetZip;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmAddressStreetZip($firmAddressStreetZip) {
    $this->firmAddressStreetZip = $firmAddressStreetZip;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmAddressPobZip() {
    return $this->firmAddressPobZip;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmAddressPobZip($firmAddressPobZip) {
    $this->firmAddressPobZip = $firmAddressPobZip;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmAddressForeignCountry() {
    return $this->firmAddressForeignCountry;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmAddressForeignCountry($firmAddressForeignCountry) {
    $this->firmAddressForeignCountry = $firmAddressForeignCountry;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->email = $email;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecondaryEmail($code = NULL) {
    $this->setUserDefinedFieldValue($code, Person::UDF_SECONDARY_EMAIL, 'Secondary Email');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondaryEmail() {
    $udf = $this->getUserDefinedFields($indexed = TRUE);
    $field = isset($udf[Person::UDF_SECONDARY_EMAIL]) ? $udf[Person::UDF_SECONDARY_EMAIL] : NULL;
    return isset($field['Value']) ? $field['Value'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getInStateCertificateNumber() {
    return $this->inStateCertificateNumber;
  }

  /**
   * {@inheritdoc}
   */
  public function setInStateCertificateNumber($inStateCertificateNumber) {
    $this->inStateCertificateNumber = $inStateCertificateNumber;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInStateCertificationDate() {
    return $this->inStateCertificationDate;
  }

  /**
   * {@inheritdoc}
   */
  public function setInStateCertificationDate($inStateCertificationDate) {
    $this->inStateCertificationDate = $inStateCertificationDate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutOfStateCertificationDate() {
    return $this->outOfStateCertificationDate;
  }

  /**
   * {@inheritdoc}
   */
  public function setOutOfStateCertificationDate($outOfStateCertificationDate) {
    $this->outOfStateCertificationDate = $outOfStateCertificationDate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutOfStateCertificationStateCode() {
    return $this->outOfStateCertificationStateCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setOutOfStateCertificationStateCode($outOfStateCertificationStateCode) {
    $this->outOfStateCertificationStateCode = $outOfStateCertificationStateCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutOfStateCertificateNumber() {
    return $this->outOfStateCertificateNumber;
  }

  /**
   * {@inheritdoc}
   */
  public function setOutOfStateCertificateNumber($outOfStateCertificateNumber) {
    $this->outOfStateCertificateNumber = $outOfStateCertificateNumber;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchoolAffiliation(array $codes = []) {
    $this->setUserDefinedListValue($codes, Person::UDL_SCHOOL_AFFILIATION_FIELD, Person::UDL_SCHOOL_AFFILIATION_LIST_KEY, 'School Affiliation');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchoolAffiliation() {
    return $this->getUserDefinedListValue(Person::UDL_SCHOOL_AFFILIATION_FIELD);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkedFirmCode() {
    return $this->linkedFirmCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setLinkedFirmCode($linkedFirmCode) {
    $this->linkedFirmCode = $linkedFirmCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondaryLinkedFirmCode() {
    return $this->secondaryLinkedFirmCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecondaryLinkedFirmCode($secondaryLinkedFirmCode) {
    $this->secondaryLinkedFirmCode = $secondaryLinkedFirmCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGeneralMailPreferenceCode() {
    return $this->generalMailPreferenceCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setGeneralMailPreferenceCode($generalMailPreferenceCode) {
    $this->generalMailPreferenceCode = $generalMailPreferenceCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCpeMailPreferenceCode() {
    return $this->cpeMailPreferenceCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setCpeMailPreferenceCode($cpeMailPreferenceCode) {
    $this->cpeMailPreferenceCode = $cpeMailPreferenceCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPositionDescription() {
    return $this->positionDescription;
  }

  /**
   * {@inheritdoc}
   */
  public function setPositionDescription($positionDescription) {
    $this->positionDescription = $positionDescription;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPositionCode() {
    return $this->positionCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setPositionCode($positionCode) {
    $this->positionCode = $positionCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomePhone() {
    return $this->homePhone;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomePhone($homePhone) {
    $this->homePhone = $homePhone;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectPhone() {
    return $this->directPhone;
  }

  /**
   * {@inheritdoc}
   */
  public function setDirectPhone($directPhone) {
    $this->directPhone = $directPhone;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectFax() {
    return $this->directFax;
  }

  /**
   * {@inheritdoc}
   */
  public function setDirectFax($directFax) {
    $this->directFax = $directFax;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNickName() {
    return $this->nickName;
  }

  /**
   * {@inheritdoc}
   */
  public function setNickName($nickName) {
    $this->nickName = $nickName;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCredentials() {
    return $this->credentials;
  }

  /**
   * {@inheritdoc}
   */
  public function setCredentials($credentials) {
    $this->credentials = $credentials;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCertifiedCode() {
    return $this->certifiedCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setCertifiedCode($certifiedCode) {
    $this->certifiedCode = $certifiedCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBirthDate() {
    return $this->birthDate;
  }

  /**
   * {@inheritdoc}
   */
  public function setBirthDate($birthDate) {
    $this->birthDate = $birthDate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredChapterCode() {
    return $this->preferredChapterCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreferredChapterCode($preferredChapterCode) {
    $this->preferredChapterCode = $preferredChapterCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActualChapterCode() {
    return $this->actualChapterCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setActualChapterCode($actualChapterCode) {
    $this->actualChapterCode = $actualChapterCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSpouse() {
    return $this->spouse;
  }

  /**
   * {@inheritdoc}
   */
  public function setSpouse($spouse) {
    $this->spouse = $spouse;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMobilePhone() {
    return $this->mobilePhone;
  }

  /**
   * {@inheritdoc}
   */
  public function setMobilePhone($mobilePhone) {
    $this->mobilePhone = $mobilePhone;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmRegistrationMethodCode() {
    return $this->confirmRegistrationMethodCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfirmRegistrationMethodCode($confirmRegistrationMethodCode) {
    $this->confirmRegistrationMethodCode = $confirmRegistrationMethodCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberStatusCode() {
    return $this->memberStatusCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberStatusCode($memberStatusCode) {
    $this->memberStatusCode = $memberStatusCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInHouseMailExclusionCode() {
    return $this->inHouseMailExclusionCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setInHouseMailExclusionCode($inHouseMailExclusionCode) {
    $this->inHouseMailExclusionCode = $inHouseMailExclusionCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyMailExclusionCode() {
    return $this->thirdPartyMailExclusionCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartyMailExclusionCode($thirdPartyMailExclusionCode) {
    $this->thirdPartyMailExclusionCode = $thirdPartyMailExclusionCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFaxExclusionCode() {
    return $this->faxExclusionCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setFaxExclusionCode($faxExclusionCode) {
    $this->faxExclusionCode = $faxExclusionCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailExclusionCode() {
    return $this->emailExclusionCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmailExclusionCode($emailExclusionCode) {
    $this->emailExclusionCode = $emailExclusionCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTextMessageExclusionCode() {
    return $this->textMessageExclusionCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setTextMessageExclusionCode($textMessageExclusionCode) {
    $this->textMessageExclusionCode = $textMessageExclusionCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinorityGroupCode() {
    return $this->minorityGroupCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setMinorityGroupCode($minorityGroupCode) {
    $this->minorityGroupCode = $minorityGroupCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPopulateWebsiteUserProfile($code = NULL) {
    $this->setUserDefinedFieldValue($code, Person::UDF_POPULATE_WEBSITE_USER_PROFILE, 'Populate website user profile');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPopulateWebsiteUserProfile() {
    $udf = $this->getUserDefinedFields($indexed = TRUE);
    $field = isset($udf[Person::UDF_POPULATE_WEBSITE_USER_PROFILE]) ? $udf[Person::UDF_POPULATE_WEBSITE_USER_PROFILE] : NULL;
    $value = isset($field['Value']) ? $field['Value'] : NULL;
    return ($value == 'Y');
  }

  /**
   * {@inheritdoc}
   */
  public function setGraduateDegreeRecv($code = NULL) {
    $this->setUserDefinedFieldValue($code, Person::UDF_GRAD_DATE, 'Undergrad Degree Recv.');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraduateDegreeRecv() {
    $udf = $this->getUserDefinedFields($indexed = TRUE);
    $field = isset($udf[Person::UDF_GRAD_DATE]) ? $udf[Person::UDF_GRAD_DATE] : NULL;
    return isset($field['Value']) ? $field['Value'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberSolicitationCode() {
    return $this->memberSolicitationCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberSolicitationCode($memberSolicitationCode) {
    $this->memberSolicitationCode = $memberSolicitationCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCityCode() {
    return $this->cityCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setCityCode($cityCode) {
    $this->cityCode = $cityCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountyCode() {
    return $this->countyCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountyCode($countyCode) {
    $this->countyCode = $countyCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInternetExclusionCode() {
    return $this->internetExclusionCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setInternetExclusionCode($internetExclusionCode) {
    $this->internetExclusionCode = $internetExclusionCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomeAddressFax() {
    return $this->homeAddressFax;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomeAddressFax($homeAddressFax) {
    $this->homeAddressFax = $homeAddressFax;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOfficePhoneExtension() {
    return $this->officePhoneExtension;
  }

  /**
   * {@inheritdoc}
   */
  public function setOfficePhoneExtension($officePhoneExtension) {
    $this->officePhoneExtension = $officePhoneExtension;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJoinDate() {
    return $this->joinDate;
  }

  /**
   * {@inheritdoc}
   */
  public function setJoinDate($joinDate) {
    $this->joinDate = $joinDate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJoinDate2() {
    return $this->joinDate2;
  }

  /**
   * {@inheritdoc}
   */
  public function setJoinDate2($joinDate2) {
    $this->joinDate2 = $joinDate2;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingClassCode() {
    return $this->billingClassCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingClassCode($billingClassCode) {
    $this->billingClassCode = $billingClassCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getListCodes() {
    return $this->listCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setListCodes($listCodes) {
    $this->listCodes = $listCodes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDuesPaidThrough() {
    return $this->duesPaidThrough;
  }

  /**
   * {@inheritdoc}
   */
  public function setDuesPaidThrough($duesPaidThrough) {
    $this->duesPaidThrough = $duesPaidThrough;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAicpaNumber() {
    return $this->aicpaNumber;
  }

  /**
   * {@inheritdoc}
   */
  public function setAicpaNumber($aicpaNumber) {
    $this->aicpaNumber = $aicpaNumber;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isAicpaMember() {
    return !is_null($this->aicpaMember) ? boolval($this->aicpaMember) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setAicpaMember($aicpaMember) {
    $this->aicpaMember = $aicpaMember;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLicensedCode() {
    return $this->licensedCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setLicensedCode($licensedCode) {
    $this->licensedCode = $licensedCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupMembershipCodes() {
    return $this->groupMembershipCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupMembershipCodes($groupMembershipCodes) {
    $this->groupMembershipCodes = $groupMembershipCodes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsOfInterestCodes() {
    return $this->fieldsOfInterestCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldsOfInterestCodes($fieldsOfInterestCodes) {
    $this->fieldsOfInterestCodes = $fieldsOfInterestCodes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSpecialNeedsCodes() {
    return $this->specialNeedsCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setSpecialNeedsCodes($specialNeedsCodes) {
    $this->specialNeedsCodes = $specialNeedsCodes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAreasOfExpertiseCodes() {
    return $this->areasOfExpertiseCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setAreasOfExpertiseCodes($areasOfExpertiseCodes) {
    $this->areasOfExpertiseCodes = $areasOfExpertiseCodes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionsCodes() {
    return $this->sectionsCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setSectionsCodes($sectionsCodes) {
    $this->sectionsCodes = $sectionsCodes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMailstop() {
    return $this->mailstop;
  }

  /**
   * {@inheritdoc}
   */
  public function setMailstop($mailstop) {
    $this->mailstop = $mailstop;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacebook() {
    return $this->facebook;
  }

  /**
   * {@inheritdoc}
   */
  public function setFacebook($facebook) {
    $this->facebook = $facebook;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkedIn() {
    return $this->linkedIn;
  }

  /**
   * {@inheritdoc}
   */
  public function setLinkedIn($linkedIn) {
    $this->linkedIn = $linkedIn;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTwitter() {
    return $this->twitter;
  }

  /**
   * {@inheritdoc}
   */
  public function setTwitter($twitter) {
    $this->twitter = $twitter;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstagram() {
    return $this->instagram;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstagram($instagram) {
    $this->instagram = $instagram;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailOptInCodes() {
    return $this->emailOptInCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmailOptInCodes($emailOptInCodes) {
    $this->emailOptInCodes = $emailOptInCodes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailOptOutCodes() {
    return $this->emailOptOutCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmailOptOutCodes($emailOptOutCodes) {
    $this->emailOptOutCodes = $emailOptOutCodes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNasbaId() {
    return $this->nasbaID;
  }

  /**
   * {@inheritdoc}
   */
  public function setNasbaId($nasbaID) {
    $this->nasbaID = $nasbaID;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNasbaOptOut() {
    return !is_null($this->nasbaOptOut) ? boolval($this->nasbaOptOut) : $this->nasbaOptOut;
  }

  /**
   * {@inheritdoc}
   */
  public function setNasbaOptOut($nasbaOptOut) {
    $this->nasbaOptOut = $nasbaOptOut;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsLegislator($isLegislator) {
    $this->isLegislator = $isLegislator;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsLegislator() {
    return !is_null($this->isLegislator) ? boolval($this->isLegislator) : $this->isLegislator;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsSpeaker() {
    return !is_null($this->isSpeaker) ? boolval($this->isSpeaker) : $this->isSpeaker;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsSpeaker($isSpeaker) {
    $this->isSpeaker = $isSpeaker;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLeaderBio() {
    return $this->leaderBio;
  }

  /**
   * {@inheritdoc}
   */
  public function setLeaderBio($leaderBio) {
    $this->leaderBio = $leaderBio;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsFirmAdmin($isFirmAdmin) {
    $this->isFirmAdmin = $isFirmAdmin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsFirmAdmin() {
    return !is_null($this->isFirmAdmin) ? boolval($this->isFirmAdmin) : $this->isFirmAdmin;
  }

  /**
   * {@inheritdoc}
   */
  public function isFirmAdmin() {
    return $this->getIsFirmAdmin();
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailAddressTypeCode() {
    return $this->emailAddressTypeCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmailAddressTypeCode($emailAddressTypeCode) {
    $this->emailAddressTypeCode = $emailAddressTypeCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailAddress2() {
    return $this->emailAddress2;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmailAddress2($emailAddress2) {
    $this->emailAddress2 = $emailAddress2;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailAddress2TypeCode() {
    return $this->emailAddress2TypeCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmailAddress2TypeCode($emailAddress2TypeCode) {
    $this->emailAddress2TypeCode = $emailAddress2TypeCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdatedBy() {
    return $this->updatedBy;
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdatedBy($updatedBy) {
    $this->updatedBy = $updatedBy;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastUpdate() {
    return $this->lastUpdate;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastUpdate($lastUpdate) {
    $this->lastUpdate = $lastUpdate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUd4() {
    return $this->ud4;
  }

  /**
   * {@inheritdoc}
   */
  public function setUd4($ud4) {
    $this->ud4 = $ud4;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUd5() {
    return $this->ud5;
  }

  /**
   * {@inheritdoc}
   */
  public function setUd5($ud5) {
    $this->ud5 = $ud5;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUd8() {
    return $this->ud8;
  }

  /**
   * {@inheritdoc}
   */
  public function setUd8($ud8) {
    $this->ud8 = $ud8;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUd14() {
    return $this->ud14;
  }

  /**
   * {@inheritdoc}
   */
  public function setUd14($ud14) {
    $this->ud14 = $ud14;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUd21() {
    return $this->ud21;
  }

  /**
   * {@inheritdoc}
   */
  public function setUd21($ud21) {
    $this->ud21 = $ud21;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUd23() {
    return $this->ud23;
  }

  /**
   * {@inheritdoc}
   */
  public function setUd23($ud23) {
    $this->ud23 = $ud23;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUdflist3() {
    return !empty($this->udflist3) ? implode(',', $this->udflist3) : $this->udflist3;
  }

  /**
   * {@inheritdoc}
   */
  public function setUdflist3($udflist3) {
    $this->udflist3 = $udflist3;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUdflist4() {
    return !empty($this->udflist4) ? implode(',', $this->udflist4) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setUdflist4($udflist4) {
    $this->udflist4 = $udflist4;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUdflist2() {
    return !empty($this->udflist2) ? implode(',', $this->udflist2) : $this->udflist2;
  }

  /**
   * {@inheritdoc}
   */
  public function setUdflist2($udflist2) {
    $this->udflist2 = $udflist2;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUd30() {
    return $this->ud30;
  }

  /**
   * {@inheritdoc}
   */
  public function setUd30($ud30) {
    $this->ud30 = $ud30;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserDefinedFieldValue($field_value = NULL, $field_key = NULL, $caption = NULL) {
    if (!empty($field_key)) {
      $udf = $this->getUserDefinedFields($indexed = TRUE);
      if (isset($udf[$field_key])) {
        $udf[$field_key]['Value'] = $field_value;
      }
      else {
        $udf[$field_key] = [
          'Value' => $field_value,
          'Field' => $field_key,
        ];
        if (!empty($caption)) {
          $udf[$field_key]['Caption'] = $caption;
        }
      }
      $this->setUserDefinedFields($udf);
      // Update UDF Property.
      $method_name = 'set' . ucfirst($field_key);
      if (method_exists($this, $method_name)) {
        $this->$method_name($field_value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUserDefinedListValue(array $field_values = [], $field_key = NULL, $list_key = NULL, $caption = NULL) {
    if (!empty($field_key)) {
      // Process Value list.
      $values = [];
      if (!empty($field_values)) {
        foreach ($field_values as $value) {
          $values[] = ['Value' => $value];
        }
      }
      // Update de values.
      $udl = $this->getUserDefinedLists($indexed = TRUE);
      if (isset($udl[$field_key])) {
        $udl[$field_key]['Values'] = $values;
      }
      else {
        $udl[$field_key] = [
          'Values' => $values,
          'Field' => $field_key,
          'ListKey' => $list_key,
        ];
        if (!empty($caption)) {
          $udl[$field_key]['Caption'] = $caption;
        }
      }
      $this->setUserDefinedLists($udl);
      // Update UDF Property.
      $method_name = 'set' . ucfirst($field_key);
      if (method_exists($this, $method_name)) {
        $this->$method_name($field_values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUserDefinedListValue($field_key = NULL) {
    $udl = $this->getUserDefinedLists($indexed = TRUE);
    $field = isset($udl[$field_key]) ? $udl[$field_key] : NULL;
    if (is_null($field)) {
      return NULL;
    }
    $values = $field['Values'] ?? [];
    if (empty($values)) {
      return NULL;
    }
    $field_values = [];
    foreach ($values as $key => $value) {
      $val = $value['Value'] ?? NULL;
      if ($val) {
        $field_values[] = $val;
      }
    }
    return $field_values;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserDefinedFields($indexed = FALSE) {
    $fields = [];
    $userDefinedFields = $this->userDefinedFields;
    if ($indexed) {
      if (!empty($userDefinedFields)) {
        foreach ($userDefinedFields as $delta => $item) {
          $key = isset($item['Field']) ? $item['Field'] : NULL;
          if ($key) {
            $fields[$key] = $item;
          }
        }
      }
    }
    else {
      $fields = $userDefinedFields;
    }
    return !empty($fields) ? $fields : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function normalizeUserDefinedFields(array $userDefinedFields = []) {
    return !empty($userDefinedFields) ? array_values($userDefinedFields) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setUserDefinedFields(array $userDefinedFields = []) {
    $this->userDefinedFields = $this->normalizeUserDefinedFields($userDefinedFields);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserDefinedLists($indexed = FALSE) {
    $fields = [];
    $userDefinedLists = $this->userDefinedLists;
    if ($indexed) {
      if (!empty($userDefinedLists)) {
        foreach ($userDefinedLists as $delta => $item) {
          $key = isset($item['Field']) ? $item['Field'] : NULL;
          if ($key) {
            $fields[$key] = $item;
          }
        }
      }
    }
    else {
      $fields = $userDefinedLists;
    }
    return !empty($fields) ? $fields : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserDefinedLists($userDefinedLists) {
    $this->userDefinedLists = $this->normalizeUserDefinedFields($userDefinedLists);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCreateEntityApiEndPoint() {
    return self::$createEntityApiEndPoint;
  }

  /**
   * {@inheritdoc}
   */
  public static function getUpdateEntityApiEndPoint() {
    return self::$createEntityApiEndPoint;
  }

  /**
   * Sets the create Entity API endpoint.
   *
   * @param string $createEntityApiEndPoint
   *   The API endpoint.
   */
  public static function setCreateEntityApiEndPoint($createEntityApiEndPoint) {
    self::$createEntityApiEndPoint = $createEntityApiEndPoint;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCreateRequestType() {
    return 'post';
  }

  /**
   * {@inheritdoc}
   */
  public static function getUpdateRequestType() {
    return 'put';
  }

  /**
   * {@inheritdoc}
   */
  public static function getLoadByPropertiesApiEndpoint() {
    return 'PersonSearch';
  }

  /**
   * {@inheritdoc}
   */
  public static function getLoadByPropertiesObjectIdentifierKey() {
    return 'PersonID';
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->setUpdatedBy('web');
    return parent::save();
  }

}
