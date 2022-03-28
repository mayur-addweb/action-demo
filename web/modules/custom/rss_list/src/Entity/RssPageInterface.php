<?php

namespace Drupal\rss_list\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining RSS Page entities.
 *
 * @ingroup rss_list
 */
interface RssPageInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the RSS Page name.
   *
   * @return string
   *   Name of the RSS Page.
   */
  public function getName();

  /**
   * Sets the RSS Page name.
   *
   * @param string $name
   *   The RSS Page name.
   *
   * @return \Drupal\rss_list\Entity\RssPageInterface
   *   The called RSS Page entity.
   */
  public function setName($name);

  /**
   * Gets the RSS Page creation timestamp.
   *
   * @return int
   *   Creation timestamp of the RSS Page.
   */
  public function getCreatedTime();

  /**
   * Sets the RSS Page creation timestamp.
   *
   * @param int $timestamp
   *   The RSS Page creation timestamp.
   *
   * @return \Drupal\rss_list\Entity\RssPageInterface
   *   The called RSS Page entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the RSS Page published status indicator.
   *
   * Unpublished RSS Page are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the RSS Page is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a RSS Page.
   *
   * @param bool $published
   *   TRUE to set this RSS Page to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\rss_list\Entity\RssPageInterface
   *   The called RSS Page entity.
   */
  public function setPublished($published);

  /**
   * Gets the RSS Feed path.
   *
   * @return string
   *   The RSS Feed path.
   */
  public function getFeedPath();

  /**
   * Gets the RSS Feed length.
   *
   * @return string
   *   The RSS Feed length.
   */
  public function getRssLength();

  /**
   * Gets the RSS Feed Channel Description.
   *
   * @return string
   *   The RSS Feed Channel Description.
   */
  public function getFeedChannelDescription();

  /**
   * Check if the RSS Feed is enabled.
   *
   * @return bool
   *   Check if the RSS Feed is enabled.
   */
  public function isFeedEnable();

}
