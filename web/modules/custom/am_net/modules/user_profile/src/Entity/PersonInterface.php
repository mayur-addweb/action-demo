<?php

namespace Drupal\am_net_user_profile\Entity;

/**
 * Defines the interface for AM.net user profile object.
 */
interface PersonInterface {

  /**
   * Sets the email address of the Person.
   *
   * @param string $mail
   *   The new email address of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setEmail($mail);

  /**
   * Gets the email address of the Person.
   *
   * @return string
   *   The email address.
   */
  public function getEmail();

  /**
   * Gets the first name of the Person.
   *
   * @return string
   *   The first name.
   */
  public function getFirstName();

  /**
   * Sets the first name of the Person.
   *
   * @param string $firstName
   *   The new first name of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirstName($firstName);

  /**
   * Gets the update confirmation email flag of the Person.
   *
   * @return string
   *   The update confirmation email flag.
   */
  public function getSendProfileUpdateConfirmationEmail();

  /**
   * Sets the update confirmation email flag of the Person.
   *
   * @param string $value
   *   The update confirmation email flag of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setSendProfileUpdateConfirmationEmail($value);

  /**
   * Sets the flag 'is firm admin' of the Person.
   *
   * @param bool $isFirmAdmin
   *   The flag value.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setIsFirmAdmin($isFirmAdmin);

  /**
   * Gets the flag value 'is firm admin'.
   *
   * @return bool
   *   The flag value 'is firm admin'.
   */
  public function getIsFirmAdmin();

  /**
   * Gets the flag value 'is firm admin'.
   *
   * Alias of the function getIsFirmAdmin().
   *
   * @return bool
   *   The flag value 'is firm admin'.
   */
  public function isFirmAdmin();

  /**
   * Gets the last name of the Person.
   *
   * @return string
   *   The last name.
   */
  public function getLastName();

  /**
   * Sets the last name of the Person.
   *
   * @param string $lastName
   *   The new last name of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setLastName($lastName);

  /**
   * Gets the middle initial of the Person.
   *
   * @return string
   *   The middle initial.
   */
  public function getMiddleInitial();

  /**
   * Sets the middle initial of the Person.
   *
   * @param string $middleInitial
   *   The new middle initial of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setMiddleInitial($middleInitial);

  /**
   * Gets the suffix of the Person.
   *
   * @return string
   *   The suffix.
   */
  public function getSuffix();

  /**
   * Sets the suffix of the Person.
   *
   * @param string $suffix
   *   The new suffix of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setSuffix($suffix);

  /**
   * Gets the gender code of the Person.
   *
   * @return string
   *   The gender code.
   */
  public function getGenderCode();

  /**
   * Sets the gender code of the Person.
   *
   * @param string $genderCode
   *   The new gender code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setGenderCode($genderCode);

  /**
   * Gets the member type code of the Person.
   *
   * @return string
   *   The member type code.
   */
  public function getMemberTypeCode();

  /**
   * Sets the member type code of the Person.
   *
   * @param string $memberTypeCode
   *   The new member type code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setMemberTypeCode($memberTypeCode);

  /**
   * Gets the member status description of the Person.
   *
   * @return string
   *   The member status description.
   */
  public function getMemberStatusDescription();

  /**
   * Sets the member status description of the Person.
   *
   * @param string $memberStatusDescription
   *   The new member status description of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setMemberStatusDescription($memberStatusDescription);

  /**
   * Gets the prospective member source codes of the Person.
   *
   * @return string
   *   The prospective member source codes.
   */
  public function getProspectiveMemberSourceCodes();

  /**
   * Gets the Senate District Code of the Person.
   *
   * @return string
   *   The Senate District Code.
   */
  public function getSenateDistrictCode();

  /**
   * Sets Senate District Code.
   *
   * @param string $value
   *   The senate district code.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setSenateDistrictCode($value);

  /**
   * Gets the Senate Representative ID of the Person.
   *
   * @return string
   *   The Senate Representative ID.
   */
  public function getSenateRepresentativeId();

