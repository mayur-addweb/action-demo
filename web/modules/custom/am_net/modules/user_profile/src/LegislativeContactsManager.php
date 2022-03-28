<?php

namespace Drupal\am_net_user_profile;

use Drupal\am_net_user_profile\Entity\PersonInterface;
use Drupal\am_net\AssociationManagementClient;
use Drupal\am_net_user_profile\Entity\Person;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;

/**
 * Legislative Contacts Manager.
 */
class LegislativeContactsManager {

  /**
   * Flag that describes the person type legislator TID.
   */
  const PERSON_TYPE_LEGISLATOR_TID = 16199;

  /**
   * Flag that describes the member only content - public access TID.
   */
  const MEMBER_ONLY_CONTENT_PUBLIC_ACCESS = 15272;

  /**
   * Flag that describes the constituent political relate TID.
   */
  const CONSTITUENT_POLITICAL_RELATE_TID = 16194;

  /**
   * Flag that describes the default active contact level.
   */
  const DEFAULT_ACTIVE_CONTACT_LEVEL = 5;

  /**
   * Flag that describes the default in-active contact level.
   */
  const INACTIVE_CONTACT_LEVEL = 0;

  /**
   * Pull Legislative Contacts.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param int $amnet_id
   *   The AM.net Person (Name) ID.
   * @param int $senate_representative_id
   *   The senate representative ID.
   * @param int $house_representative_id
   *   The house representative ID.
   * @param array $info
   *   The array of info.
   */
  public function pullLegislativeContacts(UserInterface &$user, $amnet_id = NULL, $senate_representative_id = NULL, $house_representative_id = NULL, array &$info = []) {
    // Data Sanitizing.
    $senate_representative_id = trim($senate_representative_id);
    $house_representative_id = trim($house_representative_id);
    $senate_person_id = $this->getPersonIdByAmNetId($senate_representative_id);
    $house_person_id = $this->getPersonIdByAmNetId($house_representative_id);
    $params = ['type' => 'pol_contact'];
    // Set Senator Relationship.
    if (!empty($senate_person_id)) {
      $field_name = 'field_pol_senator_relates';
      $senator_relationship_id = $user->get($field_name)->getValue();
      if (!empty($senator_relationship_id)) {
        // Extract the target id.
        $values = current($senator_relationship_id);
        $senator_relationship_id = $values['target_id'] ?? NULL;
      }
      $senator_relationship = !empty($senator_relationship_id) ? Paragraph::load($senator_relationship_id) : Paragraph::create($params);
      $senator_relationship->set('field_legislator', $senate_person_id);
      $senator_relationship->field_legislator_relates = [self::CONSTITUENT_POLITICAL_RELATE_TID];
      $senator_relationship->save();
      $senator_relationship_id = $senator_relationship->id();
      $field_value = [
        [
          'target_id' => $senator_relationship_id,
          'target_revision_id' => $senator_relationship->getRevisionId(),
        ],
      ];
      $user->set($field_name, $field_value);
      $info[] = ['Set Senator Relationship: ', $senator_relationship_id];
    }
    // Set Delegate Relationship.
    if (!empty($house_person_id)) {
      $field_name = 'field_pol_delegate_relates';
      $delegate_relationship_id = $user->get($field_name)->getValue();
      if (!empty($delegate_relationship_id)) {
        // Extract the target id.
        $values = current($delegate_relationship_id);
        $delegate_relationship_id = $values['target_id'] ?? NULL;
      }
      $delegate_relationship = !empty($delegate_relationship_id) ? Paragraph::load($delegate_relationship_id) : Paragraph::create($params);
      $delegate_relationship->set('field_legislator', $house_person_id);
      $delegate_relationship->field_legislator_relates = [self::CONSTITUENT_POLITICAL_RELATE_TID];
      $delegate_relationship->save();
      $delegate_relationship_id = $delegate_relationship->id();
      $field_value = [
        [
          'target_id' => $delegate_relationship_id,
          'target_revision_id' => $delegate_relationship->getRevisionId(),
        ],
      ];
      $user->set($field_name, $field_value);
      $info[] = ['Set Delegate Relationship: ', $delegate_relationship_id];
    }
    // Set Other Relationship.
    $contacts = \Drupal::service('am_net.client')->getLegislativeContacts($amnet_id);
    if (empty($contacts)) {
      // Clean Other Relationships.
      $field_name = 'field_pol_other_relates';
      $user->set($field_name, NULL);
      $info[] = ['Set Other Relationship: ', 'empty'];
    }
    else {
      $field_name = 'field_pol_other_relates';
      $field_value = $this->getFieldTargetIdsValues($user, $field_name);
      $new_field_value = [];
      // Handle other Relationship.
      foreach ($contacts as $key => $contact) {
        $paragraph_id = $field_value[$key] ?? NULL;
        $is_new = empty($paragraph_id);
        $person_id = $contact['LegislatorNamesId'] ?? NULL;
        $contact_log = $contact['ContactLog'] ?? [];
        if (empty($person_id) && empty($contact_log)) {
          continue;
        }
        $contact_level = $contact['ContactLevel'] ?? self::INACTIVE_CONTACT_LEVEL;
        if (!($contact_level > self::INACTIVE_CONTACT_LEVEL)) {
          // This Contact Level was removed.
          continue;
        }
        $person_id = !empty($person_id) ? trim($person_id) : NULL;
        $contact_log = !empty($contact_log) ? end($contact_log) : $contact_log;
        $paragraph = $this->updateOtherLegislativeValue($person_id, $contact_log, $paragraph_id);
        if (!$paragraph) {
          continue;
        }
        $init_value = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
        if ($is_new) {
          // Save new.
          $new_field_value[] = $init_value;
        }
        else {
          // Update Item.
          $new_field_value[$key] = $init_value;
          unset($field_value[$key]);
        }
      }
      // If there are more items remove them.
      foreach ($field_value as $key => $paragraph_id) {
        $relationship = Paragraph::load($paragraph_id);
        $relationship->delete();
      }
      $user->set($field_name, $new_field_value);
      $info[] = ['Set Other Relationship: ', json_encode($new_field_value)];
    }
  }

