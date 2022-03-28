<?php

namespace Drupal\am_net_triggers\Plugin\QueueWorker;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes AM.net Remove accounts with empty emails.
 *
 * @QueueWorker(
 *   id = "am_net_remove_accounts",
 *   title = @Translation("AM.net queue worker: Remove accounts with empty emails"),
 *   cron = {"time" = 60}
 * )
 */
class AmNetRemoveAccountsWithEmptyEmails extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The 'am_net_triggers' logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * EventSyncQueueWorker constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The 'am_net_triggers' logger channel.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.am_net_triggers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($name_id = NULL) {
    if (empty($name_id)) {
      return NULL;
    }
    $this->logger->info('Processing AM.net ID {id}', [
      'id' => $name_id,
    ]);
    // Check if the user exits locally.
    $user = am_net_user_profile_get_user_by_amnet_id($name_id);
    if (!$user) {
      // Try one more time but cleaning the ID.
      $name_id = trim($name_id);
      $user = am_net_user_profile_get_user_by_amnet_id($name_id);
    }
    // Check user.
    if (!$user) {
      return NULL;
    }
    $uid = $user->id();
    // Check if the user_has_order_associated.
    $user_has_order_associated = am_net_user_profile_user_has_order_associated($uid);
    if ($user_has_order_associated) {
      $this->logger->info('User has order associated {id}', [
        'id' => $name_id,
      ]);
      // Add to the list and stop here.
      $state = \Drupal::state();
      $key = 'to_remove_with_orders_associated';
      $val = $state->get($key);
      $val[] = [
        $name_id,
        $uid,
        $user->getEmail(),
      ];
      $state->set($key, $val);
      return TRUE;
    }
    else {
      $this->logger->info('User has no order associated {id}', [
        'id' => $name_id,
      ]);
      // 1. Delete user locally.
      $user->delete();
      // 2. Ensure that old account on Gluu is removed.
      $name_id = trim($name_id);
      /** @var \Drupal\vscpa_sso\GluuClient $gluuClient */
      $gluuClient = \Drupal::service('gluu.client');
      $gluu_account = $gluuClient->getExternalId($name_id);
      if ($gluu_account) {
        // Remove the old Gluu Account.
        $gluuClient->deleteUser($gluu_account->id);
      }
      // Log the item.
      $state = \Drupal::state();
      $key = 'deleted_account_without_emails';
      $val = $state->get($key);
      $val[] = [
        $name_id,
        $uid,
        $user->getEmail(),
      ];
      $state->set($key, $val);
      return TRUE;
    }
  }

}
