<?php

namespace Drupal\am_net_user_profile\Commands;

use Drush\Commands\DrushCommands;
use Drupal\am_net\Commands\Helper;
use Drupal\am_net_user_profile\CollegeManager;

/**
 * Class College.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\am_net_user_profile\Commands
 */
class College extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\am_net_user_profile\CollegeManager
   */
  protected $collegeManager;

  /**
   * ConfigSplitCommands constructor.
   *
   * @param \Drupal\am_net_user_profile\CollegeManager $collegeManager
   *   The CLI service which allows interoperability.
   */
  public function __construct(CollegeManager $collegeManager) {
    $this->collegeManager = $collegeManager;
  }

  /**
   * Fetch location Colleges from AM.net.
   *
   * @command college:fetch
   *
   * @usage drush college:fetch
   *   Fetch location Colleges from AM.net.
   *
   * @aliases ucf
   */
  public function fetch() {
    // Run the fetch.
    $info = $this->collegeManager->fetchColleges();
    // Show Result.
    Helper::printMessages($info);
  }

}
