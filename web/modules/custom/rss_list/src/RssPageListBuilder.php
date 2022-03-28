<?php

namespace Drupal\rss_list;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of RSS Page entities.
 *
 * @ingroup rss_list
 */
class RssPageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['updated'] = $this->t('Updated');
    $header['feed_enabled'] = $this->t('RSS feed Enabled?');
    $header['status'] = $this->t('Status');
    $header['author'] = $this->t('Author');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\rss_list\Entity\RssPage */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.rss_page.canonical',
      ['rss_page' => $entity->id()]
    );
    $row['updated'] = format_date($entity->getCreatedTime());
    $row['feed_enabled'] = $entity->isFeedEnable() ? 'Yes' : 'No';
    $row['status'] = $entity->isPublished() ? 'Published' : 'Unpublished';
    $row['author'] = $entity->getOwner()->getEmail();
    return $row + parent::buildRow($entity);
  }

}
