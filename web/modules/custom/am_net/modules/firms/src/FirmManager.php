<?php

namespace Drupal\am_net_firms;

use Drupal\am_net_firms\Entity\Firm;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Default implementation of the Firm Manager.
 */
class FirmManager {

  use FirmSyncTrait;

  /**
   * Fetches a AM.net Firm Codes object by Date.
   *
   * @param string $date
   *   The given date since.
   *
   * @return array|bool
   *   List of AM.net Firm Codes.
   */
  public function getAllFirmByDate($date) {
    return \Drupal::service('am_net.client')->getAllFirmByDate($date);
  }

  /**
   * Fetches all AM.net Firm Codes.
   *
   * @return array
   *   List of AM.net Firm Codes.
   */
  public function getAllFirmCodes() {
    return \Drupal::service('am_net.client')->getAllFirmCodes();
  }

  /**
   * Sync Firm record.
   *
   * @param string $firm_code
   *   Required param, The Firm Code ID.
   * @param string $changeDate
   *   Optional param, The change date.
   *
   * @return bool|int
   *   SAVED_NEW or SAVED_UPDATED on operation was successfully completed,
   *   otherwise FALSE
   */
  public function syncFirmRecord($firm_code = NULL, $changeDate = NULL) {
    // Check if Form code is suitable.
    $firm_code = trim($firm_code);
    if (empty($firm_code)) {
      return FALSE;
    }
    if (strlen($firm_code) > 5) {
      // The firm parameter can only be 5 digits max.
      return FALSE;
    }
    /** @var \Drupal\am_net_firms\Entity\FirmInterface $firm */
    $firm = Firm::load($firm_code);
    if (!$firm || ($firm->getFirmCode() != $firm_code)) {
      return FALSE;
    }
    $name = $firm->getFirmName1();
    // Firm 'name' cannot be null.
    if (!$name) {
      $message = t("Firm 'name' cannot be null: @firmCode", ["@firmCode" => $firm_code]);
      \Drupal::logger('am_net_firms')->notice($message);
      return FALSE;
    }
    // Update Firm Info.
    $updated = FALSE;
    $firmTerm = self::loadFirmTermByFirmCode($firm_code);
    if ($firmTerm == FALSE) {
      // Create the Firm taxonomy Term.
      $firmTerm = Term::create([
        'name' => $name,
        'vid' => 'firm',
      ]);
    }
    else {
      $updated = TRUE;
    }
    // Update Firm Name 1.
    $firmTerm->setName($name);
    // Update Firm Name 2.
    $firmTerm->set('field_firm_name2', $firm->getFirmName2());
    // Update description only if it is empty.
    $description = $firmTerm->get('description')->getString();
    if (empty($description)) {
      $firmTerm->set('description', $firm->getFirmName1());
    }
    // Update AM.net ID.
    $firmTerm->set('field_amnet_id', $firm->getFirmCode());
    // Update Phone.
    $firmTerm->set('field_phone', $firm->getPhone());
    // Update Fax.
    $firmTerm->set('field_fax', $firm->getFax());
    // Update AM.net Member Count.
    $firmTerm->set('field_amnet_member_count', $firm->getMemberCount());
    // Update AM.net Nonmember Count.
    $firmTerm->set('field_amnet_nonmember_count', $firm->getNonmemberCount());
    // Update Address.
    $firmAddress = $firm->getAddress();
    $zip = $firmAddress['MailZip'];
    $postal_code = '';
    if (strpos($zip, '-') !== FALSE) {
      $zipParts = explode('-', $zip);
      $postal_code = $zipParts[0];
    }
    $address = [
      'country_code' => 'US',
      'address_line1' => $firmAddress['Line1'],
      'address_line2' => $firmAddress['Line2'],
      'locality' => $firmAddress['City'],
      'administrative_area' => $firmAddress['StateCode'],
      'postal_code' => $postal_code,
    ];
    $firmTerm->set('field_address', $address);
    // Update Main Office.
    $mainOffice = $firm->getMainOffice();
    $mainOffice = $mainOffice['FirmCode'] ?? NULL;
    $firmTerm->set('field_amnet_main_office', $mainOffice);
    if (!empty($mainOffice)) {
      // Set the parent.
      $parent_tid = $this->loadFirmTidByFirmCode($mainOffice);
    }
    else {
      $parent_tid = NULL;
    }
    if (!empty($parent_tid)) {
      $firmTerm->parent = ['target_id' => $parent_tid];
    }
    else {
      $firmTerm->parent = NULL;
    }
    // Update AM.net Branch Offices.
    $firmBranchOffices = $firm->getBranchOffices();
    $branchOffices = [];
    if (!empty($firmBranchOffices) && is_array($firmBranchOffices)) {
      foreach ($firmBranchOffices as $delta => $branchOffice) {
        if (isset($branchOffice['FirmCode'])) {
          $branchOfficeFirmCode = $branchOffice['FirmCode'];
          $branchOffices[] = ['value' => $branchOfficeFirmCode];
        }
      }
    }
    $firmTerm->field_amnet_branch_offices = $branchOffices;
    // Update AM.net Linked Persons.
    $firmLinkedPersons = $firm->getLinkedPersons();
    $linkedPersons = [];
    if (!empty($firmLinkedPersons) && is_array($firmLinkedPersons)) {
      foreach ($firmLinkedPersons as $delta => $linkedPerson) {
        if (isset($linkedPerson['NamesID'])) {
          $linkedPersonNamesID = $linkedPerson['NamesID'];
          $linkedPersons[] = ['value' => $linkedPersonNamesID];
        }
      }
    }
    $firmTerm->field_amnet_linked_persons = $linkedPersons;
    // Lock the firm's sync so as not to push the changes that
    // come from AM.net.
    $this->lockFirmSyncById($firm_code);
    // Save Changes.
    $firmTerm->save();
    // UnLock the sync for this form code.
    $this->unlockFirmSyncById($firm_code);
    $response = $updated ? SAVED_UPDATED : SAVED_NEW;
    // Return response.
    return $response;
  }

