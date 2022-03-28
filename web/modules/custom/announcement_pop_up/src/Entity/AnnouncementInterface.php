<?php

namespace Drupal\announcement_pop_up\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Announcement entities.
 *
 * @ingroup announcement_pop_up
 */
interface AnnouncementInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Announcement name.
   *
   * @return string
   *   Name of the Announcement.
   */
  public function getName();

  /**
   * Sets the Announcement name.
   *
   * @param string $name
   *   The Announcement name.
   *
   * @return \Drupal\announcement_pop_up\Entity\AnnouncementInterface
   *   The called Announcement entity.
   */
  public function setName($name);

  /**
   * Gets the Announcement creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Announcement.
   */
  public function getCreatedTime();

  /**
   * Sets the Announcement creation timestamp.
   *
   * @param int $timestamp
   *   The Announcement creation timestamp.
   *
   * @return \Drupal\announcement_pop_up\Entity\AnnouncementInterface
   *   The called Announcement entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Announcement published status indicator.
   *
   * Unpublished Announcement are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Announcement is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Announcement.
   *
   * @param bool $published
   *   TRUE to set this Announcement to published, FALSE to set it to
   *   unpublished.
   *
   * @return \Drupal\announcement_pop_up\Entity\AnnouncementInterface
   *   The called Announcement entity.
   */
  public function setPublished($published);

}