  /**
   * Sets the Senate Representative ID of the Person.
   *
   * @param string $value
   *   The Representative ID.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setSenateRepresentativeId($value);

  /**
   * Gets the House Representative ID of the Person.
   *
   * @return string
   *   The House Representative ID.
   */
  public function getHouseRepresentativeId();

  /**
   * Sets the House Representative ID of the Person.
   *
   * @param string $value
   *   The Representative ID.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHouseRepresentativeId($value);

  /**
   * Gets the House District Code of the Person.
   *
   * @return string
   *   The House District Code.
   */
  public function getHouseDistrictCode();

  /**
   * Sets House District Code.
   *
   * @param string $value
   *   The senate district code.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHouseDistrictCode($value);

  /**
   * Sets the prospective member source codes of the Person.
   *
   * @param string $prospectiveMemberSourceCodes
   *   The new prospective member source codes of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setProspectiveMemberSourceCodes($prospectiveMemberSourceCodes);

  /**
   * Gets the firm name1 of the Person.
   *
   * @return string
   *   The firm name1.
   */
  public function getFirmName1();

  /**
   * Sets the firm name1 of the Person.
   *
   * @param string $firmName1
   *   The new firm name1 of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirmName1($firmName1);

  /**
   * Gets the firm name2 of the Person.
   *
   * @return string
   *   The firm name2.
   */
  public function getFirmName2();

  /**
   * Sets the firm name2 of the Person.
   *
   * @param string $firmName2
   *   The new firm name2 of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirmName2($firmName2);

  /**
   * Gets the home address line1 of the Person.
   *
   * @return string
   *   The home address line1.
   */
  public function getHomeAddressLine1();

  /**
   * Sets the home address line1 of the Person.
   *
   * @param string $homeAddressLine1
   *   The new home address line1 of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHomeAddressLine1($homeAddressLine1);

  /**
   * Gets the home address line2 of the Person.
   *
   * @return string
   *   The home address line2.
   */
  public function getHomeAddressLine2();

  /**
   * Sets the home address line2 of the Person.
   *
   * @param string $homeAddressLine2
   *   The new home address line2 of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHomeAddressLine2($homeAddressLine2);

  /**
   * Gets the home address city of the Person.
   *
   * @return string
   *   The home address city.
   */
  public function getHomeAddressCity();

  /**
   * Sets the home address city of the Person.
   *
   * @param string $homeAddressCity
   *   The new home address city of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHomeAddressCity($homeAddressCity);

  /**
   * Gets the home address state code of the Person.
   *
   * @return string
   *   The home address state code.
   */
  public function getHomeAddressStateCode();

  /**
   * Sets the home address state code of the Person.
   *
   * @param string $homeAddressStateCode
   *   The new home address state code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHomeAddressStateCode($homeAddressStateCode);

  /**
   * Gets the home address street zip of the Person.
   *
   * @return string
   *   The home address street zip.
   */
  public function getHomeAddressStreetZip();

  /**
   * Sets the home address street zip of the Person.
   *
   * @param string $homeAddressStreetZip
   *   The new home address street zip of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHomeAddressStreetZip($homeAddressStreetZip);

  /**
   * Gets the home address pob zip of the Person.
   *
   * @return string
   *   The home address pob zip.
   */
  public function getHomeAddressPobZip();

  /**
   * Sets the home address pob zip of the Person.
   *
   * @param string $homeAddressPobZip
   *   The new home address pob zip of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHomeAddressPobZip($homeAddressPobZip);

  /**
   * Gets the home address foreign country of the Person.
   *
   * @return string
   *   The home address foreign country.
   */
  public function getHomeAddressForeignCountry();

  /**
   * Sets the home address foreign country of the Person.
   *
   * @param string $homeAddressForeignCountry
   *   The new home address foreign country of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHomeAddressForeignCountry($homeAddressForeignCountry);

  /**
   * Gets the firm address line1 of the Person.
   *
   * @return string
   *   The firm address line1.
   */
  public function getFirmAddressLine1();

