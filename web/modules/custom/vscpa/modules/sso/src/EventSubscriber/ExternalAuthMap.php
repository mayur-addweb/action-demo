<?php

namespace Drupal\vscpa_sso\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent;
use Drupal\externalauth\Event\ExternalAuthEvents;

/**
 * Event subscriber subscribing to ExternalAuthEvents::AUTHMAP_ALTER.
 */
class ExternalAuthMap implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ExternalAuthEvents::AUTHMAP_ALTER][] = ['externalAuthMapAlter'];
    return $events;
  }

  /**
   * External auth map alter.
   *
   * @param \Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent $event
   *   The auth map event.
   */
  public function externalAuthMapAlter(ExternalAuthAuthmapAlterEvent $event) {
    $user_name = $event->getUsername();
    $provider = $event->getProvider();
    $prefix = $provider . '_';
    // Remove prefix from username.
    $new_user_name = str_replace($prefix, '', $user_name);
    $new_user_name = str_replace($provider, '', $new_user_name);
    // Set the username.
    $event->setUsername($new_user_name);
  }

}
