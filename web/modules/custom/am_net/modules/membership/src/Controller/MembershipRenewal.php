<?php

namespace Drupal\am_net_membership\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for Membership Renewal.
 */
class MembershipRenewal extends ControllerBase {

  /**
   * Redirect to the Membership Renewal Forms.
   */
  public function render() {
    if ($this->currentUser()->isAnonymous()) {
      // Show user-friendly message.
      drupal_set_message(t('Please login below to renew your membership today.'));
      $route_name = 'user.login';
      $params = [];
      $options = [
        'query' => ['destination' => '/membership/application'],
      ];
    }
    else {
      // Go to the renewal first step.
      $route_name = 'am_net_membership.renewal';
      $params = [
        'user' => $this->currentUser->id(),
      ];
      $options = [];
    }
    return new RedirectResponse(URL::fromRoute($route_name, $params, $options)->toString());
  }

}
