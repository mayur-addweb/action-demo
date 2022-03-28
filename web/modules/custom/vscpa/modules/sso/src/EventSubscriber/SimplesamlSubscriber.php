<?php

namespace Drupal\vscpa_sso\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\simplesamlphp_auth\EventSubscriber\SimplesamlSubscriber as BaseSimplesamlSubscriber;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class SimplesamlSubscriber extends BaseSimplesamlSubscriber {

  /**
   * Logs out user if not SAML authenticated and local logins are disabled.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The subscribed event.
   */
  public function checkAuthStatus(GetResponseEvent $event) {
    if ($this->account->isAnonymous()) {
      return;
    }

    if (!$this->simplesaml->isActivated()) {
      return;
    }

    if ($this->simplesaml->isAuthenticated()) {
      return;
    }

    if ($this->config->get('allow.default_login')) {

      $allowed_uids = explode(',', $this->config->get('allow.default_login_users'));
      if (in_array($this->account->id(), $allowed_uids)) {
        return;
      }

      $allowed_roles = $this->config->get('allow.default_login_roles');
      if (array_intersect($this->account->getRoles(), $allowed_roles)) {
        return;
      }
    }
    if ($this->config->get('debug')) {
      $this->logger->debug('User %name not authorized to log in using local account.', ['%name' => $this->account->getAccountName()]);
    }
    user_logout();
    // Get the path (default: '/saml_login') from the
    // 'simplesamlphp_auth.saml_login' route.
    $saml_login_path = \Drupal::url('simplesamlphp_auth.saml_login');
    $response = new RedirectResponse($saml_login_path, RedirectResponse::HTTP_FOUND);
    $event->setResponse($response);
    $event->stopPropagation();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set event susbcriber with high priority to override
    // BaseSimplesamlSubscriber execution.
    $events[KernelEvents::REQUEST][] = ['checkAuthStatus', 100];
    $events[KernelEvents::REQUEST][] = ['login_directly_with_external_IdP', 101];
    return $events;
  }

}
