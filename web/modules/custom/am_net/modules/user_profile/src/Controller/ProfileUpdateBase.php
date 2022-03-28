<?php

namespace Drupal\am_net_user_profile\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\am_net_user_profile\UserProfileUpdateHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Profile Update Base page controller.
 */
class ProfileUpdateBase extends ControllerBase {

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership checker service.
   *
   * @var \Drupal\am_net_membership\MembershipCheckerInterface
   */
  protected $userProfileUpdateHelper;

  /**
   * Constructs a new Profile Update Base object.
   *
   * @param \Drupal\am_net_user_profile\UserProfileUpdateHelper $user_profile_update_helper
   *   The update profile helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type definition.
   */
  public function __construct(UserProfileUpdateHelper $user_profile_update_helper, EntityTypeManagerInterface $entity_type_manager) {
    $this->userProfileUpdateHelper = $user_profile_update_helper;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_user_profile_update.helper'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Checks access for update profile pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    return $this->userProfileUpdateHelper->access($account);
  }

  /**
   * Checks access for update profile pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function becomeFirmAdminAccess(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    return $this->userProfileUpdateHelper->becomeFirmAdminAccess($account);
  }

  /**
   * Get Edit user form.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The User entity.
   * @param string $form_mode
   *   The form mode used to display the entity form.
   *
   * @return array
   *   The form render array.
   */
  public function getEditUserForm(UserInterface $user = NULL, $form_mode = '') {
    if (!$user) {
      return [];
    }
    $form = $this->entityTypeManager->getFormObject('user', $form_mode)->setEntity($user);
    return \Drupal::formBuilder()->getForm($form, $form_mode);
  }

}
