<?php

namespace Drupal\am_net_user_profile\Commands;

use Drupal\am_net_triggers\QueueItem\NameSyncQueueItem;
use Drupal\am_net_user_profile\UserProfileManager;
use Drupal\vscpa_sso\QueueItem\UserSyncQueueItem;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\Console\Helper\Table;
use Drush\Exceptions\UserAbortException;
use Drupal\am_net\Commands\Helper;
use Drush\Commands\DrushCommands;
use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;

/**
 * Class UserProfile.
 *
 * This is the Drush 9 command.
 *
 * @package Drupal\am_net_user_profile\Commands
 */
class UserProfile extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\am_net_user_profile\UserProfileManager
   */
  protected $userProfileManager;

  /**
   * ConfigSplitCommands constructor.
   *
   * @param \Drupal\am_net_user_profile\UserProfileManager $userProfileManager
   *   The CLI service which allows interoperability.
   */
  public function __construct(UserProfileManager $userProfileManager) {
    $this->userProfileManager = $userProfileManager;
  }

  /**
   * Add mismatched user names to the sync queue.
   *
   * @command user-profile:add-mismatched-user-names-to-the-sync-queue
   *
   * @usage drush user-profile:add-mismatched-user-names-to-the-sync-queue
   *   Add mismatched user names to the sync queue.
   *
   * @aliases add_mismatched_user_names_to_the_sync_queue
   */
  public function addMismatchedUserNamesToTheSyncQueue() {
    $state = \Drupal::state();
    $key = "am_net.names.add_sync_queue";
    $users = $state->get($key, []);
    $count = count($users);
    if ($count == 0) {
      $line = 'No Users with mismatched user-names!. Task Completed.';
      $this->output()->writeln($line);
      return FALSE;
    }
    $queue_name = 'am_net_triggers';
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get($queue_name);
    $i = 1;
    foreach ($users as $delta => $user) {
      $uid = $user['uid'] ?? NULL;
      $mail = $user['mail'] ?? NULL;
      $name = $user['name'] ?? NULL;
      $amnet_id = $user['amnet_id'] ?? NULL;
      if (empty($uid) || empty($mail) || empty($name) || empty($amnet_id)) {
        continue;
      }
      $names_id = trim($amnet_id);
      $item = new NameSyncQueueItem($names_id, NULL);
      $queue->createItem($item);
      $message = t('The AM.net Name ID: @id added to queue.', ['@id' => $names_id]);
      $output = "#$i " . ((string) $message);
      $this->output()->writeln($output);
    }
    $this->output()->writeln(dt('Task Completed!.'));
    return TRUE;
  }

  /**
   * Handle match username with the email address.
   *
   * @command user-profile:match-email-usernames
   *
   * @usage drush user-profile:match-email-usernames
   *   Handle match username with the email address.
   *
   * @aliases user_profile_match_email_usernames
   */
  public function matchEmailUserNames() {
    $users = $this->getListingUsersWithMismatchedUserNames();
    $count = count($users);
    if ($count == 0) {
      $line = 'No Users with mismatched user-names!. Task Completed.';
      $this->output()->writeln($line);
      return FALSE;
    }
    $output = dt('Are you sure you want match ' . $count . ' mismatched user-names?');
    if (!$this->io()->confirm($output)) {
      $this->output()->writeln(dt('Task Completed!.'));
      return FALSE;
    }
    $state = \Drupal::state();
    $key = "am_net.names.add_sync_queue";
    $values = $state->get($key, []);
    foreach ($users as $delta => $user) {
      $uid = $user['uid'] ?? NULL;
      $mail = $user['mail'] ?? NULL;
      $name = $user['name'] ?? NULL;
      $amnet_id = $user['amnet_id'] ?? NULL;
      if (empty($uid) || empty($mail) || empty($name) || empty($amnet_id)) {
        continue;
      }
      // Show Table.
      $row = [$uid, $amnet_id, $mail, $name];
      $table = new Table($this->output());
      $table->setHeaders(['Uid', 'Name ID', 'Email', 'Name']);
      $table->setRows([$row]);
      $table->render();
      // Confirm the operation before performing it.
      $output = dt('Are you sure that you perform this change?');
      if (!$this->io()->confirm($output)) {
        $this->output()->writeln(dt('Task Complete!.'));
        continue;
      }
      $connection = Database::getConnection();
      // 1. Update username.
      try {
        $num_updated = $connection->update('users_field_data')
          ->fields(['name' => $mail])
          ->condition('uid', $uid)
          ->execute();
        $values[] = $user;
        $state->set($key, $values);
      }
      catch (\Exception $e) {
        $output = dt('# Error: ' . $e->getMessage() . '.');
        $this->output()->writeln($output);
        continue;
      }
      // 2. Show the result.
      $output = dt('# of row updated: ' . $num_updated . '.');
      $this->output()->writeln($output);
    }
    $this->output()->writeln(dt('Task Completed!.'));
    return TRUE;
  }

  /**
   * Get listing of duplicated names.
   *
   * @return array
   *   The listing of duplicated names.
   */
  public function getListingUsersWithMismatchedUserNames() {
    // Get Duplicated names.
    $connection = Database::getConnection();
    $query = $connection->select('users_field_data', 'us');
    $query->addField('us', 'uid', 'uid');
    $query->addField('us', 'mail', 'mail');
    $query->addField('us', 'name', 'name');
    $query->join('user__field_amnet_id', 'd', 'us.uid = d.entity_id');
    $query->addField('d', 'field_amnet_id_value', 'amnet_id');
    $query->where('us.name != us.mail');
    // Execute the statement.
    $data = $query->execute();
    // Get all the results.
    return $data->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Handle duplicated names.
   *
   * @command user-profile:handle-duplicated-names
   *
   * @usage drush user-profile:handle-duplicated-names
   *   Handle duplicated names record.
   *
   * @aliases user_profile_handle_duplicated_names
   */
  public function handleDuplicatedNames() {
    $duplicated_names = $this->getListingOfDuplicatedNames();
    $count = count($duplicated_names);
    $output = dt('Are you sure you want merge ' . $count . ' duplicated Names records accounts?');
    if (!$this->io()->confirm($output)) {
      $this->output()->writeln(dt('Task Complete!.'));
      return FALSE;
    }
    foreach ($duplicated_names as $delta => $name) {
      $amnet_id = $name['amnet_id'] ?? NULL;
      if (empty($amnet_id)) {
        continue;
      }
      $this->output()->writeln('');
      $line = '+----------------------------- Processing Name ID: ' . $amnet_id . '------------------------------------------+';
      $this->output()->writeln($line);
      $merge = $this->getOrderInformationByNameId($amnet_id, TRUE);
      if (empty($merge)) {
        continue;
      }
      $result = $this->doRemoveDuplicatedNames($merge);
      if ($result) {
        $output = dt('Operation completed!');
        $this->output()->writeln($output);
      }
      else {
        $output = dt('It was not possible to remove the duplicated records, please merge them manually');
        $this->output()->writeln($output);
      }
    }
    $this->output()->writeln(dt('Task Complete!.'));
    return TRUE;
  }

  /**
   * Get listing of duplicated names.
   *
   * @return array
   *   The listing of duplicated names.
   */
  public function getListingOfDuplicatedNames() {
    // Get Duplicated names.
    $connection = Database::getConnection();
    $query = $connection->select('user__field_amnet_id', 'us');
    $query->addField('us', 'field_amnet_id_value', 'amnet_id');
    $query->addExpression('COUNT(us.field_amnet_id_value)', 'count');
    $query->groupBy('amnet_id');
    $query->having('COUNT(us.field_amnet_id_value) > 1');
    // Execute the statement.
    $data = $query->execute();
    // Get all the results.
    return $data->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Get Order information by Name ID.
   *
   * @command user-profile:get-order-information-by-name-id
   *
   * @usage drush user-profile:get-order-information-by-name-id
   *   Get Order Information by Name ID.
   *
   * @aliases user_profile_get_order_information_by_name_id
   */
  public function getOrderInformationByNameId($amnet_id = NULL, $return = FALSE) {
    if (empty($amnet_id)) {
      return NULL;
    }
    // 1. Get drupal accounts associated with the given name_id.
    $connection = Database::getConnection();
    $query = $connection->select('user__field_amnet_id', 'us');
    $query->fields('us', ['entity_id']);
    $query->condition('field_amnet_id_value', $amnet_id);
    // Get all the results.
    $uids = $query->execute()->fetchAllKeyed(0, 0);
    if (empty($uids)) {
      $output = dt('There are no user accounts associated to AM.Net name ID: ' . $amnet_id . '.');
      $this->output()->writeln($output);
      return NULL;
    }
    $result = $this->getOrderInfoByUids($uids);
    $rows = $result['items'];
    $table = new Table($this->output());
    $table->setHeaders(['Uid', 'Name', 'Email', 'Orders']);
    $table->setRows($rows);
    $table->render();

    // Define Selected UID.
    $selected_uid = $result['selected_uid'];
    if (empty($selected_uid)) {
      $output = dt('It was not possible determine the target user for the merge, please merge it manually.');
      $this->output()->writeln($output);
      return NULL;
    }
    $output = dt('Are you sure that you want keep only UID ' . $selected_uid . '?');
    if (!$this->io()->confirm($output)) {
      $this->output()->writeln(dt('Task Complete!.'));
      return NULL;
    }
    // Define Selected Email.
    $emails = $result['emails'];
    $selected_email_index = NULL;
    $error_output = dt('It was not possible determine the target user email for the merge, please merge it manually.');
    try {
      $question = 'Please select the final email that this user should have.';
      $default = current($emails);
      $selected_email_index = $this->io()->choice($question, $emails, $default);
    }
    catch (UserAbortException $e) {
      $this->output()->writeln($error_output);
      return NULL;
    }
    $selected_email = $emails[$selected_email_index] ?? NULL;
    if (empty($selected_email)) {
      $this->output()->writeln($error_output);
      return NULL;
    }
    $output = dt('Selected email was: ' . $selected_email . '.');
    $this->output()->writeln($output);
    if ($return) {
      return [
        'index_items' => $result['index_items'] ?? [],
        'selected_email' => $selected_email,
        'selected_uid' => $selected_uid,
      ];
    }
    else {
      $this->output()->writeln(dt('Task Complete!.'));
      return TRUE;
    }
  }

  /**
   * Get order info by Uids.
   *
   * @param array $uids
   *   The uids.
   *
   * @return array
   *   The users data.
   */
  public function getOrderInfoByUids(array $uids = []) {
    $items = [];
    $index_items = [];
    $emails = [];
    $selected_uid = NULL;
    $i = 0;
    foreach ($uids as $delta => $uid) {
      $user_data = $this->getUserDataByUid($uid);
      $email = $user_data['mail'] ?? NULL;
      $name = $user_data['name'] ?? NULL;
      if (empty($email)) {
        continue;
      }
      $emails[] = $email;
      $row = [
        $user_data['uid'] ?? 0,
        $user_data['name'] ?? 1,
        $email,
      ];
      $index_row = [
        'uid' => $user_data['uid'] ?? NULL,
        'name' => $user_data['name'] ?? NULL,
        'mail' => $email,
      ];
      $result = $this->getOrderInfoByUid($uid);
      $orders = $result['orders'] ?? 0;
      $row['orders'] = $orders;
      $index_row['orders'] = $orders;
      if (is_null($selected_uid)) {
        if (!$this->stringStartsWith($name, '1')) {
          $selected_uid = $uid;
        }
      }
      elseif ($orders > $i) {
        $selected_uid = $uid;
        $i = $orders;
      }
      // Add Items.
      $items[] = $row;
      $index_items[] = $index_row;
    }
    if (!empty($emails)) {
      $emails = array_unique($emails, SORT_REGULAR);
      $emails = array_values($emails);
    }
    return [
      'items' => $items,
      'index_items' => $index_items,
      'emails' => $emails,
      'selected_uid' => $selected_uid,
    ];
  }

  /**
   * Get user data by uid.
   *
   * @param string $uid
   *   The Uid.
   *
   * @return array
   *   The user data by uid.
   */
  public function getUserDataByUid($uid = NULL) {
    // Get Duplicated names.
    $connection = Database::getConnection();
    $query = $connection->select('users_field_data');
    $query->fields('users_field_data', [
      'uid',
      'name',
      'mail',
    ]);
    $query->condition('uid', $uid);
    // Execute the statement.
    $data = $query->execute();
    $users = $data->fetchAll(\PDO::FETCH_ASSOC);
    if (empty($users)) {
      return [];
    }
    return current($users);
  }

  /**
   * Get Order info by Uid.
   *
   * @param string $uid
   *   The Uid.
   *
   * @return array
   *   The Order info.
   */
  public function getOrderInfoByUid($uid = NULL) {
    // Get Duplicated names.
    $connection = Database::getConnection();
    $query = $connection->select('commerce_order');
    $query->addField('commerce_order', 'uid');
    $query->condition('uid', $uid);
    $query->addExpression('COUNT(uid)', 'orders');
    $query->groupBy('uid');
    // Execute the statement.
    $data = $query->execute();
    // Get all the results.
    $items = $data->fetchAll(\PDO::FETCH_ASSOC);
    if (empty($items)) {
      return [];
    }
    return current($items);
  }

  /**
   * Get order info by Uids.
   *
   * @param string $string
   *   The given string.
   * @param string $subString
   *   The given subString.
   * @param bool $caseSensitive
   *   The case sensitive flag.
   *
   * @return bool
   *   TRUE if the string start with the given substring, otherwise FALSE.
   */
  public function stringStartsWith($string, $subString, $caseSensitive = TRUE) {
    if ($caseSensitive === FALSE) {
      $string = mb_strtolower($string);
      $subString = mb_strtolower($subString);

    }
    if (mb_substr($string, 0, mb_strlen($subString)) == $subString) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Do remove duplicated names.
   *
   * @param array $info
   *   The items listing.
   *
   * @return bool
   *   TRUE if the operation was completed, otherwise FALSE.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function doRemoveDuplicatedNames(array $info = []) {
    $selected_uid = $info['selected_uid'] ?? NULL;
    $selected_email = $info['selected_email'] ?? NULL;
    $items = $info['index_items'] ?? [];
    if (empty($selected_uid) || empty($selected_email) || empty($items)) {
      return NULL;
    }
    // 1. Remove duplicated user.
    foreach ($items as $delta => $item) {
      $uid = $item['uid'] ?? NULL;
      if (empty($uid)) {
        continue;
      }
      $user = User::load($uid);
      if (!$user) {
        continue;
      }
      if ($uid != $selected_uid) {
        $output = dt('Removing UID:' . $uid . '.');
        $this->output()->writeln($output);
        // Remove account.
        $user->delete();
      }
      else {
        \Drupal::service('am_net_user_profile.manager')
          ->pullUserProfileChanges($user);
        $output = dt('Pull user profile changes:' . $uid . '.');
        $this->output()->writeln($output);
      }
    }
    return TRUE;
  }

  /**
   * Handle user names with numeral prefixes.
   *
   * @command user-profile:handle-user-names-with-numeral-prefixes
   *
   * @usage drush user-profile:handle-user-names-with-numeral-prefixes
   *   Handle user names with numeral prefixes.
   *
   * @aliases user_profile_handle_user_names_with_numeral_prefixes
   */
  public function handleUserNamesWithNumeralPrefixes() {
    // Get Duplicated names.
    $connection = Database::getConnection();
    $query = $connection->select('users_field_data', 'us');
    $query->fields('us', ['uid', 'name', 'mail']);
    $query->where('us.name != us.mail');
    // Execute the statement.
    $data = $query->execute();
    // Get all the results.
    $items = $data->fetchAll(\PDO::FETCH_ASSOC);
    $count = count($items);
    $output = dt('Are you sure you want fix ' . $count . ' mismatched usernames?');
    if (!$this->io()->confirm($output)) {
      $this->output()->writeln(dt('Task Complete!.'));
      return FALSE;
    }
    $user_profile_manager = \Drupal::service('am_net_user_profile.manager');
    foreach ($items as $delta => $item) {
      $uid = $item['uid'] ?? NULL;
      $name = $item['name'] ?? NULL;
      $mail = $item['mail'] ?? NULL;
      if (empty($uid) || empty($mail) || empty($name)) {
        continue;
      }
      /** @var \Drupal\user\Entity\User $user */
      $user = User::load($uid);
      if (!$user) {
        continue;
      }
      if ($name == $mail) {
        continue;
      }
      $this->output()->writeln('');
      $line = '+----------------------------- Processing Name ID: ' . $uid . '------------------------------------------+';
      $this->output()->writeln($line);
      $line = '+---- Name: ' . $name;
      $this->output()->writeln($line);
      $line = '+---- Email: ' . $mail;
      $this->output()->writeln($line);
      $user->setUsername($mail);
      $user_profile_manager->lockUserSync($user);
      $user->save();
      $user_profile_manager->unlockUserSync($user);
    }
    $this->output()->writeln(dt('Task Complete!.'));
    return TRUE;
  }

  /**
   * Add to the queue a give User Record.
   *
   * @param string $uid
   *   The AMNet Name ID.
   *
   * @command user-profile:queue_user_profile
   *
   * @usage drush user-profile:queue_user_profile 12345
   *   Add to the queue a give User Record.
   *
   * @aliases user_profile_queue_user_profile
   */
  public function queueUserProfile($uid = NULL) {
    if (empty($uid)) {
      $message = t('Please provide a valid User ID.');
      drush_log($message, 'warning');
      return;
    }
    $uid = trim($uid);
    $queue_name = 'vscpa_sso_sync_accounts';
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get($queue_name);
    $item = new UserSyncQueueItem($uid);
    $queue->createItem($item);
    $message = t('The User ID: @id added to queue.', ['@id' => $uid]);
    drush_log($message, 'success');
  }

  /**
   * Add to the queue All the Users Records.
   *
   * @command user-profile:queue_add_all_user_profile
   *
   * @usage drush user-profile:queue_add_all_user_profile
   *   Add to the queue All the Users Records.
   *
   * @aliases queue_add_all_user_profile
   */
  public function queueAddAllUserProfile() {
    // Get all list of user profiles.
    $user_id_list = \Drupal::entityQuery('user')->execute();
    if (empty($user_id_list)) {
      $message = t('No User Records Founds.');
      drush_log($message, 'warning');
      return;
    }
    $queue_name = 'vscpa_sso_sync_accounts';
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get($queue_name);
    // Loop over the user profile list.
    $total = 0;
    foreach ($user_id_list as $key => $uid) {
      $total = $total + 1;
      $uid = trim($uid);
      $element = new UserSyncQueueItem($uid);
      $queue->createItem($element);
      $message = t('The User ID: @id added to queue.', ['@id' => $uid]);
      drush_log($message, 'success');
    }
    $message = t('Total Numbers of User IDs Added: @total.', ['@total' => $total]);
    drush_log($message, 'success');
    $total = $queue->numberOfItems();
    $message = t('Queue number of items: @total.', ['@total' => $total]);
    drush_log($message, 'success');
  }

  /**
   * Add to the queue All Accounts with empty emails.
   *
   * @command user-profile:queue_add_all_accounts_with_empty_emails
   *
   * @usage drush user-profile:queue_add_all_accounts_with_empty_emails
   *   Add all the accounts with empty emails into the queue.
   *
   * @aliases queue_add_all_accounts_with_empty_emails
   */
  public function queueAddAllAccountsWithEmptyEmails() {
    $file = 'public://resources/names.csv';
    $fileHandle = fopen($file, 'r');
    if (!$fileHandle) {
      $message = t('No CSV file Founds.');
      drush_log($message, 'warning');
      return NULL;
    }
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_name = 'am_net_remove_accounts';
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get($queue_name);
    $total = 0;
    while ((($data = fgetcsv($fileHandle)) !== FALSE)) {
      $name_id = $data[0];
      if (!empty($name_id)) {
        $total = $total + 1;
        $queue->createItem($name_id);
        $message = t('The Name ID: @id added to queue.', ['@id' => $name_id]);
        drush_log($message, 'success');
      }
    }
    $message = t('Total Numbers of Names IDs Added: @total.', ['@total' => $total]);
    drush_log($message, 'success');
    $total = $queue->numberOfItems();
    $message = t('Queue number of items: @total.', ['@total' => $total]);
    drush_log($message, 'success');
  }

  /**
   * Add to the queue a give AM.net Person Record.
   *
   * @param string $names_id
   *   The AMNet Name ID.
   *
   * @command user-profile:queue_name
   *
   * @usage drush user-profile:queue_name 12345
   *   Add to the queue a give AM.net Person Record.
   *
   * @aliases user_profile_queue_name
   */
  public function queueName($names_id = NULL) {
    if (empty($names_id)) {
      $message = t('Please provide a valid AM.net Name Record ID.');
      drush_log($message, 'warning');
      return;
    }
    $names_id = trim($names_id);
    $queue_name = 'am_net_triggers';
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get($queue_name);
    $item = new NameSyncQueueItem($names_id, NULL);
    $queue->createItem($item);
    $message = t('The AM.net Name ID: @id added to queue.', ['@id' => $names_id]);
    drush_log($message, 'success');
  }

  /**
   * De duplicating Database Queue.
   *
   * @command de-duplicating-database-queue
   *
   * @usage drush de-duplicating-database-queue
   *   De duplicating Database Queue.
   *
   * @aliases de_duplicating_database_queue
   */
  public function deDuplicatingDatabaseQueue() {
    $items = [];
    $no_name_items = [];
    $queue_name = 'am_net_triggers';
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get($queue_name);
    while ($item = $queue->claimItem()) {
      try {
        $data = $item->data;
        if (get_class($data) == NameSyncQueueItem::class) {
          $names_id = $data->id;
          $names_id = trim($names_id);
          $items[$names_id] = $data;
          $message = t('Adding ID: @id added to queue.', ['@id' => $names_id]);
          drush_log($message, 'success');
        }
        else {
          $no_name_items[] = $data;
        }
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
      catch (\Exception $e) {
        // @todo.
      }
    }
    // Add name Items to the queue.
    if (!empty($items)) {
      foreach ($items as $delta => $item) {
        $queue->createItem($item);
        if (isset($item->id)) {
          $message = t('The AM.net Name ID: @id added to queue.', ['@id' => $item->id]);
          drush_log($message, 'success');
        }
        else {
          $message = t('Adding item to queue.');
        }
      }
    }
    // Add no-names Items to the queue.
    if (!empty($no_name_items)) {
      foreach ($no_name_items as $delta => $item) {
        $queue->createItem($item);
        $message = t('Adding item to queue.');
        drush_log($message, 'success');
      }
    }
  }

  /**
   * Add to the queue All the AM.net Persons Records.
   *
   * @command user-profile:queue_add_all_name
   *
   * @usage drush user-profile:queue_add_all_name
   *   Add to the queue All the AM.net Persons Records.
   *
   * @aliases queue_add_all_name
   */
  public function queueAddAllName() {
    $service_id = 'am_net_user_profile.manager';
    $userProfileManager = \Drupal::service($service_id);
    // Get all list of user profiles.
    $user_id_list = $userProfileManager->getAllUserProfiles();
    if (empty($user_id_list)) {
      $message = t('No AM.net Person Records Founds.');
      drush_log($message, 'warning');
      return;
    }
    $queue_name = 'am_net_triggers';
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get($queue_name);
    // Loop over the user profile list.
    $total = 0;
    foreach ($user_id_list as $key => $item) {
      $total = $total + 1;
      $namesId = $item['NamesID'] ?? NULL;
      if (!empty($namesId)) {
        $namesId = trim($namesId);
        $element = new NameSyncQueueItem($namesId, NULL);
        $queue->createItem($element);
        $message = t('The AM.net Name ID: @id added to queue.', ['@id' => $namesId]);
        drush_log($message, 'success');
      }
    }
    $message = t('Total Numbers of Names IDs Added: @total.', ['@total' => $total]);
    drush_log($message, 'success');
    $total = $queue->numberOfItems();
    $message = t('Queue number of items: @total.', ['@total' => $total]);
    drush_log($message, 'success');
  }

  /**
   * Sync a give AM.net Person Record with a Drupal user account.
   *
   * @param string $names_id
   *   The AMNet Name ID or a Valid Name Email.
   *
   * @command user-profile:pull
   *
   * @usage drush user-profile:pull user@email.com
   *   Sync a give AM.net Person Record with a Drupal user account.
   *
   * @aliases upp
   */
  public function pullChanges($names_id = NULL) {
    $type = 'warning';
    if (!empty($names_id)) {
      $names_id = trim($names_id);
      $info = $this->userProfileManager->syncUserProfile($names_id, $changeDate = NULL, $validate = TRUE, $verbose = TRUE, $gluu_validation = TRUE);
      if ($info == FALSE) {
        drush_print(t('@names_id is not a valid Name Record ID or not suitable for Sync, please provide a valid AM.net record ID.', ['@names_id' => $names_id]), 1);
        // Stop Here.
        return;
      }
      $result = isset($info['result']) ? $info['result'] : NULL;
      unset($info['result']);
      Helper::printMessages($info);
      // Show Result.
      switch ($result) {
        case SAVED_NEW:
          $type = 'success';
          $message = t('The User profile @id have successfully Added.', ['@id' => $names_id]);
          break;

        case SAVED_UPDATED:
          $type = 'success';
          $message = t('The User profile @id have successfully Updated.', ['@id' => $names_id]);
          break;

        default:
          $message = t('@id — ID provided is not a valid NamesID or does not exist on AM.net.', ['@id' => $names_id]);

      }
    }
    else {
      $message = t('Please provide a valid AM.net Name Record ID or a valid email.');
    }
    drush_log($message, $type);
  }

  /**
   * Sync a give Drupal user account with a AM.net Person Record.
   *
   * @param string $uid
   *   The User ID or a Valid Name Email.
   *
   * @command user-profile:push
   *
   * @usage drush user-profile:push user@email.com
   *   Sync a give Drupal user account with a AM.net Person Record.
   *
   * @aliases ups
   */
  public function pushChanges($uid = NULL) {
    $type = 'warning';
    if (!empty($uid)) {
      $uid = trim($uid);
      $info = $this->userProfileManager->pushUserProfile($uid, $changeDate = NULL, $verbose = TRUE);
      if ($info) {
        $result = isset($info['result']) ? $info['result'] : NULL;
        unset($info['result']);
        Helper::printMessages($info);
        // Show Result.
        switch ($result) {
          case SAVED_NEW:
            $type = 'success';
            $message = t('The User profile @id have successfully Added.', ['@id' => $uid]);
            break;

          case SAVED_UPDATED:
            $type = 'success';
            $message = t('The User profile @id have successfully Updated.', ['@id' => $uid]);
            break;

          default:
            $type = 'error';
            $message = $result;

        }
      }
      else {
        $message = t('@id — ID/Email provided is not a valid User account.', ['@id' => $uid]);
      }
    }
    else {
      $message = t('Please provide a valid user ID or a valid email.');
    }
    drush_log($message, $type);
  }

  /**
   * Merge two user profiles.
   *
   * @param int $deleted_id
   *   The old AM.net name id.
   * @param int $merge_into_id
   *   The New AM.net name id.
   *
   * @command user-profile:merge
   *
   * @usage drush user-profile:merge 12234 1234
   *   Merge two user profiles.
   *
   * @aliases ups
   */
  public function mergeUserProfiles($deleted_id = NULL, $merge_into_id = NULL) {
    if (empty($deleted_id) || empty($merge_into_id)) {
      drush_log(t('Please provide a valid Old ID and a New ID.'), 'warning');
      return NULL;
    }
    $deleted_id = trim($deleted_id);
    $merge_into_id = trim($merge_into_id);
    $result = $this->userProfileManager->mergeUserProfiles($deleted_id, $merge_into_id);
    if ($result) {
      $params = [
        '@old_id' => $deleted_id,
        '@new_id' => $merge_into_id,
      ];
      drush_log(t('The User profiles have successfully Merged. Old: @old_id -> New: @new_id.', $params), 'success');
    }
    else {
      drush_log(t('The merge operation not was completed.'), 'error');
    }
  }

}
