<?php

namespace Drupal\am_net_user_profile\Commands;

use Drush\Commands\DrushCommands;
use Drupal\am_net\Commands\Helper;
use Drupal\am_net_user_profile\LegislativeContactsManager;

/**
 * Class Legislative Contacts.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\am_net_user_profile\Commands
 */
class LegislativeContacts extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\am_net_user_profile\LegislativeContactsManager
   */
  protected $legislativeContactsManager;

  /**
   * ConfigSplitCommands constructor.
   *
   * @param \Drupal\am_net_user_profile\LegislativeContactsManager $legislativeContactsManager
   *   The CLI service which allows interoperability.
   */
  public function __construct(LegislativeContactsManager $legislativeContactsManager) {
    $this->legislativeContactsManager = $legislativeContactsManager;
  }

  /**
   * Sync a give AM.net Person Record with a Drupal Person Content.
   *
   * @param string $names_id
   *   The AMNet Name ID.
   *
   * @command legislative-contacts:pull
   *
   * @usage drush legislative-contacts:pull 12345
   *   Sync a give AM.net Person Record with a Drupal Person Content.
   *
   * @aliases lcp
   */
  public function pullChanges($names_id = NULL) {
    if (empty($names_id)) {
      $message = t('Please provide a valid AM.net Name Record ID or a valid email.');
      drush_log($message, 'warning');
      return;
    }
    $info = $this->legislativeContactsManager->pullContentPerson($names_id, $verbose = TRUE);
    if ($info == FALSE) {
      drush_print(t('@names_id is not a valid Name Record ID, please provide a valid AM.net record ID.', ['@names_id' => $names_id]), 1);
      return;
    }
    if ($info == -1) {
      drush_print(t('The given person is not a Legislator: @names_id.', ['@names_id' => $names_id]), 1);
      return;
    }
    $result = isset($info['result']) ? $info['result'] : NULL;
    unset($info['result']);
    Helper::printMessages($info);
    // Show Result.
    switch ($result) {
      case SAVED_NEW:
        $type = 'success';
        $message = t('The Person Content @id have successfully Added.', ['@id' => $names_id]);
        break;

      case SAVED_UPDATED:
        $type = 'success';
        $message = t('The Person Content @id have successfully Updated.', ['@id' => $names_id]);
        break;

      default:
        $type = 'warning';
        $message = t('@id — ID provided is not a valid NamesID or does not exist on AM.net.', ['@id' => $names_id]);

    }
    drush_log($message, $type);
  }

}
