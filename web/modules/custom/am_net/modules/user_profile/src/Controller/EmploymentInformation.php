<?php

namespace Drupal\am_net_user_profile\Controller;

use Drupal\user\UserInterface;

/**
 * Profile Update: Employment Information page controller.
 */
class EmploymentInformation extends ProfileUpdateBase {

  /**
   * {@inheritdoc}
   *
   * Builds the Employment Information form.
   */
  public function render(UserInterface $user) {
    // Attributes.
    $build = [
      '#id' => 'employment-information-controller',
      '#attributes' => ['class' => ['employment-information-controller']],
    ];
    // Update Profile Edit - EmploymentInformation form.
    $form = $this->getEditUserForm($user, 'update_profile_employment_information');
    $form['group_employment_status']['#description'] = '<div>If you need to update your employment status please email <a href="mailto:membership@vscpa.com" target="_blank" title="" rel="noopener">membership@vscpa.com</a>.</div>';
    $form['field_job_status']['widget']['#attributes']['readonly'] = 'readonly';
    $form['field_job_status']['widget']['#attributes']['disabled'] = 'disabled';
    $build['form'] = $form;
    return $build;
  }

}
