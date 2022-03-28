<?php

namespace Drupal\raven\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use InvalidArgumentException;
use Raven_Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for CSP events.
 */
class CspSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    if (!class_exists(CspEvents::class)) {
      return [];
    }

    $events[CspEvents::POLICY_ALTER] = ['onCspPolicyAlter'];
    return $events;
  }

  /**
   * CspSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Alter CSP policy to allow Sentry to send JS errors.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The Policy Alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent) {
    $config = $this->configFactory->get('raven.settings');
    if (!$config->get('javascript_error_handler')) {
      return;
    }
    if (!class_exists(Raven_Client::class)) {
      return;
    }
    try {
      $dsn = Raven_Client::parseDSN(empty($_SERVER['SENTRY_DSN']) ? $config->get('public_dsn') : $_SERVER['SENTRY_DSN']);
    }
    catch (InvalidArgumentException $e) {
      // Raven is incorrectly configured.
      return;
    }
    self::fallbackAwareAppendIfEnabled(
      $alterEvent->getPolicy(),
      'connect-src',
      $dsn['server']
    );
  }

  /**
   * Append to a directive if it or a fallback directive is enabled.
   *
   * If the specified directive is not enabled but one of its fallback
   * directives is, it will be initialized with the same value as the fallback
   * before appending the new value.
   *
   * If none of the specified directive's fallbacks are enabled, the directive
   * will not be enabled.
   *
   * @param \Drupal\csp\Csp $policy
   *   The CSP directive to alter.
   * @param string $directive
   *   The directive name.
   * @param array|string $value
   *   The directive value.
   */
  private static function fallbackAwareAppendIfEnabled(Csp $policy, $directive, $value) {
    if ($policy->hasDirective($directive)) {
      $policy->appendDirective($directive, $value);
      return;
    }

    // Duplicate the closest fallback directive with a value.
    foreach (Csp::getDirectiveFallbackList($directive) as $fallback) {
      if ($policy->hasDirective($fallback)) {
        $policy->setDirective($directive, $policy->getDirective($fallback));
        $policy->appendDirective($directive, $value);
        return;
      }
    }
  }

}
