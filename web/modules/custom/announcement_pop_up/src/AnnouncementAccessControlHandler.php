<?php

namespace Drupal\announcement_pop_up;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Announcement entity.
 *
 * @see \Drupal\announcement_pop_up\Entity\Announcement.
 */
class AnnouncementAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\announcement_pop_up\Entity\AnnouncementInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished announcement entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published announcement entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit announcement entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete announcement entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add announcement entities');
  }

}
