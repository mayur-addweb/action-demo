<?php

namespace Drupal\am_net_cpe;

use Drupal\am_net\DrupalRecordNotFoundException;
use Drupal\am_net\AssociationManagementClient;
use Drupal\am_net_user_profile\UserProfileManager;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\rng\EventManagerInterface as RngEventManagerInterface;
use Drupal\user\UserInterface;

/**
 * AM.net CPE Registration Manager.
 */
class CpeRegistrationManager implements CpeRegistrationManagerInterface {

  /**
   * The AM.net REST API client.
   *
   * @var \Drupal\am_net\AssociationManagementClient
   */
  protected $client;

  /**
   * The AM.net CPE event manager.
   *
   * @var \Drupal\am_net_cpe\CpeProductManager
   */
  protected $cpeProductManager;

  /**
   * The event timezone.
   *
   * @var string
   */
  protected $eventTimezone = 'America/New_York';

  /**
   * The 'am_net_cpe' logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The product storage.
   *
   * @var \Drupal\commerce\CommerceContentEntityStorage
   */
  protected $productStorage;

  /**
   * The registration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $registrationStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The registrant storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $registrantStorage;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $rngEventManager;

  /**
   * The event session storage.
   *
   * @var \Drupal\vscpa_commerce\EventSessionStorageInterface
   */
  protected $sessionStorage;

  /**
   * The AM.net user profile manager.
   *
   * @var \Drupal\am_net_user_profile\UserProfileManager
   */
  protected $userProfileManager;

  /**
   * Event registrations.
   *
   * @var array
   */
  protected $registrations = NULL;

  /**
   * Constructs a new RegistrationManager.
   *
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The AM.net REST client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The AM.net events logger channel.
   * @param \Drupal\am_net_cpe\CpeProductManagerInterface $cpe_product_manager
   *   The AM.net CPE product manager.
   * @param \Drupal\rng\EventManagerInterface $rng_event_manager
   *   The RNG event manager.
   * @param \Drupal\am_net_user_profile\UserProfileManager $user_profile_manager
   *   The AM.net user profile manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(AssociationManagementClient $client, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, CpeProductManagerInterface $cpe_product_manager, RngEventManagerInterface $rng_event_manager, UserProfileManager $user_profile_manager) {
    $this->client = $client;
    $this->cpeProductManager = $cpe_product_manager;
    $this->logger = $logger;
    $this->productStorage = $entity_type_manager->getStorage('commerce_product');
    $this->registrantStorage = $entity_type_manager->getStorage('registrant');
    $this->registrationStorage = $entity_type_manager->getStorage('registration');
    $this->rngEventManager = $rng_event_manager;
    $this->sessionStorage = $entity_type_manager->getStorage('event_session');
    $this->userProfileManager = $user_profile_manager;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function getEventRegistrations($event_year = NULL, $event_code = NULL) {
    if (empty($event_year) || empty($event_code)) {
      return [];
    }
    // 0. Define delta.
    $event_year = trim($event_year);
    $event_code = trim($event_code);
    $state_key = "am.net.event.registrations.{$event_year}.{$event_code}";
    $state_value = $this->registrations[$state_key] ?? NULL;
    // 1. Try to get data cached locally.
    if (!is_null($state_value)) {
      return $this->registrations[$state_key];
    }
    // 2. Try to get it from the state(If exits).
    $state = \Drupal::state();
    $registrations = $state->get($state_key, FALSE);
    if (!empty($registrations)) {
      $this->registrations[$state_key] = $registrations;
      return $this->registrations[$state_key];
    }
    // 3. Try to fetch event registration from AM.net.
    $items = $this->getAmNetEventRegistrations($member = NULL, $event_year, $event_code, $since_date = NULL);
    if (empty($items) || !is_array($items)) {
      $registrations = [];
    }
    else {
      $registrations = [];
      // Pre-process the listing.
      foreach ($items as $key => $item) {
        $event_year = $item['EventYear'] ?? NULL;
        $event_code = $item['EventCode'] ?? NULL;
        $member_id = $item['NamesId'] ?? NULL;
        if (!empty($event_year) && !empty($event_code) && !empty($member_id)) {
          $member_id = trim($member_id);
          $registrations[$member_id] = $item;
        }
      }
    }
    // Update locally values.
    $this->registrations[$state_key] = $registrations;
    $state->set($state_key, $registrations);
    // Return Result.
    return $this->registrations[$state_key];
  }

  /**
   * {@inheritdoc}
   */
  public function getUserProductPurchases($amnet_id = NULL) {
    if (empty($amnet_id)) {
      return [];
    }
    $endpoint = "person/{$amnet_id}/productsales";
    $items = $this->client->get($endpoint, [])->getResult();
    if (empty($items) || !is_array($items)) {
      return [];
    }
    $purchases = [];
    // Pre-process the listing.
    foreach ($items as $key => $item) {
      $transactions = $item['Items'] ?? [];
      foreach ($transactions as $delta => $transaction) {
        $product_code = $transaction['ProductCode'] ?? NULL;
        if (!empty($product_code)) {
          $purchases[$product_code] = TRUE;
        }
      }
    }
    // Return Result.
    return $purchases;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetEventRegistrations($member = NULL, $event_year = NULL, $event_code = NULL, $since_date = NULL) {
    $endpoint = '';
    $params = [];
    if ($member && $event_year && $event_code) {
      $endpoint = "/EventRegistration";
      $params['id'] = $member;
      $params['code'] = $event_code;
      $params['yr'] = $event_year;
    }
    elseif ($member) {
      $endpoint = "/Person/${member}/registrations";
    }
    elseif ($event_year && $event_code) {
      $endpoint = "/Event/{$event_year}{$event_code}/registrations";
    }
    if ($since_date) {
      $params['starteventdate'] = $since_date;
    }
    return $this->client->get($endpoint, $params)->getResult();
  }

  /**
   * {@inheritdoc}
   */
  public function syncAmNetCpeEventRegistration(array $record) {
    $event_year = trim($record['EventYear']);
    $event_code = trim($record['EventCode']);
    $amnet_name = trim($record['NamesId']);

    if (!$event = $this->cpeProductManager->getDrupalCpeEventProduct($event_code, $event_year)) {
      throw new DrupalRecordNotFoundException('Could not find Drupal event product for AM.net event registration by code: ' . $event_code . ', year: ' . $event_year . '.');
    }
    if (!$user = $this->getDrupalUserByAmNetId($amnet_name)) {
      throw new DrupalRecordNotFoundException('Could not find Drupal user entity for AM.net event registration by AM.net name id :' . $amnet_name . '.');
    }

    $registration = $this->syncDrupalRegistration($event, $user, $record['Added']);
    $this->syncAmNetCpeEventSessionRegistrations($event, $user, $record);

    return $registration;
  }

  /**
   * Syncs event session registrations from AM.net to Drupal.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $event
   *   A CPE (usually product) entity.
   * @param \Drupal\user\UserInterface $user
   *   A Drupal user entity.
   * @param array $event_registration
   *   An AM.net event registration record.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function syncAmNetCpeEventSessionRegistrations(ContentEntityInterface $event, UserInterface $user, array $event_registration) {
    foreach ($event_registration['Sessions'] as $session_registration) {
      $this->syncDrupalCpeEventSessionRegistration($event, $user, $session_registration);
    }
  }

  /**
   * Syncs an AM.net event session registration with Drupal.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $cpe_parent
   *   The session's parent CPE (event) content entity.
   * @param \Drupal\user\UserInterface $user
   *   A Drupal user entity.
   * @param array $session_registration
   *   An AM.net Session registration record.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function syncDrupalCpeEventSessionRegistration(ContentEntityInterface $cpe_parent, UserInterface $user, array $session_registration) {
    if ($event_session = current($this->sessionStorage->loadByProperties([
      'field_session_cpe_parent' => $cpe_parent->id(),
      'field_session_code' => trim($session_registration['SessionCode']),
    ]))) {
      $this->syncDrupalRegistration($event_session, $user, $session_registration['Updated']);
    }
    else {
      $this->logger->warning('Could not find event session code {code} with parent {parent}.', [
        'code' => trim($session_registration['SessionCode']),
        'parent' => $cpe_parent->id(),
      ]);
    }
  }

  /**
   * Creates or updates a registration for a registerable event and user.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $event
   *   A registerable (RNG event type) content entity.
   * @param \Drupal\user\UserInterface $user
   *   A Drupal user entity.
   * @param string $updated
   *   A date string indicating when the registration was updated on AM.net.
   *
   * @return \Drupal\rng\RegistrationInterface
   *   An RNG Registration entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function syncDrupalRegistration(ContentEntityInterface $event, UserInterface $user, $updated = 'now') {
    /** @var \Drupal\rng\RegistrationInterface $registration */
    if (!$registration = $this->getDrupalRegistration($event, $user)) {
      $type = $event->get('rng_registration_type')->target_id;
      $registration = $this->registrationStorage->create([
        'type' => $type,
      ]);
    }
    $updated_time = new DrupalDateTime($updated, $this->eventTimezone);
    if (empty($registration->getRegistrants())) {
      $registration->addIdentity($user);
    }
    $registration
      ->setEvent($event)
      ->setChangedTime($updated_time->getTimestamp())
      ->save();

    return $registration;
  }

