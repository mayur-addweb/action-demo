<?php

namespace Drupal\am_net_user_profile;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

/**
 * Default implementation of the User Profile Update Helper.
 */
class UserProfileUpdateHelper {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new OrderReceiptSubscriber object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager) {
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * Checks access for update profile pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    return AccessResult::allowedIf($this->updateProfileAccessCheck($account));
  }

  /**
   * Checks access for update profile pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function becomeFirmAdminAccess(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    return AccessResult::allowedIf($this->becomeFirmAdminAccessCheck($account));
  }

  /**
   * Update Profile Access Check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return bool
   *   TRUE if the given account has access, otherwise FALSE.
   */
  public function updateProfileAccessCheck(AccountInterface $account) {
    // Check if the account is Authenticated.
    if (!$account->isAuthenticated()) {
      return FALSE;
    }
    $roles = $account->getRoles();
    $is_admin = in_array('administrator', $roles) || in_array('vscpa_administrator', $roles);
    if ($is_admin) {
      return TRUE;
    }
    // Check if there are a valid user in the current context.
    $user = \Drupal::routeMatch()->getParameter('user');
    if ($user && ($user instanceof AccountInterface) && ($user->id() != $account->id())) {
      return FALSE;
    }
    // User has access!.
    return TRUE;
  }

  /**
   * Send Request to Become Firm Admin given a user and a firm.
   *
   * @param bool $send_request
   *   Flag that indicate whether the request should be send or not.
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param \Drupal\taxonomy\TermInterface $firm
   *   The firm entity.
   *
   * @return string|null
   *   A result message, otherwise NULL.
   */
  public function sendRequestBecomeFirmAdmin($send_request = NULL, UserInterface $user = NULL, TermInterface $firm = NULL) {
    if (is_null($send_request) || is_null($user)  || is_null($firm)) {
      return NULL;
    }
    $field_name = 'field_become_a_firm_admin';
    $field_become_firm_admin = $user->get($field_name)->getString();
    $send_request_email = FALSE;
    $update_user = ($send_request != $field_become_firm_admin);
    if ($send_request) {
      // Check the firm admin status.
      if ($this->isFirmAdmin($user)) {
        $message = t('Your request was processed and now you are a firm admin of the firm: <i><u>@firm_name</u></i>!', ['@firm_name' => $firm->label()]);
      }
      else {
        $send_request_email = TRUE;
        $message = t('Your request was submitted, it is pending to be process, Please allow 5 business days to process this request.');
      }
    }
    else {
      // Check the firm admin status.
      if ($this->isFirmAdmin($user)) {
        $message = t('Your request was processed and now you are a firm admin of the firm: <i><u>@firm_name</u></i>!', ['@firm_name' => $firm->label()]);
      }
      else {
        $message = t('Your request was removed!.');
      }
    }
    // Update user.
    if ($update_user) {
      $membershipChecker = \Drupal::service('am_net_membership.checker');
      // Put the sync lock over the user.
      $membershipChecker->lockUserSync($user);
      // Set the Change.
      $user->set($field_name, $send_request);
      // Save the Change.
      $user->save();
      // Remove sync lock over the user.
      $membershipChecker->unlockUserSync($user);
    }
    // Send Request Email.
    if ($send_request_email) {
      $this->sendBecomeFirmAdminEmailNotification($user, $firm);
    }
    return $message;
  }

  /**
   * Sends an email notification to firm admin.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param \Drupal\taxonomy\TermInterface $firm
   *   The firm entity.
   */
  public function sendBecomeFirmAdminEmailNotification(UserInterface $user = NULL, TermInterface $firm = NULL) {
    $key = 'am_net_user_profile_setting_send_become_firm_admin_email_notification';
    $state = \Drupal::state();
    if ($state->get($key) != 1) {
      // The email notification is disabled.
      return;
    }
    $key = 'am_net_user_profile_setting_become_firm_admin_email_to';
    $to = $state->get($key);
    if (empty($to)) {
      // The email should not be empty.
      return;
    }
    $email = $user->getEmail();
    $params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'from' => 'no-reply@vscpa.com',
      'subject' => t('New Request: Become Firm Admin - @email.', ['@email' => $email]),
    ];
    $params['body'] = t('New request for Become a firm admin has been submitted.</br> From user: @user_email for the Firm: @firm_name.', ['@user_email' => $email, '@firm_name' => $firm->label()]);
    // Replicated logic from EmailAction and contact's MailHandler.
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $this->mailManager->mail('am_net_user_profile', 'become_firm_admin_email_notification', $to, $langcode, $params);
  }

  /**
   * Become Firm Admin Access Check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return bool
   *   TRUE if the given account has access, otherwise FALSE.
   */
  public function becomeFirmAdminAccessCheck(AccountInterface $account) {
    if (!$this->updateProfileAccessCheck($account)) {
      return FALSE;
    }
    if ($this->isFirmAdmin($account)) {
      return FALSE;
    }
    // User has access!.
    return TRUE;
  }

  /**
   * Check if user has the role firm admin.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return bool
   *   TRUE if the given account has access, otherwise FALSE.
   */
  public function isFirmAdmin(AccountInterface $account) {
    // Check Role.
    return in_array('firm_administrator', $account->getRoles());
  }

}
