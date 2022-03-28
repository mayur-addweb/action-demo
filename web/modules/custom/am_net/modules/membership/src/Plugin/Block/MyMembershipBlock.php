<?php

namespace Drupal\am_net_membership\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\UserInterface;
use Drupal\Core\Url;

/**
 * Provides the 'My Membership' block.
 *
 * @Block(
 *   id = "my_membership_block",
 *   admin_label = @Translation("My Membership")
 * )
 */
class MyMembershipBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\am_net_membership\MembershipCheckerInterface $membership_checker */
    $membership_checker = \Drupal::service('am_net_membership.checker');
    $elements = [];
    if ($membership_checker->userIsAuthenticated()) {
      $user = $membership_checker->getCurrentUser();
      $elements = [
        '#theme' => 'my_membership',
        '#user_picture' => $this->loadUserPicture($user),
        '#user_full_name' => $membership_checker->getUserFullName($user),
        '#member_since' => $this->getMemberSinceDate($user),
        '#member_status' => $membership_checker->getMembershipLicenseStatus($user),
        '#account_dashboard_url' => Url::fromRoute('entity.user.canonical', ['user' => $user->id()])->toString(),
        '#my_cpe_url' => Url::fromUserInput('/MyCPE')->toString(),
        '#edit_account_url' => Url::fromRoute('entity.user.edit_form', ['user' => $user->id()])->toString(),
        '#uid' => $user->id(),
        '#cache' => [
          'contexts' => [
            // The "current user" is used above, which depends on the request,
            // so we tell Drupal to vary by the 'user' cache context.
            'user',
          ],
        ],
      ];
      $renderer = \Drupal::service('renderer');
      // Merges the cache contexts, cache tags and max-age of the user entity
      // that the render array depend on.
      $renderer->addCacheableDependency($elements, $user);
    }

    return $elements;
  }

  /**
   * Get the Join date of the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return string|null
   *   The user join date.
   */
  public function getMemberSinceDate(UserInterface $user = NULL) {
    if (!$user) {
      return NULL;
    }
    $field_name = 'field_join_date';
    $field_value = $user->get($field_name)->getString();
    if (!empty($field_value)) {
      $field_value = date('F j, Y', strtotime($field_value));
      return $field_value;
    }
    // Return the Join date that have this user on Drupal.
    return date('F j, Y', $user->getCreatedTime());
  }

  /**
   * Load User Picture from user entity.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return string|null
   *   The user profile uri.
   */
  public function loadUserPicture(UserInterface $user) {
    $image_uri = NULL;
    if (isset($user->field_user_image) && !empty($user->field_user_image) && $user->field_user_image->isEmpty() === FALSE) {
      // Load field_user_image.
      /** @var \Drupal\file\FileInterface $file */
      $file = $user->field_user_image->entity;
      if ($file) {
        $image_uri = $file->getFileUri();
      }
    }
    else {
      $field = $user->get('field_user_image');
      if ($field) {
        $default_image = $field->getFieldDefinition()->getFieldStorageDefinition()->getSetting('default_image');
        if ($default_image && $default_image['uuid']) {
          $entity_repository = \Drupal::service('entity.repository');
          /** @var \Drupal\file\FileInterface $file */
          $file = $entity_repository->loadEntityByUuid('file', $default_image['uuid']);
          if ($file) {
            $image_uri = $file->getFileUri();
          }
        }
      }
    }
    return $image_uri;
  }

}
