<?php

namespace Drupal\am_net_user_profile\Controller;

use Drupal\user\UserInterface;

/**
 * Profile Update: Elected Officials page controller.
 */
class ElectedOfficials extends ProfileUpdateBase {

  /**
   * {@inheritdoc}
   *
   * Builds the Elected Officials form.
   */
  public function render(UserInterface $user) {
    // Attributes.
    $build = [
      '#id' => 'elected-officials-controller',
      '#attributes' => ['class' => ['elected-officials-controller']],
    ];
    // Update Profile Edit - Elected Officials form.
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\am_net_user_profile\Form\ElectedOfficialsForm', $user);
    return $build;
  }

}
