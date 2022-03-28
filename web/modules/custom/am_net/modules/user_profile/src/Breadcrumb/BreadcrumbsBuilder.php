<?php

namespace Drupal\am_net_user_profile\Breadcrumb;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;

/**
 * Breadcrumbs Builder for 'My Account' pages.
 */
class BreadcrumbsBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The user currently logged in.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $supported_routes = [
      'entity.user.canonical',
      'entity.user.edit_form',
      'vscpa_commerce.payment_history',
      'am_net_user_profile.employment_information',
      'am_net_user_profile.elected_officials',
      'am_net_user_profile.communications',
      'am_net_user_profile.website_account',
      'entity.commerce_payment_method.collection',
    ];
    $route_name = $route_match->getRouteName();
    return in_array($route_name, $supported_routes);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    $breadcrumb = new Breadcrumb();
    $links = [];
    // Home Link.
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    if ($route_name == 'entity.user.name_account') {
      return $breadcrumb->setLinks($links);
    }
    $email = $this->currentUser->getEmail();
    if (!empty($email)) {
      $links[] = Link::createFromRoute($email, 'entity.user.canonical', ['user' => $this->currentUser->id()]);
    }
    return $breadcrumb->setLinks($links);
  }

}
