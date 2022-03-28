<?php

namespace Drupal\content_paywall;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Implementation of the Content Paywall Helper class.
 */
class ContentPaywallHelper {

  /**
   * Control access field name.
   */
  const CONTROL_ACCESS_FIELD_NAME = 'field_memberonly';

  /**
   * Role ID member.
   */
  const ROLE_ID_MEMBER = 'member';

  /**
   * Role ID firm_administrator.
   */
  const ROLE_ID_FIRM_ADMINISTRATOR = 'firm_administrator';

  /**
   * Member-only content: Firm Admin Access TID.
   */
  const FIRM_ADMIN_ACCESS = '15275';

  /**
   * Member-only content: Member Access TID.
   */
  const MEMBER_ACCESS = '15274';

  /**
   * Member-only content: Non-Member Access TID.
   */
  const NON_MEMBER_ACCESS = '15273';

  /**
   * Member-only content: Public Access TID.
   */
  const PUBLIC_ACCESS = '15272';

  /**
   * Restricted content view mode name.
   */
  const RESTRICTED_CONTENT_VIEW_MODE = 'restricted_content';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new DonationManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Restrict Content Access.
   *
   * Check and Change the view mode Checks access based on Current user Roles.
   *
   * @param string $view_mode
   *   The view_mode that is to be used to display the entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is being viewed.
   * @param array $context
   *   Array with additional context information, currently only contains the
   *   langcode the entity is viewed in.
   *
   * @return string
   *   The view_mode that is to be used to display the entity.
   */
  public function restrictContentAccess($view_mode, EntityInterface $entity, array $context = []) {
    if ($this->accessCheck($entity, $view_mode, $context)) {
      return $view_mode;
    }
    else {
      // User does not has access to the content return the content
      // restricted view mode.
      return self::RESTRICTED_CONTENT_VIEW_MODE . '_' . $view_mode;
    }
  }

  /**
   * Checks entity access based on current user roles.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is being viewed.
   * @param string $view_mode
   *   The view_mode that is to be used to display the entity.
   * @param array $context
   *   Array with additional context information, currently only contains the
   *   lang-code the entity is viewed in.
   *
   * @return string
   *   The view_mode that is to be used to display the entity.
   */
  public function accessCheck(EntityInterface $entity, $view_mode = '', array $context = []) {
    // Check exclude view modes.
    if (!empty($view_mode) && in_array($view_mode, ['cover'])) {
      return TRUE;
    }
    // Check if the entity applies for access check.
    if (!$this->applyForAccessCheck($entity)) {
      return TRUE;
    }
    // Get Content Allowed Roles.
    $allowed_roles = $this->getEntityAccessRoles($entity);
    // Check if the content is grant for the Public Access.
    if ($allowed_roles === TRUE) {
      return TRUE;
    }
    // Include VSCPA Admin role.
    $allowed_roles[] = 'vscpa_administrator';
    $allowed_roles[] = 'content_manager';
    // Check if the user has any of the Roles requested by the entity access.
    $roles = $this->currentUser->getRoles();
    foreach ($roles as $key => $rol) {
      if (in_array($rol, $allowed_roles)) {
        return TRUE;
      }
    }

    // Check Access for non-members.
    if (in_array('non_member', $allowed_roles)) {
      // Check that user is authenticated.
      $is_authenticated = in_array('authenticated', $roles);
      $is_non_member = !in_array('member', $roles);
      if ($is_authenticated && $is_non_member) {
        return TRUE;
      }
    }
    // User does not has access to the content.
    return FALSE;
  }

  /**
   * Check if the entity applies for access check.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is being viewed.
   *
   * @return bool
   *   True if the given entity applies for access check,
   *   otherwise FALSE.
   */
  public function applyForAccessCheck(EntityInterface $entity) {
    // We can't possibly have our field on an entity that
    // does not support fields.
    if (!$entity instanceof FieldableEntityInterface) {
      return FALSE;
    }
    // Check if the entity has the control access field.
    $field_name = self::CONTROL_ACCESS_FIELD_NAME;
    return $entity->hasField($field_name);
  }

  /**
   * Get Entity Access roles.
   *
   * If the entity does not have selected roles, the condition will be
   * evaluated as TRUE for all users.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is being viewed.
   *
   * @return array|bool
   *   List of Roles IDs that have access to the entity,
   *   Otherwise TRUE if the entity is grant for the Public Access.
   */
  public function getEntityAccessRoles(EntityInterface $entity) {
    $field_name = self::CONTROL_ACCESS_FIELD_NAME;
    $tids = [];
    $values = $entity->get($field_name)->getValue();
    if (!empty($values)) {
      $tids = array_column($values, 'target_id');
    }
    // Check there are roles selected for this entity.
    if (empty($tids)) {
      return TRUE;
    }
    // Check if the content is grant for the Public Access.
    if (in_array(self::PUBLIC_ACCESS, $tids)) {
      return TRUE;
    }
    // Check if the user checked all the Roles: That make the
    // entity accessible for everyone.
    $access = [
      self::FIRM_ADMIN_ACCESS,
      self::MEMBER_ACCESS,
      self::NON_MEMBER_ACCESS,
    ];
    if (!array_diff($access, $tids)) {
      return TRUE;
    }
    // Return Access Roles.
    return $this->replaceAccessWithUserRoles($tids);
  }

  /**
   * Replace access with user roles.
   *
   * @param array $tids
   *   The array of content access TIDs.
   *
   * @return array
   *   List of Roles IDs.
   */
  public function replaceAccessWithUserRoles(array $tids = []) {
    if (empty($tids)) {
      return [];
    }
    // Populate the Roles array.
    $roles = [];
    foreach ($tids as $tid) {
      switch ($tid) {
        case self::MEMBER_ACCESS:
          // Role ID member.
          $roles[] = 'member';
          break;

        case self::FIRM_ADMIN_ACCESS:
          // Role ID firm_administrator.
          $roles[] = 'firm_administrator';
          break;

        case self::NON_MEMBER_ACCESS:
          // Non-member access.
          $roles[] = 'non_member';
          break;

      }
    }
    return $roles;
  }

}