  /**
   * Sets the firm address line1 of the Person.
   *
   * @param string $firmAddressLine1
   *   The new firm address line1 of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirmAddressLine1($firmAddressLine1);

  /**
   * Gets the firm address line2 of the Person.
   *
   * @return string
   *   The firm address line2.
   */
  public function getFirmAddressLine2();

  /**
   * Sets the firm address line2 of the Person.
   *
   * @param string $firmAddressLine2
   *   The new firm address line2 of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirmAddressLine2($firmAddressLine2);

  /**
   * Gets the firm address city of the Person.
   *
   * @return string
   *   The firm address city.
   */
  public function getFirmAddressCity();

  /**
   * Sets the firm address city of the Person.
   *
   * @param string $firmAddressCity
   *   The new firm address city of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirmAddressCity($firmAddressCity);

  /**
   * Gets the firm address state code of the Person.
   *
   * @return string
   *   The firm address state code.
   */
  public function getFirmAddressStateCode();

  /**
   * Sets the firm address state code of the Person.
   *
   * @param string $firmAddressStateCode
   *   The new firm address state code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirmAddressStateCode($firmAddressStateCode);

  /**
   * Gets the firm address street zip of the Person.
   *
   * @return string
   *   The firm address street zip.
   */
  public function getFirmAddressStreetZip();

  /**
   * Sets the firm address street zip of the Person.
   *
   * @param string $firmAddressStreetZip
   *   The new firm address street zip of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirmAddressStreetZip($firmAddressStreetZip);

  /**
   * Gets the firm address pob zip of the Person.
   *
   * @return string
   *   The firm address pob zip.
   */
  public function getFirmAddressPobZip();

  /**
   * Sets the firm address pob zip of the Person.
   *
   * @param string $firmAddressPobZip
   *   The new firm address pob zip of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirmAddressPobZip($firmAddressPobZip);

  /**
   * Gets the firm address foreign country of the Person.
   *
   * @return string
   *   The firm address foreign country.
   */
  public function getFirmAddressForeignCountry();

  /**
   * Sets the firm address foreign country of the Person.
   *
   * @param string $firmAddressForeignCountry
   *   The new firm address foreign country of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFirmAddressForeignCountry($firmAddressForeignCountry);

  /**
   * Gets the in state certificate number of the Person.
   *
   * @return string
   *   The in state certificate number.
   */
  public function getInStateCertificateNumber();

  /**
   * Sets the in state certificate number of the Person.
   *
   * @param string $inStateCertificateNumber
   *   The new in state certificate number of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setInStateCertificateNumber($inStateCertificateNumber);

  /**
   * Gets the in state certification date of the Person.
   *
   * @return string
   *   The in state certification date.
   */
  public function getInStateCertificationDate();

  /**
   * Sets the in state certification date of the Person.
   *
   * @param string $inStateCertificationDate
   *   The new in state certification date of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setInStateCertificationDate($inStateCertificationDate);

  /**
   * Gets the out of state certification date of the Person.
   *
   * @return string
   *   The out of state certification date.
   */
  public function getOutOfStateCertificationDate();

  /**
   * Sets the out of state certification date of the Person.
   *
   * @param string $outOfStateCertificationDate
   *   The new out of state certification date of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setOutOfStateCertificationDate($outOfStateCertificationDate);

  /**
   * Gets the out of state certification state code of the Person.
   *
   * @return string
   *   The out of state certification state code.
   */
  public function getOutOfStateCertificationStateCode();

  /**
   * Sets the out of state certification state code of the Person.
   *
   * @param string $outOfStateCertificationStateCode
   *   The new out of state certification state code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setOutOfStateCertificationStateCode($outOfStateCertificationStateCode);

  /**
   * Gets the out of state certificate number of the Person.
   *
   * @return string
   *   The out of state certificate number.
   */
  public function getOutOfStateCertificateNumber();

