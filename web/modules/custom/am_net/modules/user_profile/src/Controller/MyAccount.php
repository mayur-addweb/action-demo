<?php

namespace Drupal\am_net_user_profile\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * My Account Base page controller.
 */
class MyAccount extends ControllerBase {

  /**
   * {@inheritdoc}
   *
   * Builds the My Account Actions Message.
   */
  public function render() {
    if ($this->currentUser()->isAuthenticated()) {
      // Redirect to account detail page.
      return $this->redirect('entity.user.canonical', ['user' => $this->currentUser->id()]);
    }
    // Show Login/Register links.
    return [
      '#markup' => "<h2><a href='/MyAccount' rel='bookmark'><span property='schema:name'>My Account</span></a></h2><div class='clearfix'></div><h5>Already have an account? <i><a href='/user/login'>Sign in.</a></i> Don't have an account? <strong><a href='/user/register'>Join Now.</a></strong></h5>",
    ];
  }

}
