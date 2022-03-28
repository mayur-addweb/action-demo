<?php

namespace Drupal\am_net_firms;

use Drupal\taxonomy\TermInterface;

/**
 * The Firm Sync Helper trait implementation.
 */
trait FirmSyncTrait {

  /**
   * Check if is term available for sync.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term entity.
   *
   * @return bool
   *   TRUE if is term available for sync, otherwise FALSE.
   */
  public function isFirmAvailableForSync(TermInterface $term) {
    $tid = $term->id();
    if ($tid == 0) {
      // Anonymous term.
      return FALSE;
    }
    // Only a sync per request is allowed.
    if (am_net_entity_is_synced('term', $term->id())) {
      return FALSE;
    }
    $state = \Drupal::state();
    // Check By tid.
    $key = "term.{$tid}.locked";
    $locked = $state->get($key, FALSE);
    if ($locked) {
      return FALSE;
    }
    // Check by AM.net ID.
    $amnet_id = $term->get('field_amnet_id')->getString();
    if (!empty($amnet_id)) {
      $key = "term.{$amnet_id}.locked";
      $locked = $state->get($key, FALSE);
      if ($locked) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Lock term sync.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term entity.
   */
  public function lockFirmSync(TermInterface $term) {
    $tid = $term->id();
    if ($tid == 0) {
      // Anonymous term.
      return FALSE;
    }
    $key = "term.{$tid}.locked";
    \Drupal::state()->set($key, TRUE);
  }

  /**
   * Lock term sync by AM.net ID.
   *
   * @param string $amnet_id
   *   The AM.net ID related to the term.
   */
  public function lockFirmSyncById($amnet_id) {
    if (empty($amnet_id)) {
      return;
    }
    $key = "term.{$amnet_id}.locked";
    \Drupal::state()->set($key, TRUE);
  }

  /**
   * Unlock term sync by AM.net ID.
   *
   * @param string $amnet_id
   *   The AM.net ID related to the term.
   */
  public function unlockFirmSyncById($amnet_id) {
    if (empty($amnet_id)) {
      return;
    }
    $key = "term.{$amnet_id}.locked";
    \Drupal::state()->set($key, FALSE);
  }

  /**
   * Unlock term sync.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term entity.
   */
  public function unlockFirmSync(TermInterface $term) {
    $tid = $term->id();
    if ($tid == 0) {
      // Anonymous term.
      return FALSE;
    }
    $key = "term.{$tid}.locked";
    \Drupal::state()->set($key, FALSE);
  }

}
