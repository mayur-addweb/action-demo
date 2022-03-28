<?php

namespace Drupal\am_net_triggers\Plugin\rest\resource;

use Drupal\am_net_cpe\CpeProductManagerInterface;
use Drupal\am_net_triggers\QueueItem\EventSyncQueueItem;
use Drupal\Core\Queue\QueueFactory;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource for triggering an event sync.
 *
 * @RestResource(
 *   id = "am_net_trigger_sync_event",
 *   label = @Translation("AM.net Sync Event trigger"),
 *   uri_paths = {
 *     "create" = "/api/v1/amnet/trigger/sync/event",
 *   }
 * )
 */
class SyncEventResource extends ResourceBase {

  /**
   * The AM.net CPE product manager.
   *
   * @var \Drupal\am_net_cpe\CpeProductManagerInterface
   */
  protected $productManager;

  /**
   * The Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a new SyncEventResource instance.
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
   * @param \Drupal\am_net_cpe\CpeProductManagerInterface $product_manager
   *   The AM.net CPE product manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, CpeProductManagerInterface $product_manager, QueueFactory $queue_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->productManager = $product_manager;
    $this->queueFactory = $queue_factory;
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
      $container->get('am_net_cpe.product_manager'),
      $container->get('queue')
    );
  }

  /**
   * Responds to an event sync POST request.
   *
   * @param array $data
   *   An array that should contain both 'year' and 'code' properties.
   *
   * @return \Drupal\rest\ResourceResponse
   *   An event code 200 or 400 header and a body with an 'error' if one exists.
   */
  public function post(array $data) {
    if (empty($data['year']) || empty($data['code'])) {
      return new ResourceResponse(['error' => "The POST body must contain 'year' and 'code' properties."], 400);
    }
    $year = trim($data['year']);
    $code = trim($data['code']);
    if (!empty($data['realtime'])) {
      try {
        $this->productManager->syncAmNetCpeEventProduct($code, $year);

        return new ResourceResponse();
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());

        return new ResourceResponse('An error has occurred, and has been logged.', 400);
      }
    }
    else {
      $queue_factory = $this->queueFactory->get('am_net_triggers');
      $queue_factory->createItem(
        new EventSyncQueueItem($year, $code)
      );

      return new ResourceResponse();
    }
  }

}
