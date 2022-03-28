<?php

namespace Drupal\am_net_user_profile\Controller;

use Drupal\user\UserInterface;

/**
 * Profile Update: General Member Information page controller.
 */
class GeneralMemberInformation extends ProfileUpdateBase {

  /**
   * {@inheritdoc}
   *
   * Builds the General Member Information form.
   */
  public function render(UserInterface $user) {
    // Attributes.
    $build = [
      '#id' => 'general-member-information-controller',
      '#attributes' => ['class' => ['general-member-information-controller']],
    ];
    // Update Profile Edit - General Member Information form.
    $build['form'] = $this->getEditUserForm($user, 'update_profile_general_member_information');
    return $build;
  }

}
