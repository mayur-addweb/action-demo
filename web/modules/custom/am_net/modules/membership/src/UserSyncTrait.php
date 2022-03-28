<?php

namespace Drupal\am_net_membership;

use Drupal\user\UserInterface;

/**
 * The User Sync Helper trait implementation.
 */
trait UserSyncTrait {

  /**
   * Check if is user available for sync.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return bool
   *   TRUE if is user available for sync, otherwise FALSE.
   */
  public function isUserAvailableForSync(UserInterface $user) {
    $uid = $user->id();
    if ($uid == 0) {
      // Anonymous user.
      return FALSE;
    }
    // Only a sync per request is allowed.
    if (am_net_entity_is_synced('user', $user->id())) {
      return FALSE;
    }
    $state = \Drupal::state();
    // Check By uid.
    $key = "user.{$uid}.locked";
    $locked = $state->get($key, FALSE);
    if ($locked) {
      return FALSE;
    }
    // Check by AM.net ID.
    $amnet_id = $user->get('field_amnet_id')->getString();
    $amnet_id = trim($amnet_id);
    if (!empty($amnet_id)) {
      $key = "user.{$amnet_id}.locked";
      $locked = $state->get($key, FALSE);
      if ($locked) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Lock user sync.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   */
  public function lockUserSync(UserInterface $user) {
    $uid = $user->id();
    if ($uid == 0) {
      // Anonymous user.
      return FALSE;
    }
    $key = "user.{$uid}.locked";
    \Drupal::state()->set($key, TRUE);
  }

  /**
   * Lock user sync by AM.net ID.
   *
   * @param string $amnet_id
   *   The AM.net ID related to the user.
   */
  public function lockUserSyncById($amnet_id) {
    if (empty($amnet_id)) {
      return;
    }
    $key = "user.{$amnet_id}.locked";
    \Drupal::state()->set($key, TRUE);
  }

  /**
   * Unlock user sync by AM.net ID.
   *
   * @param string $amnet_id
   *   The AM.net ID related to the user.
   */
  public function unlockUserSyncById($amnet_id) {
    if (empty($amnet_id)) {
      return;
    }
    $key = "user.{$amnet_id}.locked";
    \Drupal::state()->set($key, FALSE);
  }

  /**
   * Unlock user sync.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   */
  public function unlockUserSync(UserInterface $user) {
    $uid = $user->id();
    if ($uid == 0) {
      // Anonymous user.
      return FALSE;
    }
    $key = "user.{$uid}.locked";
    \Drupal::state()->set($key, FALSE);
    // Unlock User By AM.net ID if applies.
    $am_net_id = $user->get('field_amnet_id')->getString();
    $am_net_id = trim($am_net_id);
    $this->unlockUserSyncById($am_net_id);
  }

  /**
   * Check if the membership status info is Available for push.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return bool
   *   TRUE if is user available for sync, otherwise FALSE.
   */
  public function isMembershipStatusInfoAvailableForPush(UserInterface $user) {
    $uid = $user->id();
    if ($uid == 0) {
      // Anonymous user.
      return FALSE;
    }
    $key = "user.{$uid}.push.membership.data";
    return \Drupal::state()->get($key, FALSE);;
  }

  /**
   * Set if the membership status info is Available for push.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param bool $available
   *   The flag that determine whether the membership status
   *   info is Available for push.
   */
  public function setMembershipStatusInfoAvailableForPush(UserInterface $user, $available = FALSE) {
    $uid = $user->id();
    if ($uid == 0) {
      // Anonymous user.
      return FALSE;
    }
    $key = "user.{$uid}.push.membership.data";
    \Drupal::state()->set($key, $available);
  }

}
