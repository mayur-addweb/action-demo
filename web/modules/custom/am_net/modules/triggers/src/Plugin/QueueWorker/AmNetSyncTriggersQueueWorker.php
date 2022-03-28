<?php

namespace Drupal\am_net_triggers\Plugin\QueueWorker;

use Drupal\am_net_cpe\CpeProductManagerInterface;
use Drupal\am_net_cpe\CpeRegistrationManagerInterface;
use Drupal\am_net_triggers\QueueItem\FirmSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\EventSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\NameSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\ProductSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\NameMergesSyncQueueItem;
use Drupal\am_net_triggers\QueueItem\RegistrationSyncQueueItem;
use Drupal\am_net_user_profile\UserProfileManager;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\am_net_firms\FirmManager;

/**
 * Processes AM.net sync triggers.
 *
 * @QueueWorker(
 *   id = "am_net_triggers",
 *   title = @Translation("AM.net sync triggers queue worker"),
 *   cron = {"time" = 60}
 * )
 */
class AmNetSyncTriggersQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The CPE product manager.
   *
   * @var \Drupal\am_net_cpe\CpeProductManagerInterface
   */
  protected $productManager;

  /**
   * The CPE registration manager.
   *
   * @var \Drupal\am_net_cpe\CpeRegistrationManagerInterface
   */
  protected $registrationManager;

  /**
   * The 'am_net_triggers' logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The AM.net user profile manager.
   *
   * @var \Drupal\am_net_user_profile\UserProfileManager
   */
  protected $userProfileManager;

  /**
   * The AM.net Firm manager.
   *
   * @var \Drupal\am_net_firms\FirmManager
   */
  protected $firmManager;

  /**
   * EventSyncQueueWorker constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\am_net_cpe\CpeProductManagerInterface $cpe_product_manager
   *   The CPE product manager.
   * @param \Drupal\am_net_cpe\CpeRegistrationManagerInterface $cpe_registration_manager
   *   The CPE registration manager.
   * @param \Drupal\am_net_user_profile\UserProfileManager $user_profile_manager
   *   The AM.net user profile manager.
   * @param \Drupal\am_net_firms\FirmManager $firm_manager
   *   The AM.net firm manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The 'am_net_triggers' logger channel.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, CpeProductManagerInterface $cpe_product_manager, CpeRegistrationManagerInterface $cpe_registration_manager, UserProfileManager $user_profile_manager, FirmManager $firm_manager, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->productManager = $cpe_product_manager;
    $this->registrationManager = $cpe_registration_manager;
    $this->userProfileManager = $user_profile_manager;
    $this->firmManager = $firm_manager;
    $this->logger = $logger;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('am_net_cpe.product_manager'),
      $container->get('am_net_cpe.registration_manager'),
      $container->get('am_net_user_profile.manager'),
      $container->get('am_net_firms.firm_manager'),
      $container->get('logger.channel.am_net_triggers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    switch (get_class($data)) {
      case EventSyncQueueItem::class:
        $this->processCpeEvent($data);
        break;

      case NameSyncQueueItem::class:
        $this->processName($data);
        break;

      case ProductSyncQueueItem::class:
        $this->processCpeSelfStudyProduct($data);
        break;

      case RegistrationSyncQueueItem::class:
        $this->processCpeEventRegistration($data);
        break;

      case FirmSyncQueueItem::class:
        $this->processFirm($data);
        break;

      case NameMergesSyncQueueItem::class:
        $this->processNameMerges($data);
        break;
    }
  }

  /**
   * Processes a CPE event sync queue item.
   *
   * @param \Drupal\am_net_triggers\QueueItem\EventSyncQueueItem $item
   *   An event sync queue item.
   */
  protected function processCpeEvent(EventSyncQueueItem $item) {
    try {
      $product = $this->productManager->syncAmNetCpeEventProduct($item->code, $item->year);
      if ($product) {
        $this->logger->info('Synced event ({year}/{code}): "{name}"', [
          'name' => $product->label(),
          'year' => $item->year,
          'code' => $item->code,
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Processes a Firm sync queue item.
   *
   * @param \Drupal\am_net_triggers\QueueItem\FirmSyncQueueItem $item
   *   A Firm sync queue item.
   */
  protected function processFirm(FirmSyncQueueItem $item) {
    try {
      $this->firmManager->syncFirmRecord($item->id);
      $this->logger->info('Synced AM.net Firm ID {id}', [
        'id' => $item->id,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Processes a Name sync queue item.
   *
   * @param \Drupal\am_net_triggers\QueueItem\NameSyncQueueItem $item
   *   A Name sync queue item.
   */
  protected function processName(NameSyncQueueItem $item) {
    try {
      $this->userProfileManager->syncUserProfile($item->id, NULL, FALSE, FALSE, TRUE);
      $this->logger->info('Synced AM.net Name ID {id}', [
        'id' => $item->id,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Processes a Name sync queue item.
   *
   * @param \Drupal\am_net_triggers\QueueItem\NameMergesSyncQueueItem $item
   *   A Name sync queue item.
   */
  protected function processNameMerges(NameMergesSyncQueueItem $item) {
    try {
      $this->userProfileManager->mergeUserProfiles($item->deletedId, $item->mergeIntoId, $item->mergedDateTime);
      $this->logger->info('Merges Names Old {old_id} -> New {new_id}.', [
        'old_id' => $item->deletedId,
        'new_id' => $item->mergeIntoId,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Processes a CPE event registration sync queue item.
   *
   * @param \Drupal\am_net_triggers\QueueItem\RegistrationSyncQueueItem $item
   *   A registration sync queue item.
   */
  protected function processCpeEventRegistration(RegistrationSyncQueueItem $item) {
    try {
      $registration = $this->registrationManager->syncAmNetCpeEventRegistration($item->record);
      if ($registration) {
        $this->logger->info('Synced {event} registration {id}', [
          'event' => $registration->getEvent()->label(),
          'id' => $registration->id(),
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Processes a CPE self-study product sync queue item.
   *
   * @param \Drupal\am_net_triggers\QueueItem\ProductSyncQueueItem $item
   *   An CPE self-study product sync queue item.
   */
  protected function processCpeSelfStudyProduct(ProductSyncQueueItem $item) {
    try {
      $product = $this->productManager->syncAmNetCpeSelfStudyProduct($item->code);
      if ($product) {
        $this->logger->info('Synced product ({code}): "{name}"', [
          'name' => $product->label(),
          'code' => $item->code,
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

}