  /**
   * Push Legislative Contacts.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param int $amnet_id
   *   The AM.net Person (Name) ID.
   * @param array $info
   *   The info array.
   */
  public function pushLegislativeContacts(UserInterface &$user, $amnet_id = NULL, array $info = []) {
    $client = \Drupal::service('am_net.client');
    $contacts = $this->getLegislativeContactsByAmNetId($amnet_id);
    $current = $user->get('field_pol_other_relates')->getValue();
    $field_value = !empty($current) ? $current : [];
    $item_processed = [];
    foreach ($field_value as $delta => $contact) {
      $paragraph_id = $contact['target_id'] ?? NULL;
      $info = $this->extractLegislativeOtherValues($paragraph_id);
      $legislator_names_id = $info['legislator_names_id'];
      $contact_type_codes = $info['contact_type_codes'];
      $legislator_record = $this->extractLegislatorRelationship($legislator_names_id, $contacts);
      if ($legislator_record == FALSE) {
        // The relationship do not exist: Add process.
        $this->addLegislatorContact($amnet_id, $legislator_names_id, $contact_type_codes, $client);
      }
      else {
        // The relation exist: Update process.
        $this->updateLegislatorContact($amnet_id, $legislator_names_id, $contact_type_codes, $legislator_record, $client);
      }
      $item_processed[] = $legislator_names_id;
    }
    // Handle the remove items.
    foreach ($contacts as $delta => $contact) {
      $legislator_name_id = $contact['LegislatorNamesId'] ?? NULL;
      if (empty($legislator_name_id)) {
        continue;
      }
      $legislator_name_id = trim($legislator_name_id);
      if (!in_array($legislator_name_id, $item_processed)) {
        $this->removeLegislatorContact($amnet_id, $legislator_name_id, $contact, $client);
      }
    }
  }

