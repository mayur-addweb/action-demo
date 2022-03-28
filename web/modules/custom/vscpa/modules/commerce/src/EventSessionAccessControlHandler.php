<?php

namespace Drupal\vscpa_commerce;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Event session entity.
 *
 * @see \Drupal\vscpa_commerce\Entity\EventSession.
 */
class EventSessionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\vscpa_commerce\Entity\EventSessionInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished event session entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published event session entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit event session entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete event session entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add event session entities');
  }

}