  /**
   * Sets the out of state certificate number of the Person.
   *
   * @param string $outOfStateCertificateNumber
   *   The new out of state certificate number of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setOutOfStateCertificateNumber($outOfStateCertificateNumber);

  /**
   * Gets the linked firm code of the Person.
   *
   * @return string
   *   The linked firm code.
   */
  public function getLinkedFirmCode();

  /**
   * Sets the linked firm code of the Person.
   *
   * @param string $linkedFirmCode
   *   The new linked firm code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setLinkedFirmCode($linkedFirmCode);

  /**
   * Gets the secondary linked firm code of the Person.
   *
   * @return string
   *   The secondary linked firm code.
   */
  public function getSecondaryLinkedFirmCode();

  /**
   * Sets the secondary linked firm code of the Person.
   *
   * @param string $secondaryLinkedFirmCode
   *   The new secondary linked firm code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setSecondaryLinkedFirmCode($secondaryLinkedFirmCode);

  /**
   * Gets the general mail preference code of the Person.
   *
   * @return string
   *   The general mail preference code.
   */
  public function getGeneralMailPreferenceCode();

  /**
   * Sets the general mail preference code of the Person.
   *
   * @param string $generalMailPreferenceCode
   *   The new general mail preference code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setGeneralMailPreferenceCode($generalMailPreferenceCode);

  /**
   * Gets the cpe mail preference code of the Person.
   *
   * @return string
   *   The cpe mail preference code.
   */
  public function getCpeMailPreferenceCode();

  /**
   * Sets the cpe mail preference code of the Person.
   *
   * @param string $cpeMailPreferenceCode
   *   The new cpe mail preference code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setCpeMailPreferenceCode($cpeMailPreferenceCode);

  /**
   * Gets the position description of the Person.
   *
   * @return string
   *   The position description.
   */
  public function getPositionDescription();

  /**
   * Sets the position description of the Person.
   *
   * @param string $positionDescription
   *   The new position description of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setPositionDescription($positionDescription);

  /**
   * Gets the position code of the Person.
   *
   * @return string
   *   The position code.
   */
  public function getPositionCode();

  /**
   * Sets the position code of the Person.
   *
   * @param string $positionCode
   *   The new position code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setPositionCode($positionCode);

  /**
   * Gets the home phone of the Person.
   *
   * @return string
   *   The home phone.
   */
  public function getHomePhone();

  /**
   * Sets the home phone of the Person.
   *
   * @param string $homePhone
   *   The new home phone of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHomePhone($homePhone);

  /**
   * Gets the direct phone of the Person.
   *
   * @return string
   *   The direct phone.
   */
  public function getDirectPhone();

  /**
   * Sets the direct phone of the Person.
   *
   * @param string $directPhone
   *   The new direct phone of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setDirectPhone($directPhone);

  /**
   * Gets the direct fax of the Person.
   *
   * @return string
   *   The direct fax.
   */
  public function getDirectFax();

  /**
   * Sets the direct fax of the Person.
   *
   * @param string $directFax
   *   The new direct fax of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setDirectFax($directFax);

  /**
   * Gets the nick name of the Person.
   *
   * @return string
   *   The nick name.
   */
  public function getNickName();

  /**
   * Sets the nick name of the Person.
   *
   * @param string $nickName
   *   The new nick name of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setNickName($nickName);

  /**
   * Gets the credentials of the Person.
   *
   * @return string
   *   The credentials.
   */
  public function getCredentials();

  /**
   * Sets the credentials of the Person.
   *
   * @param string $credentials
   *   The new credentials of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setCredentials($credentials);

  /**
   * Gets the certified code of the Person.
   *
   * @return string
   *   The certified code.
   */
  public function getCertifiedCode();

  /**
   * Sets the certified code of the Person.
   *
   * @param string $certifiedCode
   *   The new certified code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setCertifiedCode($certifiedCode);

  /**
   * Gets the title of the Person.
   *
   * @return string
   *   The title.
   */
  public function getTitle();

  /**
   * Sets the title of the Person.
   *
   * @param string $title
   *   The new title of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setTitle($title);

  /**
   * Gets the birth date of the Person.
   *
   * @return string
   *   The birth date.
   */
  public function getBirthDate();

