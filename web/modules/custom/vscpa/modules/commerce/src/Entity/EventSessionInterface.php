<?php

namespace Drupal\vscpa_commerce\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event session entities.
 *
 * @ingroup vscpa_commerce
 */
interface EventSessionInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Event session name.
   *
   * @return string
   *   Name of the Event session.
   */
  public function getName();

  /**
   * Sets the Event session name.
   *
   * @param string $name
   *   The Event session name.
   *
   * @return \Drupal\vscpa_commerce\Entity\EventSessionInterface
   *   The called Event session entity.
   */
  public function setName($name);

  /**
   * Gets the Event session creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event session.
   */
  public function getCreatedTime();

  /**
   * Sets the Event session creation timestamp.
   *
   * @param int $timestamp
   *   The Event session creation timestamp.
   *
   * @return \Drupal\vscpa_commerce\Entity\EventSessionInterface
   *   The called Event session entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Event session published status indicator.
   *
   * Unpublished Event session are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event session is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event session.
   *
   * @param bool $published
   *   TRUE to set this Event session to published, FALSE to set it unpublished.
   *
   * @return \Drupal\vscpa_commerce\Entity\EventSessionInterface
   *   The called Event session entity.
   */
  public function setPublished($published);

  /**
   * Gets the Event session revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Event session revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\vscpa_commerce\Entity\EventSessionInterface
   *   The called Event session entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Event session revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Event session revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\vscpa_commerce\Entity\EventSessionInterface
   *   The called Event session entity.
   */
  public function setRevisionUserId($uid);

}
