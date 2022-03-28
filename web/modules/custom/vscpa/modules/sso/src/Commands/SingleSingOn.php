<?php

namespace Drupal\vscpa_sso\Commands;

use Drupal\am_net_cpe\CpeRegistrationManagerInterface;
use Drupal\am_net_user_profile\Entity\Person;
use Drupal\am_net_user_profile\Entity\PersonInterface;
use Drupal\vscpa_sso\GluuClient;
use Drush\Commands\DrushCommands;

/**
 * SingleSingOn related commands.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\am_net_cpe\Commands
 */
class SingleSingOn extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\am_net_cpe\CpeRegistrationManagerInterface
   */
  protected $cpeRegistrationManager;

  /**
   * The the Gluu client.
   *
   * @var \Drupal\vscpa_sso\GluuClient
   */
  protected $gluuClient;

  /**
   * Cpe constructor.
   *
   * @param \Drupal\am_net_cpe\CpeRegistrationManagerInterface $cpeRegistrationManager
   *   The CPE registration manager.
   * @param \Drupal\vscpa_sso\GluuClient $gluu_client
   *   The Gluu Client instance.
   */
  public function __construct(CpeRegistrationManagerInterface $cpeRegistrationManager, GluuClient $gluu_client) {
    $this->cpeRegistrationManager = $cpeRegistrationManager;
    $this->gluuClient = $gluu_client;
  }

  /**
   * Event Generate Registrants Report.
   *
   * @command event_generate_registrants_report
   *
   * @usage drush event_generate_registrants_report
   *   Event Generate Registrants Report.
   *
   * @aliases event_generate_registrants_report
   */
  public function eventGenerateRegistrantsReport($code = NULL, $year = NULL, $limit = 10000) {
    if (empty($code) || empty($year)) {
      $this->output()->writeln(dt('Please provide a valid code and year.'));
      return;
    }
    $registrations = $this->cpeRegistrationManager->getAmNetEventRegistrations(NULL, $year, $code, NULL);
    if (empty($registrations)) {
      $message = "Am.net did not return registrants for the given event($code/$year).";
      $this->output()->writeln($message);
      return;
    }
    $count = count($registrations);
    $output = dt('Are you sure you want generate the report for ' . $count . ' registrants?');
    if (!$this->io()->confirm($output)) {
      $this->output()->writeln(dt('Task Completed!.'));
      return;
    }
    $result = [
      'good_accounts' => [],
      'bad_accounts' => [],
    ];
    foreach ($registrations as $key => $item) {
      $name_id = $item['NamesId'] ?? NULL;
      if (empty($name_id)) {
        continue;
      }
      $report = $this->generateAccountReport($name_id);
      $status = $report['status'] ?? NULL;
      if ($status != 'Account-In-Place') {
        $result['bad_accounts'][] = $report;
      }
      else {
        $result['good_accounts'][] = $report;
      }
      $message = "#{$key} Name ID: $name_id - Account Status: $status.";
      $this->output()->writeln($message);
      if ($key > $limit) {
        break;
      }
    }
    $store_key = "event_generate_registrants_report.$year.$code";
    \Drupal::state()->set($store_key, $result);
    $this->output()->writeln(dt('Task Completed!.'));
  }

  /**
   * Generate account report.
   *
   * @param int $name_id
   *   The Name ID of the user.
   *
   * @return array
   *   The Array with the report columns.
   */
  public function generateAccountReport($name_id = NULL) {
    $name_id = trim($name_id);
    $report = [
      'name_id' => $name_id,
      'drupal_uid' => NULL,
      'drupal_username' => NULL,
      'drupal_email' => NULL,
      'amnet_email' => NULL,
      'gluu_email' => NULL,
      'gluu_acounts' => NULL,
      'gluu_ids' => NULL,
      'status' => 'Account-With-Issues',
    ];
    // Load the name.
    $person = Person::load($name_id);
    if (($person == FALSE) || !($person instanceof PersonInterface)) {
      $report['status'] = 'No-Name-record';
      // None Person was found with that AM.net ID.
      return $report;
    }
    $amnet_email = $person->getEmail();
    $report['amnet_email'] = $amnet_email;
    // Added Drupal related info.
    $user_data = $this->getUserDataByNameId($name_id);
    if (empty($user_data)) {
      $raw_name_id = str_pad($name_id, 6, " ", STR_PAD_LEFT);
      $user_data = $this->getUserDataByNameId($raw_name_id);
    }
    $report['drupal_uid'] = $user_data->entity_id ?? NULL;
    $report['drupal_username'] = $user_data->name ?? NULL;
    $drupal_email = $user_data->mail ?? NULL;
    $report['drupal_email'] = $drupal_email;
    // Added Gluu related info.
    $gluu_data = $this->getGluuDataByNameId($name_id, $amnet_email);
    $gluu_emails = $gluu_data['emails'] ?? [];
    $gluu_emails = array_unique($gluu_emails, SORT_REGULAR);
    $gluu_emails_items = implode(',', $gluu_emails);
    $gluu_emails = implode(',', $gluu_emails);
    $report['gluu_email'] = $gluu_emails_items;
    $gluu_ids = $gluu_data['id'] ?? [];
    $gluu_ids = array_unique($gluu_ids, SORT_REGULAR);
    $report['gluu_acounts'] = count($gluu_ids);
    $gluu_ids = implode(',', $gluu_ids);
    $prefix = '@!13A3.08A9.8106.3629!0001!80A5.E1DE!0000!';
    $gluu_ids = str_replace($prefix, '', $gluu_ids);
    $report['gluu_ids'] = $gluu_ids;
    // Set the Status.
    $amnet_email = strtolower($amnet_email);
    $drupal_email = strtolower($drupal_email);
    $gluu_emails = strtolower($gluu_emails);
    if (($amnet_email == $drupal_email) && ($drupal_email == $gluu_emails)) {
      $report['status'] = 'Account-In-Place';
    }
    return $report;
  }

  /**
   * Get Gluu Data by Name ID.
   *
   * @param int $name_id
   *   The Name ID of the user.
   * @param int $amnet_email
   *   The amnet email.
   *
   * @return array
   *   The Array with the report columns.
   */
  public function getGluuDataByNameId($name_id = NULL, $amnet_email = NULL) {
    $result = [
      'equals' => FALSE,
      'total' => 0,
      'id' => FALSE,
      'emails' => FALSE,
      'a' => [],
      'b' => [],
    ];
    $gluu_account_name_id = $this->gluuClient->getExternalId($name_id);
    if (!$gluu_account_name_id) {
      return $result;
    }
    $emails = $gluu_account_name_id->emails ?? [];
    $id = $gluu_account_name_id->id ?? NULL;
    $result['a']['id'] = $id;
    /** @var \Drupal\vscpa_sso\Entity\Email $email */
    $email = current($emails);
    $result['a']['email_address'] = $email->getValue();
    $result['emails'][] = $email->getValue();
    $result['id'][] = $id;
    // Check by email.
    $gluu_account_email = $this->gluuClient->getByMail($amnet_email);
    if (!$gluu_account_email) {
      return $result;
    }
    $emails = $gluu_account_email->emails ?? [];
    $id = $gluu_account_email->id ?? NULL;
    $result['b']['id'] = $id;
    /** @var \Drupal\vscpa_sso\Entity\Email $email */
    $email = current($emails);
    $result['b']['email_address'] = $email->getValue();
    $result['equals'] = ($result['a'] == $result['b']);
    $result['emails'][] = $email->getValue();
    $result['id'][] = $id;
    return $result;
  }

  /**
   * Get User Data by Name ID.
   *
   * @param int $name_id
   *   The Name ID of the user.
   *
   * @return array
   *   The Array with the report columns.
   */
  public function getUserDataByNameId($name_id = NULL) {
    $database = \Drupal::database();
    $query = $database->select('user__field_amnet_id', 't');
    $query->fields('t', ['entity_id']);
    $query->condition('field_amnet_id_value', $name_id);
    $query->leftJoin('users_field_data', 'um', 'um.uid = t.entity_id');
    $query->fields('um', ['name', 'mail']);
    $query->range(0, 1);
    $results = $query->execute()->fetchAllAssoc('entity_id');
    if (empty($results)) {
      return [];
    }
    return current($results);
  }

}