  /**
   * Sets the birth date of the Person.
   *
   * @param string $birthDate
   *   The new birth date of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setBirthDate($birthDate);

  /**
   * Gets the preferred chapter code of the Person.
   *
   * @return string
   *   The preferred chapter code.
   */
  public function getPreferredChapterCode();

  /**
   * Sets the preferred chapter code of the Person.
   *
   * @param string $preferredChapterCode
   *   The new preferred chapter code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setPreferredChapterCode($preferredChapterCode);

  /**
   * Gets the actual chapter code of the Person.
   *
   * @return string
   *   The actual chapter code.
   */
  public function getActualChapterCode();

  /**
   * Sets the actual chapter code of the Person.
   *
   * @param string $actualChapterCode
   *   The new actual chapter code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setActualChapterCode($actualChapterCode);

  /**
   * Gets the spouse of the Person.
   *
   * @return string
   *   The spouse.
   */
  public function getSpouse();

  /**
   * Sets the spouse of the Person.
   *
   * @param string $spouse
   *   The new spouse of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setSpouse($spouse);

  /**
   * Gets the mobile phone of the Person.
   *
   * @return string
   *   The mobile phone.
   */
  public function getMobilePhone();

  /**
   * Sets the mobile phone of the Person.
   *
   * @param string $mobilePhone
   *   The new mobile phone of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setMobilePhone($mobilePhone);

  /**
   * Gets the confirm registration method code of the Person.
   *
   * @return string
   *   The confirm registration method code.
   */
  public function getConfirmRegistrationMethodCode();

  /**
   * Sets the confirm registration method code of the Person.
   *
   * @param string $confirmRegistrationMethodCode
   *   The new confirm registration method code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setConfirmRegistrationMethodCode($confirmRegistrationMethodCode);

  /**
   * Gets the member status code of the Person.
   *
   * @return string
   *   The member status code.
   */
  public function getMemberStatusCode();

  /**
   * Sets the member status code of the Person.
   *
   * @param string $memberStatusCode
   *   The new member status code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setMemberStatusCode($memberStatusCode);

  /**
   * Gets the in house mail exclusion code of the Person.
   *
   * @return string
   *   The in house mail exclusion code.
   */
  public function getInHouseMailExclusionCode();

  /**
   * Sets the in house mail exclusion code of the Person.
   *
   * @param string $inHouseMailExclusionCode
   *   The new in house mail exclusion code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setInHouseMailExclusionCode($inHouseMailExclusionCode);

  /**
   * Gets the third party mail exclusion code of the Person.
   *
   * @return string
   *   The third party mail exclusion code.
   */
  public function getThirdPartyMailExclusionCode();

  /**
   * Sets the third party mail exclusion code of the Person.
   *
   * @param string $thirdPartyMailExclusionCode
   *   The new third party mail exclusion code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setThirdPartyMailExclusionCode($thirdPartyMailExclusionCode);

  /**
   * Gets the fax exclusion code of the Person.
   *
   * @return string
   *   The fax exclusion code.
   */
  public function getFaxExclusionCode();

  /**
   * Sets the fax exclusion code of the Person.
   *
   * @param string $faxExclusionCode
   *   The new fax exclusion code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFaxExclusionCode($faxExclusionCode);

  /**
   * Gets the email exclusion code of the Person.
   *
   * @return string
   *   The email exclusion code.
   */
  public function getEmailExclusionCode();

  /**
   * Sets the email exclusion code of the Person.
   *
   * @param string $emailExclusionCode
   *   The new email exclusion code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setEmailExclusionCode($emailExclusionCode);

  /**
   * Gets the text message exclusion code of the Person.
   *
   * @return string
   *   The text message exclusion code.
   */
  public function getTextMessageExclusionCode();

  /**
   * Sets the text message exclusion code of the Person.
   *
   * @param string $textMessageExclusionCode
   *   The new text message exclusion code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setTextMessageExclusionCode($textMessageExclusionCode);

  /**
   * Gets the minority group code of the Person.
   *
   * @return string
   *   The minority group code.
   */
  public function getMinorityGroupCode();

