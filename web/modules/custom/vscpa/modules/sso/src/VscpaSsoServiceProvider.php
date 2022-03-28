<?php

namespace Drupal\vscpa_sso;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the simplesamlphp_auth drupal-auth  service.
 */
class VscpaSsoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Alter service: simplesamlphp_auth.drupalauth.
    $definition = $container->getDefinition('simplesamlphp_auth.drupalauth');
    $definition->setClass('Drupal\vscpa_sso\SimplesamlphpDrupalAuth');
    // Alter service: simplesamlphp_auth.manager.
    $definition = $container->getDefinition('simplesamlphp_auth.manager');
    $definition->setClass('Drupal\vscpa_sso\SimplesamlphpAuthManager');

  }

}
