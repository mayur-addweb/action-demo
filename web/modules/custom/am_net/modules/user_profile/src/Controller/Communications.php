<?php

namespace Drupal\am_net_user_profile\Controller;

use Drupal\user\UserInterface;

/**
 * Profile Update: General Member Information page controller.
 */
class Communications extends ProfileUpdateBase {

  /**
   * {@inheritdoc}
   *
   * Builds the General Member Information form.
   */
  public function render(UserInterface $user) {
    // Attributes.
    $build = [
      '#id' => 'communication-preferences',
      '#attributes' => ['class' => ['communication-preferences']],
    ];
    // Update Profile Edit - General Member Information form.
    $build['form'] = $this->getEditUserForm($user, 'update_profile_communications');
    return $build;
  }

}