  /**
   * Sync a give Drupal firm term id with a AM.net Firm Record.
   *
   * @param string|null $firm_id
   *   The firm term id.
   * @param bool $verbose
   *   Provides additional details as to what the sync is doing.
   *
   * @return bool|array
   *   The responses from the requested operation, otherwise FALSE.
   */
  public function pushFirmChanges($firm_id = NULL, $verbose = FALSE) {
    if (empty($firm_id)) {
      return FALSE;
    }
    $term = Term::load($firm_id);
    if (!$term) {
      return FALSE;
    }
    // Update AM.net Firm Record from Taxonomy Term.
    $result = $this->updateFirmRecordFormTerm($term, $verbose);
    return $result;
  }

  /**
   * Update Firm Record form Taxonomy Term.
   *
   * @param \Drupal\taxonomy\TermInterface|null $term
   *   The customer entity.
   * @param bool $verbose
   *   Provides additional details about what the synchronization is doing.
   *
   * @return bool|array
   *   The responses from the requested operation, otherwise FALSE.
   */
  public function updateFirmRecordFormTerm(TermInterface $term = NULL, $verbose = FALSE) {
    if (!$term) {
      return ($verbose) ? [] : FALSE;
    }
    $info = [];
    $am_net_id = '';
    /** @var \Drupal\am_net_firms\Entity\FirmInterface $firm */
    // Prepare User info for AM.net.
    $firm = Firm::create([]);
    if ($this->firmIsSynchronized($term)) {
      // Get AM.net ID.
      $am_net_id = $this->getTextFieldValue($term, 'field_amnet_id');
    }
    // AM.net ID.
    if (!empty($am_net_id)) {
      $firm->setFirm($am_net_id);
      $firm->enforceIsNew(FALSE);
      $info[] = ['AM.net ID: ', $am_net_id];
    }
    // Update Firm Name 1.
    $field_value = $term->label();
    $firm->setFirmName1($field_value);
    $info[] = ['Firm Name 1: ', $field_value];
    // Update Firm Name 2.
    $field_value = $this->getTextFieldValue($term, 'field_firm_name2');
    $firm->setFirmName2($field_value);
    $info[] = ['Firm Name 2: ', $field_value];
    // Update Phone.
    $field_value = $this->getTextFieldValue($term, 'field_phone');
    $field_value = !empty($field_value) ? $field_value : NULL;
    $firm->setPhone($field_value);
    $info[] = ['Phone: ', $field_value];
    // Update Fax.
    $field_value = $this->getTextFieldValue($term, 'field_fax');
    $field_value = !empty($field_value) ? $field_value : NULL;
    $firm->setFax($field_value);
    $info[] = ['Fax: ', $field_value];
    // General Business Code.
    $field_value = $this->getTextFieldValue($term, 'field_general_business');
    $field_general_business = !empty($field_value) ? $this->loadGeneralBusinessCodeByGeneralBusinessTid($field_value) : NULL;
    $firm->setGeneralBusinessCode($field_general_business);
    $info[] = ['General Business Code: ', $field_general_business];
    // Address.
    $field_value = $this->getAddressFieldValue($term, 'field_address');
    $address = [];
    // Address Line1.
    $address_line1 = !empty($field_value['address_line1']) ? $field_value['address_line1'] : NULL;
    $address['Line1'] = $address_line1;
    $info[] = ['Address Line1: ', $address_line1];
    // Address Line2.
    $address_line2 = !empty($field_value['address_line2']) ? $field_value['address_line2'] : NULL;
    $address['Line2'] = $address_line2;
    $info[] = ['Address Line2: ', $address_line2];
    // Address City.
    $locality = !empty($field_value['locality']) ? $field_value['locality'] : NULL;
    $address['City'] = $locality;
    $info[] = ['Address City: ', $locality];
    // Address State Code.
    $administrative_area = !empty($field_value['administrative_area']) ? $field_value['administrative_area'] : NULL;
    $address['StateCode'] = $administrative_area;
    $info[] = ['Address State Code: ', $administrative_area];
    // Address Street Zip.
    $postal_code = !empty($field_value['postal_code']) ? $field_value['postal_code'] : NULL;
    $address['StreetZip'] = $postal_code;
    $info[] = ['Address Street Zip: ', $postal_code];
    // Foreign Country.
    $country_code = !empty($field_value['country_code']) ? $field_value['country_code'] : NULL;
    if ($country_code != 'US') {
      $address['ForeignCountry'] = $country_code;
      $info[] = ['Address Foreign Country: ', $country_code];
    }
    $phone = $firm->getPhone();
    if (!empty($phone)) {
      $address['Phone'] = $phone;
    }
    // Set Address.
    $firm->setAddress($address);
    // Set Website.
    $field_value = $this->getTextFieldValue($term, 'field_websites');
    $firm->setWebsite($field_value);
    // Save Changes on the Firm entity in AM.net system.
    // $result = $firm->save();
    $result = NULL;
    if ($verbose) {
      $info['result'] = $result;
    }
    return ($verbose) ? $info : $result;
  }

