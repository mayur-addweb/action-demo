<?php

namespace Drupal\am_net_firms\Entity;

use Drupal\am_net\Entity\AMNetEntity;
use Drupal\taxonomy\TermInterface;

/**
 * Defines object that represent AM.Net Firms.
 */
class Firm extends AMNetEntity implements FirmInterface {

  /**
   * The Firm Code.
   *
   * @var string
   */
  protected $firmCode;

  /**
   * The Index name.
   *
   * @var string
   */
  protected $indexName;

  /**
   * The Firm Name1.
   *
   * @var string
   */
  protected $firmName1;

  /**
   * The Firm Name2.
   *
   * @var string
   */
  protected $firmName2;

  /**
   * The AICPA Number.
   *
   * @var string
   */
  protected $aicpaNumber;

  /**
   * The Phone.
   *
   * @var string
   */
  protected $phone;

  /**
   * The Fax.
   *
   * @var string
   */
  protected $fax;

  /**
   * The Flag.
   *
   * @var string
   */
  protected $flag;

  /**
   * The AP Terms Code.
   *
   * @var string
   */
  protected $apTermsCode;

  /**
   * The AP Flag.
   *
   * @var string
   */
  protected $apFlag;

  /**
   * The Member Count.
   *
   * @var string
   */
  protected $memberCount;

  /**
   * The Nonmember Count.
   *
   * @var string
   */
  protected $nonmemberCount;

  /**
   * The General Business Code.
   *
   * @var string
   */
  protected $generalBusinessCode;

  /**
   * The Specific Business Code.
   *
   * @var string
   */
  protected $specificBusinessCode;

  /**
   * The Firm Admin Email.
   *
   * @var string
   */
  protected $firmAdminEmail;

  /**
   * The Person In Charge Name.
   *
   * @var string
   */
  protected $personInChargeName;

  /**
   * The Person In Charge Id.
   *
   * @var string
   */
  protected $personInChargeId;

  /**
   * The Firm Website.
   *
   * @var string
   */
  protected $website;

  /**
   * The Address.
   *
   * @var array
   */
  protected $address;

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
   * The Linked Persons.
   *
   * @var array
   */
  protected $linkedPersons;

  /**
   * The Main Office.
   *
   * @var array
   */
  protected $mainOffice;

  /**
   * The Branch Offices.
   *
   * @var array
   */
  protected $branchOffices;

  /**
   * The AM.net API endpoint for handle the CREATION of the concrete entity.
   *
   * @var string
   */
  protected static $createEntityApiEndPoint = 'FirmsServer';

  /**
   * The AM.net API endpoint for GET operation.
   *
   * @var string
   */
  protected static $getEntityApiEndPoint = 'Firm';

