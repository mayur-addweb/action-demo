<?php

namespace Drupal\vscpa_utility\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class OneTimeLoginLinkController.
 *
 * @package Drupal\vscpa_utility\Controller
 */
class OneTimeLoginLinkController extends ControllerBase {

  /**
   * Generates a one-time login (password reset) link for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user for which to generate the login link.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect to the destination, if one was provided.
   */
  public function generate(AccountInterface $user) {
    $url = user_pass_reset_url($user);
    $mail = $user->getEmail();
    drupal_set_message($this->t('One-time login link created for :mail:<br/> <code>:login</code>', [
      ':mail' => $mail,
      ':login' => $url,
    ]));
    drupal_set_message($this->t("Remember, if you are an administrator, do NOT change the user's password. (Logging out is OK, and will not affect their session)."), 'warning');

    if ($destination = \Drupal::destination()->get()) {
      return new RedirectResponse($destination);
    }
  }

}