  /**
   * Update Firm Parents.
   *
   * @param int $term_id
   *   Required param, The term ID.
   */
  public function updateFirmParents($term_id) {
    $term = Term::load($term_id);
    if (!$term) {
      return;
    }
    if ($term->bundle() != 'firm') {
      return;
    }
    // Get branch offices.
    $branch_offices = $term->get('field_amnet_branch_offices')->getValue();
    if (empty($branch_offices)) {
      return;
    }
    $parent_tid = $term->id();
    $main_office_code = $term->get('field_amnet_main_office')->getString();
    // Update branch offices firm parent.
    foreach ($branch_offices as $key => $branch_office_item) {
      $branch_office_code = $branch_office_item['value'] ?? NULL;
      if ($main_office_code != $branch_office_code) {
        $branch_office_tid = !empty($branch_office_code) ? self::loadFirmTidByFirmCode($branch_office_code) : NULL;
        if (!empty($branch_office_tid) && ($branch_office_tid != $parent_tid)) {
          $branch_office = Term::load($branch_office_tid);
          $branch_office->parent = ['target_id' => $parent_tid];
          // Lock the firm's sync so as not to push the changes that
          // come from AM.net.
          $this->lockFirmSyncById($branch_office_code);
          // Save Changes.
          $branch_office->save();
          // UnLock the sync for this form code.
          $this->unlockFirmSyncById($branch_office_code);
        }
      }
    }
  }

  /**
   * Check if a given Term ID has parents.
   *
   * @param int $term_id
   *   Required param, The term ID.
   *
   * @return bool
   *   TRUE if the given Term ID has parents, otherwise FALSE
   */
  public static function termHasParents($term_id = NULL) {
    if (empty($term_id)) {
      return FALSE;
    }
    $ancestors = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($term_id);
    if (empty($ancestors)) {
      return FALSE;
    }
    // Remove Current Term ID of the array of ancestors.
    if (isset($ancestors[$term_id])) {
      unset($ancestors[$term_id]);
    }
    return !empty($ancestors);
  }