  /**
   * {@inheritdoc}
   */
  public static function getIdKey() {
    return 'firm';
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmCode() {
    return $this->firmCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmCode($firmCode) {
    $this->firmCode = $firmCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return isset($this->firmCode) ? $this->firmCode : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirm() {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setFirm($id) {
    $this->setFirmCode($id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexName() {
    return $this->indexName;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndexName($indexName) {
    $this->indexName = $indexName;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebsite() {
    return $this->website;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebsite($website) {
    $this->website = $website;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFname1() {
    return $this->getFirmName1();
  }

  /**
   * {@inheritdoc}
   */
  public function setFname1($firmName1) {
    return $this->setFirmName1($firmName1);
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
  public function getFname2() {
    return $this->getFirmName2();
  }

  /**
   * {@inheritdoc}
   */
  public function setFname2($firmName2) {
    return $this->setFirmName2($firmName2);
  }

  /**
   * {@inheritdoc}
   */
  public function getFaddr1() {
    $address = $this->getAddress();
    return isset($address['Line1']) ? $address['Line1'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFaddr1($addr1) {
    $address = $this->getAddress();
    $address['Line1'] = $addr1;
    return $this->setAddress($address);
  }

  /**
   * {@inheritdoc}
   */
  public function getFaddr2() {
    $address = $this->getAddress();
    return isset($address['Line2']) ? $address['Line2'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFaddr2($addr2) {
    $address = $this->getAddress();
    $address['Line2'] = $addr2;
    return $this->setAddress($address);
  }

  /**
   * {@inheritdoc}
   */
  public function getFcity() {
    $address = $this->getAddress();
    return isset($address['City']) ? $address['City'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFcity($city) {
    $address = $this->getAddress();
    $address['City'] = $city;
    return $this->setAddress($address);
  }

  /**
   * {@inheritdoc}
   */
  public function getFst() {
    $address = $this->getAddress();
    return isset($address['StateCode']) ? $address['StateCode'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFst($state_code) {
    $address = $this->getAddress();
    $address['StateCode'] = $state_code;
    return $this->setAddress($address);
  }

  /**
   * {@inheritdoc}
   */
  public function getFzip() {
    $address = $this->getAddress();
    return isset($address['StreetZip']) ? $address['StreetZip'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFzip($street_zip) {
    $address = $this->getAddress();
    $address['StreetZip'] = $street_zip;
    return $this->setAddress($address);
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
  public function getPhone() {
    return $this->phone;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhone($phone) {
    $this->phone = $phone;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFphone() {
    return $this->getPhone();
  }

  /**
   * {@inheritdoc}
   */
  public function setFphone($phone) {
    return $this->setPhone($phone);
  }

  /**
   * {@inheritdoc}
   */
  public function getFax() {
    return $this->fax;
  }

  /**
   * {@inheritdoc}
   */
  public function setFax($fax) {
    $this->fax = $fax;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFfax() {
    return $this->getFax();
  }

  /**
   * {@inheritdoc}
   */
  public function setFfax($fax) {
    return $this->setFax($fax);
  }

  /**
   * {@inheritdoc}
   */
  public function getFlag() {
    return $this->flag;
  }

  /**
   * {@inheritdoc}
   */
  public function setFlag($flag) {
    $this->flag = $flag;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getApTermsCode() {
    return $this->apTermsCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setApTermsCode($apTermsCode) {
    $this->apTermsCode = $apTermsCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getApFlag() {
    return $this->apFlag;
  }

  /**
   * {@inheritdoc}
   */
  public function setApFlag($apFlag) {
    $this->apFlag = $apFlag;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberCount() {
    return $this->memberCount;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberCount($memberCount) {
    $this->memberCount = $memberCount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNonmemberCount() {
    return $this->nonmemberCount;
  }

  /**
   * {@inheritdoc}
   */
  public function setNonmemberCount($nonmemberCount) {
    $this->nonmemberCount = $nonmemberCount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGeneralBusinessCode() {
    return $this->generalBusinessCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setGeneralBusinessCode($generalBusinessCode) {
    $this->generalBusinessCode = $generalBusinessCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGb() {
    return $this->getGeneralBusinessCode();
  }

  /**
   * {@inheritdoc}
   */
  public function setGb($generalBusinessCode) {
    return $this->setGeneralBusinessCode($generalBusinessCode);
  }

  /**
   * {@inheritdoc}
   */
  public function getSpecificBusinessCode() {
    return $this->specificBusinessCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setSpecificBusinessCode($specificBusinessCode) {
    $this->specificBusinessCode = $specificBusinessCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmAdminEmail() {
    return $this->firmAdminEmail;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmAdminEmail($firmAdminEmail) {
    $this->firmAdminEmail = $firmAdminEmail;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersonInChargeName() {
    return $this->personInChargeName;
  }

  /**
   * {@inheritdoc}
   */
  public function setPersonInChargeName($personInChargeName) {
    $this->personInChargeName = $personInChargeName;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersonInChargeId() {
    return $this->personInChargeId;
  }

  /**
   * {@inheritdoc}
   */
  public function setPersonInChargeId($personInChargeId) {
    $this->personInChargeId = $personInChargeId;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress() {
    return $this->address;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddress($address) {
    $this->address = $address;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserDefinedFields() {
    return $this->userDefinedFields;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserDefinedFields($userDefinedFields) {
    $this->userDefinedFields = $userDefinedFields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserDefinedLists() {
    return $this->userDefinedLists;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserDefinedLists($userDefinedLists) {
    $this->userDefinedLists = $userDefinedLists;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkedPersons() {
    return $this->linkedPersons;
  }

  /**
   * {@inheritdoc}
   */
  public function setLinkedPersons($linkedPersons) {
    $this->linkedPersons = $linkedPersons;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMainOffice() {
    return $this->mainOffice;
  }

  /**
   * {@inheritdoc}
   */
  public function setMainOffice($mainOffice) {
    $this->mainOffice = $mainOffice;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBranchOffices($branchOffices) {
    $this->branchOffices = $branchOffices;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBranchOffices() {
    return $this->branchOffices;
  }

  /**
   * {@inheritdoc}
   */
  public static function getApiEndPoint() {
    return self::$getEntityApiEndPoint;
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
   * Gets the load By properties API Endpoint.
   *
   * @return string
   *   The API endpoint.
   */
  public static function getLoadByPropertiesApiEndpoint() {
    return self::$getEntityApiEndPoint;
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
  public static function getLoadByPropertiesObjectIdentifierKey() {
    return 'FirmCode';
  }

  /**
   * Delete Firm Entity.
   *
   * @param int $firm_code
   *   Required param, The Firm Code ID.
   */
  public static function deleteEntity($firm_code = NULL) {
    if (empty($firm_code)) {
      return NULL;
    }
    $firmTerm = self::loadFirmTermByFirmCode($firm_code);
    if (!$firmTerm) {
      return NULL;
    }
    // Delete the record.
    // Save Changes.
    $firmTerm->delete();
  }

  /**
   * Load Firm term By Firm Code.
   *
   * @param int $firm_code
   *   Required param, The Firm Code ID.
   *
   * @return bool|\Drupal\taxonomy\TermInterface
   *   TRUE when the operation was successfully completed, otherwise FALSE
   */
  public static function loadFirmTermByFirmCode($firm_code = NULL) {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['field_amnet_id' => $firm_code]);
    if (!empty($terms)) {
      $term = current($terms);
      if (($term instanceof TermInterface) && ($term->bundle() == 'firm')) {
        return $term;
      }
    }
    return FALSE;
  }

}