  /**
   * Extract Legislative other values.
   *
   * @param string $paragraph_id
   *   The paragraph ID.
   *
   * @return array
   *   The default legislative other value.
   */
  public function extractLegislativeOtherValues($paragraph_id = NULL) {
    $default_value = [
      'legislator_names_id' => NULL,
      'contact_type_codes' => [],
    ];
    if (empty($paragraph_id)) {
      return $default_value;
    }
    $relationship = Paragraph::load($paragraph_id);
    if (!$relationship) {
      return $default_value;
    }
    // Get Legislator Names ID.
    $person_id = $relationship->get('field_legislator')->getString();
    $person = Node::load($person_id);
    $default_value['legislator_names_id'] = ($person) ? $person->get('field_amnet_id')->getString() : NULL;
    // Get contact type codes.
    $fields = $relationship->get('field_legislator_relates')->getValue();
    if (empty($fields)) {
      return $default_value;
    }
    $items = [];
    foreach ($fields as $delta => $item) {
      $target_id = $item['target_id'] ?? NULL;
      if (empty($target_id)) {
        continue;
      }
      $term = Term::load($target_id);
      $code = $term ? $term->get('field_amnet_id')->getString() : NULL;
      if (!empty($code)) {
        $items[] = $code;
      }
    }
    $default_value['contact_type_codes'] = $items;
    return $default_value;
  }

  /**
   * Update Legislator Contact.
   *
   * @param int $name_id
   *   The AM.net Person (Name) ID.
   * @param int $legislator_names_id
   *   The legislator Name ID.
   * @param array $contact_type_codes
   *   The contact type codes.
   * @param array $legislator_record
   *   The base array with the legislator record.
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The Api Client instance.
   *
   * @return bool
   *   Return TRUE of the legislator contact was added, otherwise FALSE.
   */
  public function updateLegislatorContact($name_id = NULL, $legislator_names_id = NULL, array $contact_type_codes = [], array $legislator_record = [], AssociationManagementClient $client = NULL) {
    if (empty($name_id) || empty($legislator_names_id) || empty($legislator_record)) {
      return FALSE;
    }
    $item_legislator_names_id = $this->addLeadingPadding($legislator_names_id);
    $item_names_id = $this->addLeadingPadding($name_id);
    $contact_log = $legislator_record['ContactLog'] ?? [];
    $contact_log = !empty($contact_log) ? end($contact_log) : $contact_log;
    $contact_log['ContactTypeCodes'] = implode(',', $contact_type_codes);
    $contact_log['LegislatorId'] = $item_legislator_names_id;
    // Determine Contact Level.
    $contact_level = $legislator_record['ContactLevel'] ?? self::DEFAULT_ACTIVE_CONTACT_LEVEL;
    $contact_level = (intval($contact_level) > 0) ? $contact_level : self::DEFAULT_ACTIVE_CONTACT_LEVEL;
    $record = [
      'NamesId' => $item_names_id,
      'LegislatorNamesId' => $item_legislator_names_id,
      'ContactLevel' => $contact_level,
      'ContactLog' => [$contact_log],
    ];
    $json_record = json_encode($record);
    try {
      $endpoint = "person/{$name_id}/legislativecontacts";
      $response = $client->put($endpoint, [], $json_record);
      $result = ($response && ($response->getStatusCode() == 200));
    }
    catch (\Exception $e) {
      $result = FALSE;
    }
    return $result;
  }

  /**
   * Add Legislator Contact.
   *
   * @param int $name_id
   *   The AM.net Person (Name) ID.
   * @param int $legislator_names_id
   *   The legislator Name ID.
   * @param array $contact_type_codes
   *   The contact type codes.
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The Api Client instance.
   *
   * @return bool
   *   Return TRUE of the legislator contact was added, otherwise FALSE.
   */
  public function addLegislatorContact($name_id = NULL, $legislator_names_id = NULL, array $contact_type_codes = [], AssociationManagementClient $client = NULL) {
    if (empty($name_id) || empty($legislator_names_id)) {
      return FALSE;
    }
    $item_legislator_names_id = $this->addLeadingPadding($legislator_names_id);
    $item_names_id = $this->addLeadingPadding($name_id);
    $record = [
      'NamesId' => $item_names_id,
      'LegislatorNamesId' => $item_legislator_names_id,
      'ContactLevel' => self::DEFAULT_ACTIVE_CONTACT_LEVEL,
      "RecordAdded" => date("Y-m-d"),
      "RecordAddedBy" => "web",
    ];
    $json_record = json_encode($record);
    // POST: {ApiRoot}/person/{$amnet_id}/legislativecontacts.
    $endpoint = "person/{$name_id}/legislativecontacts";
    try {
      $response = $client->post($endpoint, [], $json_record);
      $success = ($response && ($response->getStatusCode() == 200));
    }
    catch (\Exception $e) {
      $success = FALSE;
    }
    if (!$success) {
      return FALSE;
    }
    // Add the Contact Log.
    $contact_log = [
      'Year' => date('Y'),
      'ContactTypeCodes' => implode(',', $contact_type_codes),
      'ContactSourceCode' => "L",
      'PoliticalPartyCode' => "",
      'Note' => "",
    ];
    $record['AddContactLog'][] = $contact_log;
    unset($record['RecordAdded']);
    unset($record['RecordAddedBy']);
    $json_record = json_encode($record);
    try {
      $response = $client->put($endpoint, [], $json_record);
      $result = ($response && ($response->getStatusCode() == 200));
    }
    catch (\Exception $e) {
      $result = FALSE;
    }
    return $result;
  }

