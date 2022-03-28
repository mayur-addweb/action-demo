<?php

namespace Drupal\vscpa_sso\Plugin\QueueWorker;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\am_net_triggers\QueueItem\NameSyncQueueItem;
use Drupal\vscpa_sso\QueueItem\UserSyncQueueItem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\am_net_user_profile\Entity\PersonInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\am_net_user_profile\Entity\Person;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\vscpa_sso\GluuClient;
use Drupal\user\Entity\User;

/**
 * Processes Gluu/AM.net sync operations.
 *
 * @QueueWorker(
 *   id = "vscpa_sso_sync_accounts",
 *   title = @Translation("Queue worker: Sync accounts between AM.net and Gluu"),
 *   cron = {"time" = 60}
 * )
 */
class AmNetGluuSyncAccountsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The 'am_net_triggers' logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The the Gluu client.
   *
   * @var \Drupal\vscpa_sso\GluuClient
   */
  protected $gluuClient;

  /**
   * AmNetGluuSyncAccountsQueueWorker constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\vscpa_sso\GluuClient $gluu_client
   *   The Gluu Client instance.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The 'am_net_triggers' logger channel.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, GluuClient $gluu_client, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->gluuClient = $gluu_client;
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
      $container->get('gluu.client'),
      $container->get('logger.channel.vscpa_sso')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    switch (get_class($data)) {
      case NameSyncQueueItem::class:
        $this->processName($data);
        break;

      case UserSyncQueueItem::class:
        $this->processUser($data);
        break;
    }
  }

  /**
   * Processes a User sync queue item.
   *
   * @param \Drupal\vscpa_sso\QueueItem\UserSyncQueueItem $item
   *   A User sync queue item.
   */
  protected function processUser(UserSyncQueueItem $item = NULL) {
    if (!$item) {
      return;
    }
    $uid = $item->id;
    if (empty($uid)) {
      return;
    }
    $user = User::load($uid);
    if (!$user) {
      return;
    }
    $email = $user->getEmail();
    $name_id = $user->get('field_amnet_id')->getString();
    if (empty($email) || empty($name_id)) {
      return;
    }
    // Format fields.
    $email = trim($email);
    $email = strtolower($email);
    $name_id = trim($name_id);
    $gluu_account_email = NULL;
    try {
      // Get Gluu Account By external ID.
      $add_new_account = FALSE;
      $remove_account = FALSE;
      $gluu_account = $this->gluuClient->getExternalId($name_id);
      if ($gluu_account == FALSE) {
        // There are none account related to this NameId.
        $add_new_account = TRUE;
      }
      else {
        // The account exists, validate the email associated with the account.
        $emails = $gluu_account->emails ?? [];
        /* @var \Drupal\vscpa_sso\Entity\Email $email_item */
        $email_item = !empty($emails) ? current($emails) : NULL;
        $gluu_account_email = !empty($email_item) ? $email_item->getValue() : NULL;
        if (empty($gluu_account_email)) {
          $remove_account = TRUE;
          $add_new_account = TRUE;
        }
        $gluu_account_email = trim($gluu_account_email);
        $gluu_account_email = strtolower($gluu_account_email);
        if ($gluu_account_email != $email) {
          $remove_account = TRUE;
          $add_new_account = TRUE;
        }
      }
      $log_message = [
        "-> Procession User: {$uid} Name Id: {$name_id} Email: {$email}.",
      ];
      // Remove Account.
      if ($remove_account) {
        $gluu_uid = $gluu_account->id;
        $result = $this->gluuClient->deleteUser($gluu_uid);
        $log_message[] = "--> Removing Account Gluu Email: {$gluu_account_email} gluu Id: {$gluu_uid}.";
      }
      // Create New Gluu Account from User record.
      if ($add_new_account) {
        $pass = user_password();
        $first_name = $user->get('field_givenname')->getString();
        $last_name = $user->get('field_familyname')->getString();
        $data = [
          'mail' => $email,
          'pass' => $pass,
          'username' => $name_id,
          'nickname' => $first_name,
          'familyname' => $first_name,
          'givenname' => $last_name,
          'external_id' => $name_id,
        ];
        $log_message[] = "--> Adding Account Email: {$email} Pass: {$pass}.";
        $result = $this->gluuClient->createUserFromPersonData($data);
      }
      $has_changes = (count($log_message) > 1);
      if (!$has_changes) {
        $log_message[] = "--> Nothing to do.";
      }
      else {
        // Save the ID on the state.
        $state = \Drupal::state();
        $key = "gluu.user.profile.clean.up";
        $values = $state->get($key, []);
        foreach ($log_message as $delta => $message) {
          $values[] = $message;
        }
        $state->set($key, $values);
      }
      // Show log messages.
      foreach ($log_message as $key => $message) {
        $this->logger->info($message);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Processes a Name sync queue item.
   *
   * @param \Drupal\am_net_triggers\QueueItem\NameSyncQueueItem $item
   *   A Name sync queue item.
   */
  protected function processName(NameSyncQueueItem $item) {
    try {
      $name_id = $item->id;
      $amnet_person = Person::load($name_id);
      if (($amnet_person == FALSE) || !($amnet_person instanceof PersonInterface)) {
        // None Person was found with that AM.net ID.
        return;
      }
      $email = $amnet_person->getEmail();
      if (empty($email)) {
        return;
      }
      $gluu_account = $this->gluuClient->getByMail($email);
      if ($gluu_account == FALSE) {
        $pass = user_password();
        $standardized_email = strtolower($email);
        if (strpos($standardized_email, 'vscpa.com') !== FALSE) {
          $pass = 'Testing123!';
        }
        // Create New Gluu Account from Am.net person record.
        $data = [
          'mail' => $email,
          'pass' => $pass,
          'username' => $name_id,
          'nickname' => $amnet_person->getFirstName(),
          'familyname' => $amnet_person->getFirstName(),
          'givenname' => $amnet_person->getLastName(),
          'external_id' => $name_id,
        ];
        $result = $this->gluuClient->createUserFromPersonData($data);
        $updated = FALSE;
      }
      else {
        // Update Gluu Account from Am.net person record.
        $gluu_account->externalId = $name_id;
        $gluu_account->profileUrl = $name_id;
        $gluu_account->userName = $name_id;
        $gluu_account->active = TRUE;
        $gluu_uid = $gluu_account->id;
        $result = $this->gluuClient->updateUser($gluu_uid, $gluu_account);
        $updated = TRUE;
      }
      $updated = ($updated) ? "updated" : "added";
      if (!$result) {
        // Log a error.
        $message = "$email - $name_id : The account could not be $updated in Gluu";
        $this->logger->error($message);
      }
      else {
        $message = "$email - $name_id : The account was $updated in Gluu";
        drupal_set_message($message);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

}
