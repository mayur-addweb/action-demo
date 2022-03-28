<?php

namespace Drupal\am_net_user_profile\Controller;

use Drupal\user\UserInterface;

/**
 * Profile Update: Website Account page controller.
 */
class WebsiteAccount extends ProfileUpdateBase {

  /**
   * {@inheritdoc}
   *
   * Builds the Website Account form.
   */
  public function render(UserInterface $user) {
    // Attributes.
    $build = [
      '#id' => 'website-account-controller',
      '#attributes' => ['class' => ['website-account-controller']],
    ];
    // Title.
    $title = $this->t('Update Login Information');
    $build['title'] = [
      '#markup' => '<div class="page-header"><h4 class="accent-left purple">' . $title . '</h4></div>',
    ];
    // Description.
    $header_description = t('Update your website account to access purchased CPE, including webcasts, electronic materials, other password-protected content or to register for events/products. Questions? Contact VSCPA Member Services at (800) 733-8272.');
    $build['description'] = [
      '#markup' => $header_description,
    ];
    // Update Profile Edit - Website Account form.
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\vscpa_sso\Form\UpdateLoginInformationForm', $user);
    return $build;
  }

}