  /**
   * Remove Legislator Contact.
   *
   * @param int $name_id
   *   The AM.net Person (Name) ID.
   * @param int $legislator_names_id
   *   The legislator Name ID.
   * @param array $legislator_record
   *   The base array with the legislator record.
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The Api Client instance.
   *
   * @return bool
   *   Return TRUE of the legislator contact was added, otherwise FALSE.
   */
  public function removeLegislatorContact($name_id = NULL, $legislator_names_id = NULL, array $legislator_record = [], AssociationManagementClient $client = NULL) {
    if (empty($name_id) || empty($legislator_names_id) || empty($legislator_record)) {
      return FALSE;
    }
    // Skip if the record is already Inactive.
    $current_contact_level = $legislator_record['ContactLevel'] ?? NULL;
    if ($current_contact_level == self::INACTIVE_CONTACT_LEVEL) {
      return TRUE;
    }
    $item_legislator_names_id = $this->addLeadingPadding($legislator_names_id);
    $item_names_id = $this->addLeadingPadding($name_id);
    // Set Contact Level.
    $contact_level = self::INACTIVE_CONTACT_LEVEL;
    $record = [
      'NamesId' => $item_names_id,
      'LegislatorNamesId' => $item_legislator_names_id,
      'ContactLevel' => $contact_level,
    ];
    $json_record = json_encode($record);
    try {
      $endpoint = "person/{$name_id}/legislativecontacts";
      $response = $client->put($endpoint, [], $json_record);
      $result = ($response && ($response->getStatusCode() == 200));
    }
    catch (\Exception $e) {
      $result = FALSE;
    }
    return $result;
  }

  /**
   * Add Leading Padding to a given string.
   *
   * @param string $text
   *   The variable.
   * @param int $pad_length
   *   The the pad length.
   *
   * @return string
   *   Returns the padded string.
   */
  public function addLeadingPadding($text = '', $pad_length = 6) {
    return str_pad($text, $pad_length, " ", STR_PAD_LEFT);
  }

  /**
   * Extract Legislator relation by name ID.
   *
   * @param string $name_id
   *   The legislator name ID.
   * @param array $contacts
   *   The list of contacts.
   *
   * @return array|bool
   *   The legislative relation.
   */
  public function extractLegislatorRelationship($name_id = NULL, array $contacts = []) {
    if (empty($contacts)) {
      return FALSE;
    }
    foreach ($contacts as $delta => $contact) {
      $legislator_name_id = $contact['LegislatorNamesId'] ?? NULL;
      if (empty($legislator_name_id)) {
        continue;
      }
      $legislator_name_id = trim($legislator_name_id);
      if ($legislator_name_id == $name_id) {
        return $contact;
      }
    }
    return FALSE;
  }