  /**
   * Sets the minority group code of the Person.
   *
   * @param string $minorityGroupCode
   *   The new minority group code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setMinorityGroupCode($minorityGroupCode);

  /**
   * Gets the member solicitation code of the Person.
   *
   * @return string
   *   The member solicitation code.
   */
  public function getMemberSolicitationCode();

  /**
   * Sets the member solicitation code of the Person.
   *
   * @param string $memberSolicitationCode
   *   The new member solicitation code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setMemberSolicitationCode($memberSolicitationCode);

  /**
   * Gets the city code of the Person.
   *
   * @return string
   *   The city code.
   */
  public function getCityCode();

  /**
   * Sets the city code of the Person.
   *
   * @param string $cityCode
   *   The new city code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setCityCode($cityCode);

  /**
   * Gets the county code of the Person.
   *
   * @return string
   *   The county code.
   */
  public function getCountyCode();

  /**
   * Sets the county code of the Person.
   *
   * @param string $countyCode
   *   The new county code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setCountyCode($countyCode);

  /**
   * Gets the internet exclusion code of the Person.
   *
   * @return string
   *   The internet exclusion code.
   */
  public function getInternetExclusionCode();

  /**
   * Sets the internet exclusion code of the Person.
   *
   * @param string $internetExclusionCode
   *   The new internet exclusion code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setInternetExclusionCode($internetExclusionCode);

  /**
   * Gets the home address fax of the Person.
   *
   * @return string
   *   The home address fax.
   */
  public function getHomeAddressFax();

  /**
   * Sets the home address fax of the Person.
   *
   * @param string $homeAddressFax
   *   The new home address fax of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setHomeAddressFax($homeAddressFax);

  /**
   * Gets the office phone extension of the Person.
   *
   * @return string
   *   The office phone extension.
   */
  public function getOfficePhoneExtension();

  /**
   * Sets the office phone extension of the Person.
   *
   * @param string $officePhoneExtension
   *   The new office phone extension of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setOfficePhoneExtension($officePhoneExtension);

  /**
   * Gets the join date of the Person.
   *
   * @return string
   *   The join date.
   */
  public function getJoinDate();

  /**
   * Sets the join date of the Person.
   *
   * @param string $joinDate
   *   The new join date of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setJoinDate($joinDate);

  /**
   * Gets the join date2 of the Person.
   *
   * @return string
   *   The join date2.
   */
  public function getJoinDate2();

  /**
   * Sets the join date2 of the Person.
   *
   * @param string $joinDate2
   *   The new join date2 of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setJoinDate2($joinDate2);

  /**
   * Gets the billing class code of the Person.
   *
   * @return string
   *   The billing class code.
   */
  public function getBillingClassCode();

  /**
   * Sets the billing class code of the Person.
   *
   * @param string $billingClassCode
   *   The new billing class code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setBillingClassCode($billingClassCode);

  /**
   * Gets the list codes of the Person.
   *
   * @return string
   *   The list codes.
   */
  public function getListCodes();

  /**
   * Sets the list codes of the Person.
   *
   * @param string $listCodes
   *   The new list codes of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setListCodes($listCodes);

  /**
   * Gets the dues paid through of the Person.
   *
   * @return string
   *   The dues paid through.
   */
  public function getDuesPaidThrough();

  /**
   * Sets the dues paid through of the Person.
   *
   * @param string $duesPaidThrough
   *   The new dues paid through of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setDuesPaidThrough($duesPaidThrough);

  /**
   * Gets the aicpa number of the Person.
   *
   * @return string
   *   The aicpa number.
   */
  public function getAicpaNumber();

  /**
   * Sets the aicpa number of the Person.
   *
   * @param string $aicpaNumber
   *   The new aicpa number of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setAicpaNumber($aicpaNumber);

  /**
   * Check if is a ICPA member.
   *
   * @return bool
   *   TRUE if is a ICPA member, otherwise FALSE.
   */
  public function isAicpaMember();

