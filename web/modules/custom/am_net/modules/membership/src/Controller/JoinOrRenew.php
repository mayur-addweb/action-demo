<?php

namespace Drupal\am_net_membership\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Controller for 'Join or Renew' logic.
 */
class JoinOrRenew extends ControllerBase {

  /**
   * Redirect to the Login or Membership Application/Renewal Forms.
   */
  public function render() {
    $params = [];
    $options = [];
    // Check if is Anonymous user.
    if ($this->currentUser()->isAnonymous()) {
      $url = URL::fromUserInput('/join-vscpa-today', $options)->toString();
    }
    else {
      // Authenticated user.
      $messenger = \Drupal::messenger();
      $uid = $this->currentUser()->id();
      $user = User::load($uid);
      $membership_status_info = \Drupal::service('am_net_membership.checker')->getMembershipStatusInfo($user);
      if ($membership_status_info['is_membership_application']) {
        // Show user-friendly message.
        $messenger->addMessage(t('Please complete form below to Join today.'));
        // Go to Membership Application form.
        $route_name = 'am_net_membership.application';
      }
      elseif ($membership_status_info['is_membership_renewal']) {
        // Show user-friendly message.
        $messenger->addMessage(t('Renew your VSCPA membership by completing the form below.'));
        // Go to the Membership renewal landing.
        $route_name = 'am_net_membership.renewal.page';
        $params = ['user' => $this->currentUser->id()];
      }
      else {
        // User is in good standing with an active license, no further
        // action is required.
        // Show user-friendly message.
        $message = $membership_status_info['membership_status_message'];
        if (!empty($message)) {
          $messenger->addMessage($message);
        }
        // Redirect to the user view page.
        $route_name = 'entity.user.canonical';
        $params = ['user' => $this->currentUser->id()];
      }
      $url = Url::fromRoute($route_name, $params, $options)->toString();
    }
    return new RedirectResponse($url);
  }

}
