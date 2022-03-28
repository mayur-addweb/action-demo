<?php

namespace Drupal\vscpa_commerce;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\vscpa_commerce\Entity\EventSessionInterface;

/**
 * Defines the storage handler class for Event session entities.
 *
 * This extends the base storage class, adding required special handling for
 * Event session entities.
 *
 * @ingroup vscpa_commerce
 */
interface EventSessionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Event session revision IDs for a specific Event session.
   *
   * @param \Drupal\vscpa_commerce\Entity\EventSessionInterface $entity
   *   The Event session entity.
   *
   * @return int[]
   *   Event session revision IDs (in ascending order).
   */
  public function revisionIds(EventSessionInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Event session author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Event session revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\vscpa_commerce\Entity\EventSessionInterface $entity
   *   The Event session entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(EventSessionInterface $entity);

  /**
   * Unsets the language for all Event session with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
