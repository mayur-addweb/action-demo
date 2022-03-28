<?php

namespace Drupal\am_net_cpe;

/**
 * AM.net CPE Registration Manager.
 */
interface CpeRegistrationManagerInterface {

  /**
   * Gets a list of event registrations stored locally on Drupal.
   *
   * @param int $event_year
   *   An AM.net event year.
   * @param string $event_code
   *   An AM.net event code.
   *
   * @return array
   *   An array of AM.net event registrations.
   */
  public function getEventRegistrations($event_year = NULL, $event_code = NULL);

  /**
   * Gets a list of event registrations.
   *
   * @param int $member
   *   An AM.net member id.
   * @param int $event_year
   *   An AM.net event year.
   * @param string $event_code
   *   An AM.net event code.
   * @param string $since_date
   *   A starting date from which to search for registrations (YYYY-MM-DD).
   *
   * @return array
   *   An array of AM.net event registrations.
   */
  public function getAmNetEventRegistrations($member = NULL, $event_year = NULL, $event_code = NULL, $since_date = NULL);

  /**
   * Syncs an AM.net event registration to a Drupal CPE Event registration.
   *
   * @param array $record
   *   An AM.net event registration record.
   *
   * @return \Drupal\rng\RegistrationInterface
   *   The registration entity.
   */
  public function syncAmNetCpeEventRegistration(array $record);

  /**
   * Syncs an AM.net event registration to a Drupal Self-study CPE registration.
   *
   * @param array $record
   *   An AM.net CPE product purchase record.
   *
   * @return \Drupal\rng\RegistrationInterface
   *   The registration entity.
   */
  public function syncAmNetCpeSelfStudyRegistration(array $record);

  /**
   * Gets a Drupal user by AM.net id.
   *
   * @param int $am_net_id
   *   An AM.net Names ID.
   * @param bool $try_sync
   *   TRUE if the user should attempt to be synced/pulled if not found.
   *
   * @return \Drupal\user\UserInterface|null
   *   A Drupal user entity, or NULL if not found.
   */
  public function getDrupalUserByAmNetId($am_net_id, $try_sync = TRUE);

}
