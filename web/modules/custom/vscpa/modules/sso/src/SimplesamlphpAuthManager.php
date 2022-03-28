<?php

namespace Drupal\vscpa_sso;

use Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager as SimplesamlphpAuthManagerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Service to interact with the SimpleSAMLPHP authentication library.
 */
class SimplesamlphpAuthManager extends SimplesamlphpAuthManagerBase {

  /**
   * Log a user out through the SimpleSAMLphp instance.
   *
   * @param string $redirect_path
   *   The path to redirect to after logout.
   */
  public function logout($redirect_path = NULL) {
    // Return without executing if the functionality is not enabled.
    if (!\Drupal::config('simplesamlphp_auth.settings')->get('activate')) {
      return;
    }
    // Destroy the current session, and reset $user to the anonymous user.
    // Note: In Symfony the session is intended to be destroyed with
    // Session::invalidate(). Regrettably this method is currently broken
    // and may lead to the creation of spurious session records in
    // the database.
    // @see https://github.com/symfony/symfony/issues/12375
    if (!$this->isAuthenticated()) {
      // Nothing to do.
      return;
    }
    // 1. Kill Drupal Session.
    $user = \Drupal::currentUser();
    \Drupal::service('session_manager')->destroy();
    $user->setAccount(new AnonymousUserSession());
    // 2. Kill SimpleSAMLPhp session.
    $this->killSimpleSamlSession();
    // 3. Kill the session on GLuu Server(IDP).
    $idp_logout = 'https://sso.vscpa.com/idp/logout.jsp';
    $response = new TrustedRedirectResponse($idp_logout);
    $response->send();
    \Drupal::service('http_kernel')->terminate(\Drupal::request(), $response);
    exit;
  }

  /**
   * Kill SimpleSAMLphp active Session.
   */
  public function killSimpleSamlSession() {
    $session = \SimpleSAML_Session::getSessionFromRequest();
    $sessionId = $session->getSessionId();
    if (empty($sessionId)) {
      return;
    }
    $connection = \Drupal::database();
    $connection->delete('SimpleSAMLphp_saml_LogoutStore')->condition('_sessionId', $sessionId)->execute();
    $connection->delete('SimpleSAMLphp_kvstore')->condition('_key', $sessionId)->execute();
  }

}