  /**
   * Get the Populate Website User Profile flag.
   *
   * @return bool
   *   Of Bool value of the flag.
   */
  public function getPopulateWebsiteUserProfile();

  /**
   * Sets the aicpa member of the Person.
   *
   * @param string $aicpaMember
   *   The new aicpa member of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setAicpaMember($aicpaMember);

  /**
   * Gets the licensed code of the Person.
   *
   * @return string
   *   The licensed code.
   */
  public function getLicensedCode();

  /**
   * Sets the licensed code of the Person.
   *
   * @param string $licensedCode
   *   The new licensed code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setLicensedCode($licensedCode);

  /**
   * Gets the group membership codes of the Person.
   *
   * @return string
   *   The group membership codes.
   */
  public function getGroupMembershipCodes();

  /**
   * Sets the group membership codes of the Person.
   *
   * @param string $groupMembershipCodes
   *   The new group membership codes of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setGroupMembershipCodes($groupMembershipCodes);

  /**
   * Gets the fields of interest codes of the Person.
   *
   * @return string
   *   The fields of interest codes.
   */
  public function getFieldsOfInterestCodes();

  /**
   * Sets the fields of interest codes of the Person.
   *
   * @param string $fieldsOfInterestCodes
   *   The new fields of interest codes of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFieldsOfInterestCodes($fieldsOfInterestCodes);

  /**
   * Gets the special needs codes of the Person.
   *
   * @return string
   *   The special needs codes.
   */
  public function getSpecialNeedsCodes();

  /**
   * Sets the special needs codes of the Person.
   *
   * @param string $specialNeedsCodes
   *   The new special needs codes of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setSpecialNeedsCodes($specialNeedsCodes);

  /**
   * Gets the areas of expertise codes of the Person.
   *
   * @return string
   *   The areas of expertise codes.
   */
  public function getAreasOfExpertiseCodes();

  /**
   * Sets the areas of expertise codes of the Person.
   *
   * @param string $areasOfExpertiseCodes
   *   The new areas of expertise codes of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setAreasOfExpertiseCodes($areasOfExpertiseCodes);

  /**
   * Gets the sections codes of the Person.
   *
   * @return string
   *   The sections codes.
   */
  public function getSectionsCodes();

  /**
   * Sets the sections codes of the Person.
   *
   * @param string $sectionsCodes
   *   The new sections codes of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setSectionsCodes($sectionsCodes);

  /**
   * Gets the mailstop of the Person.
   *
   * @return string
   *   The mailstop.
   */
  public function getMailstop();

  /**
   * Sets the mailstop of the Person.
   *
   * @param string $mailstop
   *   The new mailstop of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setMailstop($mailstop);

  /**
   * Gets the facebook of the Person.
   *
   * @return string
   *   The facebook.
   */
  public function getFacebook();

  /**
   * Sets the facebook of the Person.
   *
   * @param string $facebook
   *   The new facebook of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setFacebook($facebook);

  /**
   * Gets the linked in of the Person.
   *
   * @return string
   *   The linked in.
   */
  public function getLinkedIn();

  /**
   * Sets the linked in of the Person.
   *
   * @param string $linkedIn
   *   The new linked in of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setLinkedIn($linkedIn);

  /**
   * Gets the twitter of the Person.
   *
   * @return string
   *   The twitter.
   */
  public function getTwitter();

  /**
   * Sets the twitter of the Person.
   *
   * @param string $twitter
   *   The new twitter of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setTwitter($twitter);

  /**
   * Gets the instagram of the Person.
   *
   * @return string
   *   The instagram.
   */
  public function getInstagram();

  /**
   * Sets the instagram of the Person.
   *
   * @param string $instagram
   *   The new instagram of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setInstagram($instagram);

  /**
   * Gets the email opt in codes of the Person.
   *
   * @return string
   *   The email opt in codes.
   */
  public function getEmailOptInCodes();

