<?php

namespace Drupal\raven\Plugin\CspReportingHandler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\csp\Csp;
use Drupal\csp\Plugin\ReportingHandlerBase;
use InvalidArgumentException;
use Raven_Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CSP Reporting Plugin for a Sentry endpoint.
 *
 * @CspReportingHandler(
 *   id = "raven",
 *   label = "Sentry",
 *   description = @Translation("Reports will be sent to Sentry."),
 * )
 */
class Raven extends ReportingHandlerBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterPolicy(Csp $policy) {
    if (!class_exists(Raven_Client::class)) {
      return;
    }
    $config = $this->configFactory->get('raven.settings');
    try {
      $dsn = Raven_Client::parseDSN(empty($_SERVER['SENTRY_DSN']) ? $config->get('public_dsn') : $_SERVER['SENTRY_DSN']);
    }
    catch (InvalidArgumentException $e) {
      // Raven is incorrectly configured.
      return;
    }
    $query = ['sentry_key' => $dsn['public_key']];
    if ($environment = empty($_SERVER['SENTRY_ENVIRONMENT']) ? $config->get('environment') : $_SERVER['SENTRY_ENVIRONMENT']) {
      $query['sentry_environment'] = $environment;
    }
    if ($release = empty($_SERVER['SENTRY_RELEASE']) ? $config->get('release') : $_SERVER['SENTRY_RELEASE']) {
      $query['sentry_release'] = $release;
    }
    $policy->setDirective('report-uri', Url::fromUri(str_replace('/store/', '/security/', $dsn['server']), ['query' => $query])->toString());
  }

}
