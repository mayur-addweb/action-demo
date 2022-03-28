<?php

namespace Drupal\am_net_triggers\Plugin\rest\resource;

use Drupal\am_net_triggers\QueueItem\NameSyncQueueItem;
use Drupal\am_net_user_profile\UserProfileManager;
use Drupal\Core\Queue\QueueFactory;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource for triggering an AM.net Name sync.
 *
 * @RestResource(
 *   id = "am_net_trigger_sync_name",
 *   label = @Translation("AM.net Sync Name trigger"),
 *   uri_paths = {
 *     "create" = "/api/v1/amnet/trigger/sync/name",
 *   }
 * )
 */
class SyncNameResource extends ResourceBase {

  /**
   * The Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The AM.net user profile manager.
   *
   * @var \Drupal\am_net_user_profile\UserProfileManager
   */
  protected $userProfileManager;

  /**
   * Constructs a new SyncNameResource instance.
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
   * @param \Drupal\am_net_user_profile\UserProfileManager $user_profile_manager
   *   The AM.net user profile manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, UserProfileManager $user_profile_manager, QueueFactory $queue_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->userProfileManager = $user_profile_manager;
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
      $container->get('am_net_user_profile.manager'),
      $container->get('queue')
    );
  }

  /**
   * Responds to a Name sync POST request.
   *
   * @param array $data
   *   An array that should contain a name 'id' property.
   *
   * @return \Drupal\rest\ResourceResponse
   *   An event code 200 or 400 header and a body with an 'error' if one exists.
   */
  public function post(array $data) {
    if (empty($data['id'])) {
      return new ResourceResponse(['error' => "The POST body must contain an 'id' property."], 400);
    }
    $am_net_id = trim($data['id']);
    if (!empty($data['realtime'])) {
      $this->userProfileManager->syncUserProfile($am_net_id);
    }
    else {
      $queue_factory = $this->queueFactory->get('am_net_triggers');
      $queue_factory->createItem(
        new NameSyncQueueItem($am_net_id)
      );
    }

    return new ResourceResponse();
  }

}
