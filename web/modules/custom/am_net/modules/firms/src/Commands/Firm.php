<?php

namespace Drupal\am_net_firms\Commands;

use Drush\Commands\DrushCommands;
use Drupal\am_net\Commands\Helper;
use Drupal\am_net_firms\FirmManager;

/**
 * Class Firm.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\am_net_firms\Commands
 */
class Firm extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\am_net_firms\FirmManager
   */
  protected $firmManager;

  /**
   * ConfigSplitCommands constructor.
   *
   * @param \Drupal\am_net_firms\FirmManager $firmManager
   *   The CLI service which allows interoperability.
   */
  public function __construct(FirmManager $firmManager) {
    $this->firmManager = $firmManager;
  }

  /**
   * Sync a give Drupal Firm Term with a AM.net Firm Record.
   *
   * @param string $firm_id
   *   The firm term id.
   *
   * @command firm:push
   *
   * @usage drush firm:push 1234
   *   Sync a give Drupal Firm Term with a AM.net Firm Record.
   *
   * @aliases ups
   */
  public function pushChanges($firm_id = NULL) {
    $type = 'warning';
    if (!empty($firm_id)) {
      $firm_id = trim($firm_id);
      $info = $this->firmManager->pushFirmChanges($firm_id, $changeDate = NULL, $verbose = TRUE);
      if (is_array($info) && !empty($info)) {
        $result = isset($info['result']) ? $info['result'] : NULL;
        unset($info['result']);
        Helper::printMessages($info);
        // Show Result.
        switch ($result) {
          case SAVED_NEW:
            $type = 'success';
            $message = t('The Firm @id have successfully Added.', ['@id' => $firm_id]);
            break;

          case SAVED_UPDATED:
            $type = 'success';
            $message = t('The Firm @id have successfully Updated.', ['@id' => $firm_id]);
            break;

          default:
            $type = 'error';
            $message = $result;

        }
      }
      else {
        $message = t('@id — ID provided is not a valid firm term.', ['@id' => $firm_id]);
      }
    }
    else {
      $message = t('Please provide a valid Firm Term ID.');
    }
    drush_log($message, $type);
  }

}
