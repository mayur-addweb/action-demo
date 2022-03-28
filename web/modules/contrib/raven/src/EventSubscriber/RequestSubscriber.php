<?php

namespace Drupal\raven\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Initializes Raven logger if fatal error logging is enabled.
 */
class RequestSubscriber implements EventSubscriberInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Raven RequestSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Initializes Raven logger if fatal error logging is enabled.
   */
  public function onRequest() {
    if ($this->configFactory->get('raven.settings')->get('fatal_error_handler') && $this->container) {
      $this->container->get('logger.raven');
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 222];
    return $events;
  }

}
