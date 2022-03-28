<?php

namespace Drupal\vscpa_sso\Routing;

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
    if ($route = $collection->get('user.reset.form')) {
      $route->setDefault('_controller', '\Drupal\vscpa_sso\Controller\UserController::getResetPassForm');
      $route->setDefault('_title', 'Reset Your Password');
      $route->setRequirements(['_access' => 'TRUE']);
    }
    if ($route = $collection->get('user.pass')) {
      $route->setDefault('_form', '\Drupal\vscpa_sso\Form\UserPasswordForm');
    }
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->addRequirements(['_custom_access' => '\Drupal\am_net_user_profile\Controller\ProfileUpdateBase::access']);
    }
    if ($route = $collection->get('user.register')) {
      $route->setDefault('_title', 'Create Account');
      $route->setDefault('_form', '\Drupal\vscpa_sso\Form\UserRegisterForm');
      $defaults = $route->getDefaults();
      unset($defaults['_entity_form']);
      $route->setDefaults($defaults);
    }
  }

}
