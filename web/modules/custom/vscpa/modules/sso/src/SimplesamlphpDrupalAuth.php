<?php

namespace Drupal\vscpa_sso;

use Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth as SimplesamlphpDrupalAuthBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Service to link SimpleSAMLphp authentication with Drupal users.
 */
class SimplesamlphpDrupalAuth extends SimplesamlphpDrupalAuthBase {

  /**
   * Log in and optionally register a user based on the authname provided.
   *
   * @param string $authname
   *   The authentication name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The logged in Drupal user.
   */
  public function externalLoginRegister($authname) {
    $account = $this->externalauth->login($authname, 'simplesamlphp_auth');
    if (!$account) {
      $account = $this->externalRegister($authname);
    }
    return $account;
  }

  /**
   * Registers a user locally as one authenticated by the SimpleSAML IdP.
   *
   * @param string $authname
   *   The authentication name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|bool
   *   The registered Drupal user.
   *
   * @throws \Exception
   *   An ExternalAuth exception.
   */
  public function externalRegister($authname) {
    $account = FALSE;

    // First we check the admin settings for simpleSAMLphp and find out if we
    // are allowed to register users.
    if (!$this->config->get('register_users')) {

      // We're not allowed to register new users on the site through simpleSAML.
      // We let the user know about this and redirect to the user/login page.
      drupal_set_message(t("We are sorry. While you have successfully authenticated, you are not yet entitled to access this site. Please ask the site administrator to provision access for you."));
      $this->simplesamlAuth->logout(base_path());

      return FALSE;
    }

    // It's possible that a user with their username set to this authname
    // already exists in the Drupal database.
    $existing_user = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $authname]);
    $existing_user = $existing_user ? reset($existing_user) : FALSE;
    if ($existing_user) {
      // If auto-enable SAML is activated, link this user to SAML.
      if ($this->config->get('autoenablesaml')) {
        $this->externalauth->linkExistingAccount($authname, 'simplesamlphp_auth', $existing_user);
        $account = $existing_user;
      }
      else {
        // User is not permitted to login to Drupal via SAML.
        // Log out of SAML and redirect to the front page.
        drupal_set_message(t('We are sorry, your user account is not SAML enabled.'));
        $this->simplesamlAuth->logout(base_path());
        return FALSE;
      }
    }
    else {
      // If auto-enable SAML is activated, take more action to find an existing
      // user.
      if ($this->config->get('autoenablesaml')) {
        // Allow other modules to decide if there is an existing Drupal user,
        // based on the supplied SAML atttributes.
        $attributes = $this->simplesamlAuth->getAttributes();
        foreach (\Drupal::moduleHandler()->getImplementations('simplesamlphp_auth_existing_user') as $module) {
          $return_value = \Drupal::moduleHandler()->invoke($module, 'simplesamlphp_auth_existing_user', [$attributes]);
          if ($return_value instanceof UserInterface) {
            $account = $return_value;
            $this->externalauth->linkExistingAccount($authname, 'simplesamlphp_auth', $account);
          }
        }
      }
    }

    if (!$account) {
      // Create the new user.
      try {
        $account = $this->externalauth->register($authname, 'simplesamlphp_auth');
      }
      catch (\Exception $ex) {
        watchdog_exception('simplesamlphp_auth', $ex);
        drupal_set_message(t('Error registering user: An account with this username already exists.'), 'error');
      }
    }

    if ($account) {
      return $this->externalauth->userLoginFinalize($account, $authname, 'simplesamlphp_auth');
    }
  }

  /**
   * Synchronizes user data if enabled.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The Drupal account to synchronize attributes on.
   * @param bool $force
   *   Define whether to force syncing of the user attributes, regardless of
   *   SimpleSAMLphp settings.
   */
  public function synchronizeUserAttributes(AccountInterface $account, $force = FALSE) {
    // We are not supporting User attributes sync on
    // this integration, stop here.
  }

  /**
   * Adds roles to user accounts.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user to add roles to.
   */
  public function roleMatchAdd(UserInterface $account) {
    // We are not supporting User role match on
    // this integration, stop here.
  }

  /**
   * Get matching user roles to assign to user.
   *
   * Matching roles are based on retrieved SimpleSAMLphp attributes.
   *
   * @return array
   *   List of matching roles to assign to user.
   */
  public function getMatchingRoles() {
    // We are not supporting User role match on
    // this integration, return empty array.
    $roles = [];
    return $roles;
  }

  /**
   * Determines whether a role should be added to an account.
   *
   * @param string $role_eval_part
   *   Part of the role evaluation rule.
   *
   * @return bool
   *   Whether a role should be added to the Drupal account.
   */
  protected function evalRoleRule($role_eval_part) {
    // We are not supporting User role match on
    // this integration, return FALSE by default.
    return FALSE;
  }

}