  /**
   * Gets a Registration entity for the given CPE event and user.
   *
   * NOTE: This does not get registrations for AM.net product purchases.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $event
   *   A CPE content entity.
   * @param \Drupal\user\UserInterface $user
   *   A Drupal user entity.
   *
   * @return \Drupal\rng\RegistrationInterface|null
   *   A Registration entity, or NULL if not found.
   */
  public function getDrupalRegistration(ContentEntityInterface $event, UserInterface $user) {
    $registrants = $this->registrantStorage->getQuery('AND')
      ->condition('registration.entity.event__target_type', $event->getEntityTypeId(), '=')
      ->condition('registration.entity.event__target_id', $event->id(), '=')
      ->condition('identity__target_type', $user->getEntityTypeId(), '=')
      ->condition('identity__target_id', $user->id(), '=')
      ->execute();

    /** @var \Drupal\rng\RegistrantInterface $registrant */
    if (!empty($registrants) && $registrant = $this->registrantStorage->load(current($registrants))) {
      return $registrant->getRegistration();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalUserByAmNetId($am_net_id, $try_sync = TRUE) {
    $users = $users = $this->userStorage->loadByProperties([
      'field_amnet_id' => $am_net_id,
    ]);
    if (!$users && $try_sync) {
      // Try to get names that don't exist and try again (only once).
      $this->userProfileManager->syncUserProfile($am_net_id);

      return $this->getDrupalUserByAmNetId($am_net_id, FALSE);
    }

    return $users ? current($users) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function syncAmNetCpeSelfStudyRegistration(array $record) {
    // TODO: Implement syncAmNetCpeSelfStudyRegistration() method.
  }

}
