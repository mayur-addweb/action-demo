<?php

namespace Drupal\am_net;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\node\Entity\Node;
use Drupal\content_paywall\ContentPaywallHelper;
use Exception;

/**
 * The AM.net Person manager.
 *
 * @package Drupal\am_net
 */
class PersonManager {

  /**
   * The AM.net API client.
   *
   * @var \Drupal\am_net\AssociationManagementClient
   */
  protected $client;

  /**
   * The 'am_net' logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new PersonManager.
   *
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The AM.net API client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The 'am_net' logger channel.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(AssociationManagementClient $client, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger) {
    $this->client = $client;
    $this->logger = $logger;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * Gets a Drupal person node for the given AM.net name id.
   *
   * @param int $name_id
   *   The AM.net Person (Name) ID.
   * @param bool $sync
   *   TRUE if the Name should be synced and this operation should try again,
   *   FALSE if this operation should fail without re-syncing the Name/Person.
   *
   * @return \Drupal\node\NodeInterface
   *   The Drupal Person node for the given AM.net name id.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalPerson($name_id, $sync = TRUE) {
    $properties = [
      'type' => 'person',
      'field_amnet_id' => $name_id,
    ];
    /* @var \Drupal\node\NodeInterface $person */
    $person = NULL;
    /* @var \Drupal\node\NodeInterface[] $persons */
    $persons = $this->nodeStorage->loadByProperties($properties);
    // Check if the Speaker have description.
    if (!empty($persons)) {
      $person = current($persons);
      $body = $person->get('body')->getString();
      $re_sync = empty($body);
    }
    else {
      $re_sync = $sync;
    }
    if ($re_sync) {
      $this->syncNameToDrupalPerson($name_id);
      $persons = $this->nodeStorage->loadByProperties($properties);
    }
    if (empty($persons)) {
      throw new AmNetRecordNotFoundException('Could not find Drupal Person node for name id (' . (int) $name_id . ')');
    }
    return current($persons);
  }

  /**
   * Gets an event record from AM.net.
   *
   * @param int $name_id
   *   The AM.net Person (Name) ID.
   *
   * @return array
   *   The AM.net Person (Name) record.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetPerson($name_id) {
    $person = $this->client->get('/Person', [
      'id' => $name_id,
    ]);
    if ($person->hasError()) {
      throw new AmNetRecordNotFoundException($person->getErrorMessage());
    }
    if ($result = $person->getResult()) {
      return $result;
    }
  }

  /**
   * Creates or updates a Drupal person node with an AM.net Person record.
   *
   * @param int $name_id
   *   The AM.net Person (Name) ID.
   */
  public function syncNameToDrupalPerson($name_id) {
    $persons = $this->nodeStorage->loadByProperties([
      'type' => 'person',
      'field_amnet_id' => $name_id,
    ]);
    /* @var \Drupal\node\NodeInterface $person */
    if (empty($persons)) {
      $person = Node::create([
        'type' => 'person',
      ]);
    }
    else {
      $person = current($persons);
    }
    try {
      $am_net_person = $this->getAmNetPerson($name_id);
      $person->set('field_amnet_id', $name_id);
      $person->set('field_givenname', $am_net_person['FirstName']);
      // Update Last name.
      $last_name = [];
      $field_last_name = $am_net_person['LastName'];
      if (!empty($field_last_name)) {
        $last_name[] = $field_last_name;
      }
      $field_middle_initial = $am_net_person['Suffix'];
      if (!empty($field_middle_initial)) {
        $last_name[] = $field_middle_initial;
      }
      $last_name = !empty($last_name) ? implode(' ', $last_name) : NULL;
      $person->set('field_familyname', $last_name);
      $person->set('field_email', $am_net_person['Email']);
      $person->set('field_address', [
        'address_line1' => $am_net_person['HomeAddressLine1'],
        'address_line2' => $am_net_person['HomeAddressLine2'],
        'locality' => $am_net_person['HomeAddressCity'],
        'administrative_area' => $am_net_person['HomeAddressStateCode'],
        'postal_code' => $am_net_person['HomeAddressStreetZip'],
      ]);
      // Update Body.
      $leader_bio = $am_net_person['LeaderBio'] ?? NULL;
      if (!empty($leader_bio)) {
        $body = ['value' => $leader_bio, 'format' => 'full_html'];
      }
      else {
        $body = NULL;
      }
      $person->set('body', $body);
      // Person Type.
      $is_speaker = $am_net_person['IsSpeaker'] ?? FALSE;
      if ($is_speaker) {
        $person->set('field_person_type', 1);
      }
      $is_legislator = $am_net_person['IsLegislator'] ?? FALSE;
      if ($is_legislator) {
        $person->set('field_person_type', 16199);
      }
      $person->set('field_alt_title', NULL);
      // Set Member Only Content - Public Access.
      $person->set('field_memberonly', ContentPaywallHelper::PUBLIC_ACCESS);
      $person->setPublished(TRUE);
      // Change the  moderation state.
      $person->set('moderation_state', 'published');
      $person->save();
    }
    catch (Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

}
