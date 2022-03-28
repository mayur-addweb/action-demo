<?php

namespace Drupal\announcement_pop_up;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Announcement entities.
 *
 * @ingroup announcement_pop_up
 */
class AnnouncementListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Announcement ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\announcement_pop_up\Entity\Announcement */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.announcement.edit_form',
      ['announcement' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
