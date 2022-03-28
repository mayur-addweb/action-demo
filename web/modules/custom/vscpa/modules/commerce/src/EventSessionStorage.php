<?php

namespace Drupal\vscpa_commerce;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class EventSessionStorage extends SqlContentEntityStorage implements EventSessionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EventSessionInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {event_session_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {event_session_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(EventSessionInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {event_session_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('event_session_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