  /**
   * Sets the email opt in codes of the Person.
   *
   * @param string $emailOptInCodes
   *   The new email opt in codes of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setEmailOptInCodes($emailOptInCodes);

  /**
   * Gets the email opt out codes of the Person.
   *
   * @return string
   *   The email opt out codes.
   */
  public function getEmailOptOutCodes();

  /**
   * Sets the email opt out codes of the Person.
   *
   * @param string $emailOptOutCodes
   *   The new email opt out codes of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setEmailOptOutCodes($emailOptOutCodes);

  /**
   * Gets the nasba id of the Person.
   *
   * @return string
   *   The nasba id.
   */
  public function getNasbaId();

  /**
   * Sets the nasba id of the Person.
   *
   * @param string $nasbaID
   *   The new nasba id of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setNasbaId($nasbaID);

  /**
   * Gets the nasba opt out of the Person.
   *
   * @return string
   *   The nasba opt out.
   */
  public function getNasbaOptOut();

  /**
   * Sets the nasba opt out of the Person.
   *
   * @param string $nasbaOptOut
   *   The new nasba opt out of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setNasbaOptOut($nasbaOptOut);

  /**
   * Gets the email address type code of the Person.
   *
   * @return string
   *   The email address type code.
   */
  public function getEmailAddressTypeCode();

  /**
   * Sets the email address type code of the Person.
   *
   * @param string $emailAddressTypeCode
   *   The new email address type code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setEmailAddressTypeCode($emailAddressTypeCode);

  /**
   * Gets the value 'LeaderBio'.
   *
   * @return string
   *   The value 'LeaderBio'.
   */
  public function getLeaderBio();

  /**
   * Gets the flag value 'is Legislator'.
   *
   * @return bool
   *   The flag value 'is Legislator'.
   */
  public function getIsLegislator();

  /**
   * Gets the flag value 'is speaker'.
   *
   * @return bool
   *   The flag value 'is speaker'.
   */
  public function getIsSpeaker();

  /**
   * Gets the email address2 of the Person.
   *
   * @return string
   *   The email address2.
   */
  public function getEmailAddress2();

  /**
   * Sets the email address2 of the Person.
   *
   * @param string $emailAddress2
   *   The new email address2 of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setEmailAddress2($emailAddress2);

  /**
   * Gets the email address2type code of the Person.
   *
   * @return string
   *   The email address2type code.
   */
  public function getEmailAddress2TypeCode();

  /**
   * Sets the email address2type code of the Person.
   *
   * @param string $emailAddress2TypeCode
   *   The new email address2type code of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setEmailAddress2TypeCode($emailAddress2TypeCode);

  /**
   * Gets the updated by of the Person.
   *
   * @return string
   *   The updated by.
   */
  public function getUpdatedBy();

  /**
   * Sets the updated by of the Person.
   *
   * @param string $updatedBy
   *   The new updated by of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setUpdatedBy($updatedBy);

  /**
   * Gets the last update of the Person.
   *
   * @return string
   *   The last update.
   */
  public function getLastUpdate();

  /**
   * Sets the last update of the Person.
   *
   * @param string $lastUpdate
   *   The new last update of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setLastUpdate($lastUpdate);

  /**
   * Gets the user defined fields of the Person.
   *
   * @param bool $indexed
   *   Flag that determine if the list of fields should be indexed.
   *
   * @return array
   *   The user defined fields.
   */
  public function getUserDefinedFields($indexed = TRUE);

  /**
   * Sets the user defined fields of the Person.
   *
   * @param array $userDefinedFields
   *   The new user defined fields of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setUserDefinedFields(array $userDefinedFields = []);

  /**
   * Gets the user defined lists of the Person.
   *
   * @return string
   *   The user defined lists.
   */
  public function getUserDefinedLists();

  /**
   * Sets the user defined lists of the Person.
   *
   * @param string $userDefinedLists
   *   The new user defined lists of the Person.
   *
   * @return \Drupal\am_net_user_profile\Entity\PersonInterface
   *   The called user entity.
   */
  public function setUserDefinedLists($userDefinedLists);

}
