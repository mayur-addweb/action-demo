<?php

namespace Drupal\rss_list;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the RSS Page entity.
 *
 * @see \Drupal\rss_list\Entity\RssPage.
 */
class RssPageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\rss_list\Entity\RssPageInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished rss page entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published rss page entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit rss page entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete rss page entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add rss page entities');
  }

}
