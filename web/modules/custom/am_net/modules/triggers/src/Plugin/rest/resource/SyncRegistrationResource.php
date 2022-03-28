<?php

namespace Drupal\am_net_triggers\Plugin\rest\resource;

use Drupal\am_net_cpe\CpeRegistrationManagerInterface;
use Drupal\am_net_triggers\QueueItem\RegistrationSyncQueueItem;
use Drupal\Core\Queue\QueueFactory;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource for triggering a registration sync.
 *
 * @RestResource(
 *   id = "am_net_trigger_sync_registration",
 *   label = @Translation("AM.net Sync Registration trigger"),
 *   uri_paths = {
 *     "create" = "/api/v1/amnet/trigger/sync/registration",
 *   }
 * )
 */
class SyncRegistrationResource extends ResourceBase {

  /**
   * The Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The AM.net CPE registration manager.
   *
   * @var \Drupal\am_net_cpe\CpeRegistrationManagerInterface
   */
  protected $registrationManager;

  /**
   * Constructs a new SyncRegistrationResource instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\am_net_cpe\CpeRegistrationManagerInterface $registration_manager
   *   The AM.net CPE registration manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, QueueFactory $queue_factory, CpeRegistrationManagerInterface $registration_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->queueFactory = $queue_factory;
    $this->registrationManager = $registration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('queue'),
      $container->get('am_net_cpe.registration_manager')
    );
  }

  /**
   * Responds to a registration sync POST request.
   *
   * @param array $data
   *   An array that should contain one or more of the following:
   *   - name: An AM.net member id.
   *   - year: A two-digit AM.net event year.
   *   - code: An event code.
   *   - since: A starting date to search for registrations (YYYY-MM-DD).
   *
   * @return \Drupal\rest\ResourceResponse
   *   An event code 200 or 400 header and a body with an 'error' or 'message'.
   */
  public function post(array $data) {
    $allowed = ['name', 'year', 'code', 'since'];
    if (empty(array_intersect($allowed, array_keys($data)))) {
      return new ResourceResponse([
        'error' => "The POST body must contain one or more of these properties:"
        . implode(', ', $allowed),
      ], 400);
    }
    $name = isset($data['name']) ? trim($data['name']) : NULL;
    $year = isset($data['year']) ? trim($data['year']) : NULL;
    $code = isset($data['code']) ? trim($data['code']) : NULL;
    $since = isset($data['since']) ? $data['since'] : NULL;
    $queue_factory = $this->queueFactory->get('am_net_triggers');
    $registrations = $this->registrationManager
      ->getAmNetEventRegistrations($name, $year, $code, $since);
    foreach ($registrations as $registration) {
      if (!empty($data['realtime'])) {
        $this->registrationManager->syncAmNetCpeEventRegistration($registration);
      }
      else {
        $queue_factory->createItem(
          new RegistrationSyncQueueItem($registration)
        );
      }
    }

    if (!empty($data['realtime'])) {
      $message = 'Synced ' . count($registrations) . ' registrations.';
    }
    else {
      $message = 'Queued up ' . count($registrations) . ' registrations for syncing.';
    }

    return new ResourceResponse([
      'message' => $message,
    ]);
  }

}
