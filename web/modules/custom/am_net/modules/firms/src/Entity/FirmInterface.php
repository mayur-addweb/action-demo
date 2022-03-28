<?php

namespace Drupal\am_net_firms\Entity;

/**
 * Defines the interface for AM.net Firm objects.
 */
interface FirmInterface {

  /**
   * Gets the firm code of the Firm.
   *
   * @return string
   *   The firm code.
   */
  public function getFirmCode();

  /**
   * Sets the firm code of the Firm.
   *
   * @param string $firmCode
   *   The new firm code of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setFirmCode($firmCode);

  /**
   * Enforces an AM.net entity to be new.
   *
   * Allows migrations to create entities with pre-defined IDs by forcing the
   * entity to be new before saving.
   *
   * @param bool $value
   *   (optional) Whether the entity should be forced to be new. Defaults to
   *   TRUE.
   *
   * @return $this
   *
   * @see \Drupal\am_net\AMNetEntityInterface::isNew()
   */
  public function enforceIsNew($value = TRUE);

  /**
   * Alias of set the firm code of the Firm.
   *
   * @param string $id
   *   The new firm code of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setFirm($id);

  /**
   * Gets the index name of the Firm.
   *
   * @return string
   *   The inde name.
   */
  public function getIndexName();

  /**
   * Sets the index name of the Firm.
   *
   * @param string $indexName
   *   The new index name of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setIndexName($indexName);

  /**
   * Gets the firm website.
   *
   * @return string
   *   The website.
   */
  public function getWebsite();

  /**
   * Sets the website of the Firm.
   *
   * @param string $website
   *   The website of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setWebsite($website);

  /**
   * Gets the firm name1 of the Firm.
   *
   * @return string
   *   The firm name1.
   */
  public function getFirmName1();

  /**
   * Sets the firm name 1 of the Firm.
   *
   * @param string $firmName1
   *   The new firm name 1 of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setFirmName1($firmName1);

  /**
   * Gets the firm name 2 of the Firm.
   *
   * @return string
   *   The firm name 2.
   */
  public function getFirmName2();

  /**
   * Sets the firm name 2 of the Firm.
   *
   * @param string $firmName2
   *   The new firm name2 of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setFirmName2($firmName2);

  /**
   * Gets the aicpa number of the Firm.
   *
   * @return string
   *   The aicpa number.
   */
  public function getAicpaNumber();

  /**
   * Sets the AICPA number of the Firm.
   *
   * @param string $aicpaNumber
   *   The new aicpa number of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setAicpaNumber($aicpaNumber);

  /**
   * Gets the phone of the Firm.
   *
   * @return string
   *   The phone.
   */
  public function getPhone();

  /**
   * Sets the phone of the Firm.
   *
   * @param string $phone
   *   The new phone of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setPhone($phone);

  /**
   * Gets the fax of the Firm.
   *
   * @return string
   *   The fax.
   */
  public function getFax();

  /**
   * Sets the fax of the Firm.
   *
   * @param string $fax
   *   The new fax of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setFax($fax);

  /**
   * Gets the flag of the Firm.
   *
   * @return string
   *   The flag.
   */
  public function getFlag();

  /**
   * Sets the flag of the Firm.
   *
   * @param string $flag
   *   The new flag of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setFlag($flag);

  /**
   * Gets the ap terms code of the Firm.
   *
   * @return string
   *   The ap terms code.
   */
  public function getApTermsCode();

  /**
   * Sets the ap terms code of the Firm.
   *
   * @param string $apTermsCode
   *   The new ap terms code of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setApTermsCode($apTermsCode);

  /**
   * Gets the ap flag of the Firm.
   *
   * @return string
   *   The ap flag.
   */
  public function getApFlag();

  /**
   * Sets the ap flag of the Firm.
   *
   * @param string $apFlag
   *   The new ap flag of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setApFlag($apFlag);

  /**
   * Gets the member count of the Firm.
   *
   * @return string
   *   The member count.
   */
  public function getMemberCount();