  /**
   * Get Default Legislative values.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string $field_name
   *   The Field Name.
   *
   * @return array|null
   *   The default legislative value.
   */
  public function getDefaultLegislativeValues(UserInterface &$user = NULL, $field_name = NULL) {
    if (!$user || empty($field_name)) {
      return NULL;
    }
    $paragraph_items = $user->get($field_name)->getValue();
    if (empty($paragraph_items)) {
      return NULL;
    }
    // Extract the target id.
    $values = current($paragraph_items);
    $paragraph_id = $values['target_id'] ?? NULL;
    if (empty($paragraph_id)) {
      return NULL;
    }
    $relationship = Paragraph::load($paragraph_id);
    if (!$relationship) {
      return NULL;
    }
    $default_value = [
      'familiarities' => [],
      'person_id' => NULL,
      'paragraph_id' => NULL,
    ];
    $default_value['paragraph_id'] = $relationship->id();
    $default_value['person_id'] = $relationship->get('field_legislator')->getString();
    $fields = $relationship->get('field_legislator_relates')->getValue();
    if (!empty($fields)) {
      foreach ($fields as $delta => $item) {
        if (isset($item['target_id'])) {
          $default_value['familiarities'][] = $item['target_id'];
        }
      }
    }
    return $default_value;
  }

  /**
   * Extract Legislative values.
   *
   * @param string $paragraph_id
   *   The paragraph ID.
   *
   * @return array|null
   *   The default legislative value.
   */
  public function extractLegislativeValues($paragraph_id = NULL) {
    $default_value = [
      'familiarities' => [],
      'person_id' => NULL,
      'paragraph_id' => $paragraph_id,
    ];
    if (empty($paragraph_id)) {
      return $default_value;
    }
    $relationship = Paragraph::load($paragraph_id);
    if (!$relationship) {
      return $default_value;
    }
    $default_value['person_id'] = $relationship->get('field_legislator')->getString();
    $fields = $relationship->get('field_legislator_relates')->getValue();
    if (!empty($fields)) {
      foreach ($fields as $delta => $item) {
        if (isset($item['target_id'])) {
          $default_value['familiarities'][] = $item['target_id'];
        }
      }
    }
    return $default_value;
  }

  /**
   * Pull Legislative Contact value.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string $field_name
   *   The field name.
   * @param int $person_id
   *   The person ID.
   * @param array $familiarity
   *   The list of familiarity.
   */
  public function updateDefaultLegislativeValue(UserInterface &$user = NULL, $field_name = NULL, $person_id = NULL, array $familiarity = []) {
    $paragraph_id = NULL;
    $paragraph_items = $user->get($field_name)->getValue();
    if (!empty($paragraph_items)) {
      // Extract the target id.
      $values = current($paragraph_items);
      $paragraph_id = $values['target_id'] ?? NULL;
    }
    $relationship = !empty($paragraph_id) ? Paragraph::load($paragraph_id) : Paragraph::create(['type' => 'pol_contact']);
    $relationship->set('field_legislator', $person_id);
    $relationship->field_legislator_relates = $familiarity;
    $relationship->save();
    $field_value = [
      [
        'target_id' => $relationship->id(),
        'target_revision_id' => $relationship->getRevisionId(),
      ],
    ];
    $user->set($field_name, $field_value);
  }

  /**
   * Add Other Legislator Contact.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param array $other_items
   *   The items.
   */
  public function updateOtherLegislativeValues(UserInterface &$user = NULL, array $other_items = []) {
    if (!$user || empty($other_items)) {
      return;
    }
    $field_name = 'field_pol_other_relates';
    $current = $user->get($field_name)->getValue();
    $field_value = !empty($current) ? $current : [];
    foreach ($other_items as $key => $item) {
      $person_id = $item['person_id'];
      $familiarity = $item['familiarity'];
      $paragraph_id = $item['paragraph_id'];
      $op = $item['op'];
      // Handle Add or Update operations.
      if (in_array($op, ['update', 'new'])) {
        $relationship = ($op == 'update') ? Paragraph::load($paragraph_id) : Paragraph::create(['type' => 'pol_contact']);
        $relationship->set('field_legislator', $person_id);
        $relationship->field_legislator_relates = $familiarity;
        $relationship->save();
        $init_value = [
          'target_id' => $relationship->id(),
          'target_revision_id' => $relationship->getRevisionId(),
        ];
        if ($op == 'new') {
          $field_value[] = $init_value;
        }
        elseif ($op == 'update') {
          foreach ($field_value as $delta => $value) {
            $target_id = $value['target_id'];
            if ($target_id == $relationship->id()) {
              $field_value[$delta] = $init_value;
              break;
            }
          }
        }
      }
      // Handle delete operation.
      if (($op == 'remove') && !empty($paragraph_id)) {
        foreach ($field_value as $delta => $value) {
          $target_id = $value['target_id'];
          if ($target_id == $paragraph_id) {
            unset($field_value[$delta]);
            $relationship = Paragraph::load($paragraph_id);
            $relationship->delete($paragraph_id);
            break;
          }
        }
      }
    }
    $user->set($field_name, $field_value);
  }