  /**
   * Load Firm Term ID By Firm Code.
   *
   * @param string $firm_code
   *   Required param, The Firm Code ID.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public static function loadFirmTidByFirmCode($firm_code = NULL) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'firm');
    $query->condition('field_amnet_id', $firm_code);
    $terms = $query->execute();
    return !empty($terms) ? current($terms) : NULL;
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

  /**
   * Get Firm Terms List.
   *
   * @return array
   *   Firm Terms list.
   */
  public function getFirmTermsList() {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'firm');
    return $query->execute();
  }

  /**
   * Get Firm Terms List with Branch Offices.
   *
   * @return array
   *   Firm Terms list.
   */
  public function getFirmTermsListWithBranchOffices() {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'firm');
    $query->exists('field_amnet_branch_offices');
    return $query->execute();
  }

  /**
   * Check for updates on Firm fields info.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Required param, The drupal term entity.
   * @param \Drupal\taxonomy\TermInterface $original_term
   *   Required param, The drupal original term entity.
   *
   * @return bool
   *   TRUE if the fields have changes, otherwise FALSE.
   */
  public function fieldsHaveChanges(TermInterface $term, TermInterface $original_term) {
    // @todo.
    return TRUE;
  }

  /**
   * Check if firm is Synchronized with AM.net.
   *
   * @param \Drupal\taxonomy\TermInterface|null $term
   *   The term entity.
   *
   * @return bool
   *   TRUE if the term is Synchronized with AM.net, otherwise FALSE.
   */
  public function firmIsSynchronized(TermInterface &$term = NULL) {
    // AM.net ID.
    $am_net_id = $this->getTextFieldValue($term, 'field_amnet_id');
    return ($am_net_id != FALSE);
  }

  /**
   * Add Firm for update their info on AMNet.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Required param, The drupal term entity.
   *
   * @return int|bool
   *   A unique ID if the item was successfully created and was (best effort)
   *   added to the queue, otherwise FALSE. We don't guarantee the item was
   *   committed to disk etc, but as far as we know, the item is now in the
   *   queue.
   */
  public function addFirmUpdateToQueue(TermInterface $term) {
    $item_id = FALSE;
    // Firm id.
    $firm_id = $term->id();
    $queue_name = 'amnet_update_firms_queue';
    $entity = '.firm.';
    $key = $queue_name . $entity . $firm_id;
    $stateService = \Drupal::state();
    $val = $stateService->get($key);
    $alreadyAdded = !is_null($val);
    if (!$alreadyAdded) {
      /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
      $queueFactory = \Drupal::service('queue');
      /** @var \Drupal\Core\Queue\QueueInterface $queue */
      $queue = $queueFactory->get($queue_name);
      $data = [];
      $data['firm_id'] = $firm_id;
      $item_id = $queue->createItem($data);
      $stateService->set($key, $item_id);
    }
    return $item_id;
  }

  /**
   * Get Address field value.
   *
   * @param \Drupal\taxonomy\TermInterface|null $term
   *   The term entity.
   * @param string $field_name
   *   The Field Name.
   *
   * @return array
   *   The Field Address Value.
   */
  public function getAddressFieldValue(TermInterface $term = NULL, $field_name = '') {
    $value = [];
    if (!is_null($term) && !empty($field_name) && $term->hasField($field_name)) {
      $field = $term->get($field_name);
      if ($field) {
        $value = $field->getValue();
        $value = !empty($value) ? current($value) : [];
      }
    }
    return $value;
  }

  /**
   * Get Text field value.
   *
   * @param \Drupal\taxonomy\TermInterface|null $term
   *   The term entity.
   * @param string $field_name
   *   The Field Name.
   *
   * @return string|array|null
   *   The Field Text Values.
   */
  public function getTextFieldValue(TermInterface $term = NULL, $field_name = '') {
    $value = NULL;
    if (!is_null($term) && !empty($field_name) && $term->hasField($field_name)) {
      $field = $term->get($field_name);
      if ($field) {
        $values = $field->getValue();
        if (is_array($values) && !empty($values)) {
          $value = [];
          foreach ($values as $delta => $val) {
            if (is_array($val)) {
              if (isset($val['uri'])) {
                $current_value = $val['uri'];
              }
              else {
                $current_value = current($val);
              }
            }
            else {
              $current_value = $val;
            }
            $value[] = $current_value;
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
   * Load General Business Code By General Business Term ID.
   *
   * @param string $tid
   *   Required param, The GeneralBusiness Term ID.
   *
   * @return bool|string
   *   AM.net Code when the operation was successfully completed, otherwise NULL
   */
  public function loadGeneralBusinessCodeByGeneralBusinessTid($tid = NULL) {
    return $this->loadFieldValueByTid('general_business', $tid, 'field_amnet_gb_code');
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

}