  /**
   * Sets the member count of the Firm.
   *
   * @param string $memberCount
   *   The new member count of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setMemberCount($memberCount);

  /**
   * Gets the nonmember count of the Firm.
   *
   * @return string
   *   The nonmember count.
   */
  public function getNonmemberCount();

  /**
   * Sets the nonmember count of the Firm.
   *
   * @param string $nonmemberCount
   *   The new nonmember count of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setNonmemberCount($nonmemberCount);

  /**
   * Gets the general business code of the Firm.
   *
   * @return string
   *   The general business code.
   */
  public function getGeneralBusinessCode();

  /**
   * Sets the general business code of the Firm.
   *
   * @param string $generalBusinessCode
   *   The new general business code of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setGeneralBusinessCode($generalBusinessCode);

  /**
   * Gets the specific business code of the Firm.
   *
   * @return string
   *   The specific business code.
   */
  public function getSpecificBusinessCode();

  /**
   * Sets the specific business code of the Firm.
   *
   * @param string $specificBusinessCode
   *   The new specific business code of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setSpecificBusinessCode($specificBusinessCode);

  /**
   * Gets the firm admin email of the Firm.
   *
   * @return string
   *   The firm admin email.
   */
  public function getFirmAdminEmail();

  /**
   * Sets the firm admin email of the Firm.
   *
   * @param string $firmAdminEmail
   *   The new firm admin email of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setFirmAdminEmail($firmAdminEmail);

  /**
   * Gets the person in charge name of the Firm.
   *
   * @return string
   *   The person in charge name.
   */
  public function getPersonInChargeName();

  /**
   * Sets the person in charge name of the Firm.
   *
   * @param string $personInChargeName
   *   The new person in charge name of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setPersonInChargeName($personInChargeName);

  /**
   * Gets the person in charge id of the Firm.
   *
   * @return string
   *   The person in charge id.
   */
  public function getPersonInChargeId();

  /**
   * Sets the person in charge id of the Firm.
   *
   * @param string $personInChargeId
   *   The new person in charge id of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setPersonInChargeId($personInChargeId);

  /**
   * Gets the address of the Firm.
   *
   * @return array
   *   The address.
   */
  public function getAddress();

  /**
   * Sets the address of the Firm.
   *
   * @param string $address
   *   The new address of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setAddress($address);

  /**
   * Gets the user defined fields of the Firm.
   *
   * @return array
   *   The user defined fields.
   */
  public function getUserDefinedFields();

  /**
   * Sets the user defined fields of the Firm.
   *
   * @param string $userDefinedFields
   *   The new user defined fields of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setUserDefinedFields($userDefinedFields);

  /**
   * Gets the user defined lists of the Firm.
   *
   * @return array
   *   The user defined lists.
   */
  public function getUserDefinedLists();

  /**
   * Sets the user defined lists of the Firm.
   *
   * @param string $userDefinedLists
   *   The new user defined lists of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setUserDefinedLists($userDefinedLists);

  /**
   * Sets the main office of the Firm.
   *
   * @param string $mainOffice
   *   The new main office of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setMainOffice($mainOffice);

  /**
   * Gets the Main Office of the Firm.
   *
   * @return string
   *   The main office ID.
   */
  public function getMainOffice();

  /**
   * Sets the linked Persons of the Firm.
   *
   * @param string $linkedPersons
   *   The new linked Persons of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setLinkedPersons($linkedPersons);

  /**
   * Gets the Linked Persons of the Firm.
   *
   * @return array
   *   The Linked Persons IDs.
   */
  public function getLinkedPersons();

  /**
   * Sets the branch offices of the Firm.
   *
   * @param string $branchOffices
   *   The new branch offices of the Firm.
   *
   * @return \Drupal\am_net_firms\Entity\FirmInterface
   *   The called firm entity.
   */
  public function setBranchOffices($branchOffices);

  /**
   * Gets the Branch Offices of the Firm.
   *
   * @return array
   *   The BranchOffices IDs.
   */
  public function getBranchOffices();

}