  /**
   * Add Other Legislator Contact.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param int $person_id
   *   The person ID.
   * @param array $familiarity
   *   The list of familiarity.
   */
  public function addOtherLegislatorContact(UserInterface &$user = NULL, $person_id = NULL, array $familiarity = []) {
    if (!$user || empty($person_id) || empty($familiarity)) {
      return;
    }
    $field_name = 'field_pol_other_relates';
    $relationship = Paragraph::create(['type' => 'pol_contact']);
    $relationship->set('field_legislator', $person_id);
    $relationship->field_legislator_relates = $familiarity;
    $relationship->save();
    // Add item.
    $current = $user->get($field_name)->getValue();
    $field_value = !empty($current) ? $current : [];
    $field_value[] = [
      'target_id' => $relationship->id(),
      'target_revision_id' => $relationship->getRevisionId(),
    ];
    $user->set($field_name, $field_value);
  }

  /**
   * Update Other Legislative Value.
   *
   * @param string $amnet_id
   *   The person ID.
   * @param array $contact_log
   *   The contact log.
   * @param string $paragraph_id
   *   The paragraph ID.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph|null
   *   The relationship object, otherwise NULL.
   */
  public function updateOtherLegislativeValue($amnet_id = NULL, array $contact_log = [], $paragraph_id = NULL) {
    if (empty($amnet_id) || empty($contact_log)) {
      return NULL;
    }
    $person_id = $this->getPersonIdByAmNetId($amnet_id);
    // Decode familiarity.
    $familiarity_codes = $contact_log['ContactTypeCodes'] ?? NULL;
    $familiarity = $this->decodeContactTypeCodes($familiarity_codes);
    if (!$familiarity) {
      $familiarity = [self::CONSTITUENT_POLITICAL_RELATE_TID];
    }
    /* @var \Drupal\paragraphs\Entity\Paragraph $relationship */
    $relationship = !empty($paragraph_id) ? Paragraph::load($paragraph_id) : Paragraph::create(['type' => 'pol_contact']);
    $relationship->set('field_legislator', $person_id);
    $relationship->field_legislator_relates = $familiarity;
    $relationship->save();
    return $relationship;
  }

  /**
   * Decode Contact type Codes.
   *
   * @param string $codes
   *   The codes.
   *
   * @return array
   *   The list of familiarities TIDs.
   */
  public function decodeContactTypeCodes($codes = NULL) {
    if (empty($codes)) {
      return [];
    }
    $items = str_split($codes);
    $database = \Drupal::database();
    $query = $database->select('taxonomy_term__field_amnet_id', 't');
    $query->fields('t', ['entity_id']);
    $query->condition('field_amnet_id_value', $items, 'IN');
    return $query->execute()->fetchCol();
  }

  /**
   * Get legislative Relationship ID.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string $field_name
   *   The Field Name.
   *
   * @return string|null
   *   The target value.
   */
  public function getLegislativeRelationshipId(UserInterface &$user, $field_name = NULL) {
    if (!$user || empty($field_name)) {
      return NULL;
    }
    $relationship_id = $user->get($field_name)->getValue();
    if (!empty($relationship_id)) {
      // Extract the target id.
      $values = current($relationship_id);
      $relationship_id = $values['target_id'] ?? NULL;
    }
    if (empty($relationship_id)) {
      return NULL;
    }
    $relationship = Paragraph::load($relationship_id);
    $value = $relationship->get('field_legislator')->getString();
    return !empty($value) ? $value : NULL;
  }

