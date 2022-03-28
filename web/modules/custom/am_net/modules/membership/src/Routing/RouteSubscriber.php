<?php

namespace Drupal\am_net_membership\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.register')) {
      $route->setDefault('_title', 'Join or Renew Today');
    }
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setPath('/account/{user}');
      $route->setDefault('_title', 'My Account');
    }
  }

}
