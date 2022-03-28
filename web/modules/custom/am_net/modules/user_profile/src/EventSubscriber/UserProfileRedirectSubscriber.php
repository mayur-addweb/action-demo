<?php

namespace Drupal\am_net_user_profile\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides redirect to the new edit Website Account page.
 */
class UserProfileRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new DonationManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return ([
      KernelEvents::REQUEST => [
        ['redirectWebsiteAccount'],
      ],
    ]);
  }

  /**
   * Redirect request from user/{user}/edit to user/{user}/edit/website-account.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Get Response Event.
   */
  public function redirectWebsiteAccount(GetResponseEvent $event) {
    $request = $event->getRequest();
    // Validate the route.
    if ($request->attributes->get('_route') !== 'entity.user.edit_form') {
      return;
    }
    // Check if token exits.
    $token = $request->query->get('pass-reset-token');
    if (empty($token)) {
      return;
    }
    // Validate by admin roles route.
    $admin_roles = [
      'administrator',
      'store_manager',
      'content_manager',
    ];
    $current_user_roles = $this->currentUser->getRoles();
    foreach ($admin_roles as $key => $role) {
      if (in_array($role, $current_user_roles)) {
        return;
      }
    }
    // In this point we need redirect the user.
    $redirect_url = Url::fromRoute('am_net_user_profile.website_account', ['user' => $this->currentUser->id()],
      [
        'query' => ['pass-reset-token' => $token],
        'absolute' => TRUE,
      ]);
    $response = new RedirectResponse($redirect_url->toString(), 301);
    $event->setResponse($response);
  }

}