  /**
   * Get field target Ids values.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string $field_name
   *   The Field Name.
   *
   * @return array|null
   *   The target values.
   */
  public function getFieldTargetIdsValues(UserInterface &$user, $field_name = NULL) {
    if (!$user || empty($field_name)) {
      return [];
    }
    $field_value = $user->get($field_name)->getValue();
    if (empty($field_value)) {
      return [];
    }
    $items = [];
    foreach ($field_value as $delta => $value) {
      $target_id = $value['target_id'] ?? NULL;
      if (!empty($target_id)) {
        $items[] = $target_id;
      }
    }
    return $items;
  }

  /**
   * Get the Person id By AMNet ID.
   *
   * @param string $amnet_id
   *   The AMNet Name ID.
   *
   * @return string|null
   *   The person content ID otherwise NULL.
   */
  public function getPersonIdByAmNetId($amnet_id = NULL) {
    if (empty($amnet_id)) {
      return NULL;
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'person');
    $query->condition('field_amnet_id', $amnet_id);
    $ids = $query->execute();
    $id = !empty($ids) ? reset($ids) : NULL;
    return $id;
  }

  /**
   * Get the legislative Contacts By AMNet ID.
   *
   * @param string $amnet_id
   *   The AMNet Name ID.
   *
   * @return array
   *   The array list of Legislators.
   */
  public function getLegislativeContactsByAmNetId($amnet_id = NULL) {
    return \Drupal::service('am_net.client')->getLegislativeContacts($amnet_id);
  }

