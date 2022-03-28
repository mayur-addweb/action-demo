<?php

namespace Drupal\am_net_membership\Breadcrumb;

use Drupal\Core\Link;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;

/**
 * Membership Breadcrumb Builder.
 */
class MembershipBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * The List of supported routes.
   *
   * @var array
   */
  protected $supportedRoutes = [
    'user.register',
    'am_net_membership.registration.dues',
  ];

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteName();
    return !empty($route) && in_array($route, $this->supportedRoutes);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $route = $route_match->getRouteName();
    if ($route == 'am_net_membership.registration.dues') {
      $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));
      $breadcrumb->addLink(Link::createFromRoute('VSCPA', '<front>'));
      $breadcrumb->addLink(Link::createFromRoute('Join or Renew Today', 'user.register'));
    }
    return $breadcrumb;
  }

}