  /**
   * Get the list of Legislators.
   *
   * @return array
   *   The array list of Legislators.
   */
  public function getLegislators() {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'person');
    $query->condition('field_person_type', self::PERSON_TYPE_LEGISLATOR_TID);
    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($ids);
    $items = [];
    /* @var \Drupal\node\NodeInterface $node */
    foreach ($nodes as $delta => $node) {
      $id = $node->id();
      $items[$id] = $this->getPersonFirstName($node);
    }
    return $items;
  }

  /**
   * Sync a give AM.net Person Record with a Content Person Drupal content.
   *
   * @param string $names_id
   *   The AMNet Name ID or a Valid Name Email.
   * @param bool $verbose
   *   Provides additional details as to what the sync is doing.
   *
   * @return bool|int|array
   *   Int Either SAVED_NEW or SAVED_UPDATED, depending on the operation
   *   performed, array if is in a drush context, otherwise FALSE.
   */
  public function pullContentPerson($names_id = NULL, $verbose = FALSE) {
    if (empty($names_id)) {
      return FALSE;
    }
    $names_id = trim($names_id);
    $person = Person::load($names_id);
    // Validate that the person record exits.
    if (($person == FALSE) || !($person instanceof PersonInterface)) {
      return FALSE;
    }
    // Look up a Drupal node related to the AM.net ID.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'person');
    $query->condition('field_amnet_id', $names_id);
    $ids = $query->execute();
    $id = !empty($ids) ? reset($ids) : NULL;
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node = !empty($id) ? $node_storage->load($id) : NULL;
    /* @var \Drupal\node\NodeInterface $node */
    $node = $node ?? Node::create(['type' => 'person']);
    // Check if the person is legislator or if is speaker.
    if ($person->getIsLegislator() || $person->getIsSpeaker()) {
      // Update content person info from AM.net Person record info.
      $result = $this->updateContentPersonFromAmNetPerson($node, $person, $verbose);
    }
    else {
      // UnPublish Node if exists.
      if (!$node->isNew()) {
        $node->setPublished(FALSE);
        $node->save();
      }
      $result = -1;
    }
    // Return Result.
    return $result;
  }

  /**
   * Update a Drupal content person give AM.net Person Record.
   *
   * @param \Drupal\node\NodeInterface|null $node
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
  public function updateContentPersonFromAmNetPerson(NodeInterface &$node = NULL, PersonInterface $person = NULL, $verbose = FALSE) {
    if (!$node || !$person) {
      return FALSE;
    }
    $info = [];
    // Update Drupal Person Content.
    $field_amnet_id = $person->id();
    $field_amnet_id = trim($field_amnet_id);
    $node->set('field_amnet_id', $field_amnet_id);
    $info[] = ['AM.net ID: ', $field_amnet_id];
    // Update First Name.
    $field_givenname = $person->getFirstName();
    $node->set('field_givenname', $field_givenname);
    $info[] = ['First Name: ', $field_givenname];
    // Update Last Name.
    $last_name = [];
    $field_last_name = $person->getLastName();
    if (!empty($field_last_name)) {
      $last_name[] = $field_last_name;
    }
    $field_middle_initial = $person->getSuffix();
    if (!empty($field_middle_initial)) {
      $last_name[] = $field_middle_initial;
    }
    $last_name = !empty($last_name) ? implode(' ', $last_name) : NULL;
    $node->set('field_familyname', $last_name);
    $info[] = ['Last Name: ', $last_name];
    // Update Alternate Title.
    $alt_title = [];
    $field_middle_initial = $person->getMiddleInitial();
    if (!empty($field_middle_initial)) {
      $alt_title[] = $field_middle_initial;
    }
    $field_suffix = $person->getSuffix();
    if (!empty($field_suffix)) {
      $alt_title[] = $field_suffix;
    }
    $alt_title = !empty($alt_title) ? implode(' ', $alt_title) : NULL;
    $node->set('field_alt_title', $alt_title);
    $info[] = ['Alternate Title: ', $alt_title];
    // Update Email.
    $field_email = $person->getEmail();
    $node->set('field_email', $field_email);
    $info[] = ['Email: ', $field_email];
    // Update Person Type.
    if ($person->getIsLegislator()) {
      $person_type = self::PERSON_TYPE_LEGISLATOR_TID;
    }
    elseif ($person->getIsSpeaker()) {
      $person_type = 1;
      $node->set('field_alt_title', NULL);
    }
    else {
      $person_type = NULL;
    }
    $node->set('field_person_type', $person_type);
    $info[] = ['Person Type: ', $person_type];
    // Update member only Content.
    $node->set('field_memberonly', self::MEMBER_ONLY_CONTENT_PUBLIC_ACCESS);
    // Home Information.
    $line1 = $person->getHomeAddressLine1();
    $line2 = $person->getHomeAddressLine2();
    $city = $person->getHomeAddressCity();
    $stateCode = $person->getHomeAddressStateCode();
    $pobZip = $person->getHomeAddressPobZip();
    $field_home_address = [
      'country_code' => 'US',
      'address_line1' => $line1,
      'address_line2' => $line2,
      'locality' => $city,
      'postal_code' => $pobZip,
      'administrative_area' => $stateCode,
    ];
    $node->set('field_address', $field_home_address);
    $info[] = ['Address: ', implode($field_home_address, ',')];
    // Get District Code.
    $districtCode = $person->getHouseDistrictCode();
    if (empty($districtCode)) {
      $districtCode = $person->getSenateDistrictCode();
    }
    $node->set('field_pol_district', $districtCode);
    $info[] = ['District: ', $districtCode];
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
    $node->field_field_of_interest = $info_items;
    $info[] = ['Fields of Interest: ', implode(', ', $info_items)];
    // Update Body.
    $leader_bio = $person->getLeaderBio();
    if (!empty($leader_bio)) {
      $body = ['value' => $leader_bio, 'format' => 'full_html'];
    }
    else {
      $body = NULL;
    }
    $node->set('body', $body);
    // Publish the content.
    $node->setPublished(TRUE);
    // Change the  moderation state.
    $node->set('moderation_state', 'published');
    // Save Changes on the Drupal Person Content.
    $result = $node->save();
    $info[] = ['Node id: ', $node->id()];
    if ($verbose) {
      $info['result'] = $result;
    }
    return ($verbose) ? $info : $result;
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
   * Get Person Summary.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The person ID.
   *
   * @return string
   *   The Person Summary, NULL otherwise.
   */
  public function getPersonFirstName(Node $node = NULL) {
    if (!$node) {
      return NULL;
    }
    $first_name = $node->get('field_givenname')->getString();
    $last_name = $node->get('field_familyname')->getString();
    $alt_title = $node->get('field_alt_title')->getString();
    $distric_code = $node->get('field_pol_district')->getString();
    // First Line.
    $first_line = "{$last_name}, {$first_name} {$alt_title}";
    $first_line = trim($first_line);
    $first_line = rtrim($first_line, '.') . '.';
    if (!empty($distric_code)) {
      $first_line .= " District {$distric_code}";
    }
    return $first_line;
  }

}
