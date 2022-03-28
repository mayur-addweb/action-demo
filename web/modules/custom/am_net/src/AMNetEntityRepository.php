<?php

namespace Drupal\am_net;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * A base AM.net entity repository class.
 */
class AMNetEntityRepository {

  /**
   * The AM.net API HTTP Client.
   *
   * @var \Drupal\am_net\AssociationManagementClient|null
   */
  protected $client = NULL;

  /**
   * The memory cache.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $memoryCache;

  /**
   * The array of entities.
   *
   * @var array
   */
  protected $entities = [];

  /**
   * Constructs an Entity Storage Base instance.
   *
   * @param \Drupal\am_net\AssociationManagementClient|null $client
   *   The AM.net API HTTP Client.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface|null $cache_factory
   *   The memory cache factory.
   */
  public function __construct(AssociationManagementClient $client = NULL, KeyValueFactoryInterface $cache_factory = NULL) {
    $this->client = $client;
    $this->memoryCache = $cache_factory->get('amnet.entity.repository');
  }

  /**
   * Loads one entity.
   *
   * @param array $id
   *   The ID of the entity to load.
   * @param \Drupal\am_net\AMNetEntityTypeContext $context
   *   The entity type context.
   *
   * @return array|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function getEntity(array $id = [], AMNetEntityTypeContext $context = NULL) {
    return $this->load($id, $context);
  }

  /**
   * Loads one entity.
   *
   * @param array $id
   *   The entity ID.
   * @param \Drupal\am_net\AMNetEntityTypeContext $context
   *   The entity type context.
   *
   * @return array|null
   *   An array tha represent the entity object or null of no entity found.
   */
  public function load(array $id = NULL, AMNetEntityTypeContext $context = NULL) {
    $entity = NULL;
    if ($context->isStaticallyCacheable()) {
      $entity = $this->getFromStaticCache($id, $context);
    }
    if (empty($entity)) {
      // Load entity from the database. This is the case if there
      // are any ids left to load.
      $queried_entity = $this->doLoad($id, $context);
    }
    if (!empty($queried_entity)) {
      $entity = $queried_entity;
      // Add entity to the cache.
      if ($context->isStaticallyCacheable()) {
        $this->setStaticCache($entity, $id, $context);
      }
    }
    return $entity;
  }

  /**
   * Performs storage-specific loading of the entity.
   *
   * Override this method to add custom functionality directly after loading.
   * This is always called, while self::postLoad() is only called when there are
   * actual results.
   *
   * @param array $id
   *   An array with the entity ID info.
   * @param \Drupal\am_net\AMNetEntityTypeContext $context
   *   The entity type context.
   *
   * @return array
   *   Associative array of entities, keyed on the entity ID.
   */
  protected function doLoad(array $id = NULL, AMNetEntityTypeContext $context = NULL) {
    $entity_type = $context->getEntityType();
    if (empty($entity_type)) {
      return NULL;
    }
    $entity = NULL;
    if ($entity_type == AMNetEntityTypesInterface::EVENT) {
      // Get Event from AM.net.
      $event_code = $id['EventCode'] ?? NULL;
      $event_year = $id['EventYear'] ?? NULL;
      $entity = $this->getAmNetEvent($event_code, $event_year);
    }
    elseif ($entity_type == AMNetEntityTypesInterface::COURSE) {
      $product_code = $id['ProductCode'] ?? NULL;
      $entity = $this->getAmNetProduct($product_code);
    }
    elseif ($entity_type == AMNetEntityTypesInterface::EVENT_REGISTRATION) {
      $name_id = $id['id'] ?? NULL;
      $event_code = $id['EventCode'] ?? NULL;
      $event_year = $id['EventYear'] ?? NULL;
      $entity = $this->getAmNetEventRegistration($name_id, $event_code, $event_year);
    }
    return $entity;
  }

  /**
   * Gets entity from the static cache.
   *
   * @param array $id
   *   If not empty, return the entity that matches with the given ID.
   * @param \Drupal\am_net\AMNetEntityTypeContext $context
   *   The entity type context.
   *
   * @return array|null
   *   Array of entity from the entity cache.
   */
  protected function getFromStaticCache(array $id = [], AMNetEntityTypeContext $context = NULL) {
    $entity = NULL;
    $cache_id = $this->buildCacheId($id, $context);
    if (isset($this->entities[$cache_id])) {
      $entity = $this->entities[$cache_id];
    }
    elseif ($cached = $this->memoryCache->get($cache_id, FALSE)) {
      $entity = $cached;
      $this->entities[$cache_id] = $entity;
    }
    return $entity;
  }

  /**
   * Stores entities in the static entity cache.
   *
   * @param array $entity
   *   Entities to store in the cache.
   * @param array $id
   *   The entity ID.
   * @param \Drupal\am_net\AMNetEntityTypeContext $context
   *   The entity type context.
   */
  protected function setStaticCache(array $entity = [], array $id = [], AMNetEntityTypeContext $context = NULL) {
    if ($context->isStaticallyCacheable()) {
      $cache_id = $this->buildCacheId($id, $context);
      $this->memoryCache->set($cache_id, $entity);
      $this->entities[$cache_id] = $entity;
    }
  }

  /**
   * Resets the internal, static entity cache.
   *
   * @param array $id
   *   The entity ID.
   *   The cache is reset for the entities with the given ids only.
   * @param \Drupal\am_net\AMNetEntityTypeContext $context
   *   The entity type context.
   */
  public function resetCache(array $id = NULL, AMNetEntityTypeContext $context = NULL) {
    if ($context->isStaticallyCacheable() && isset($id)) {
      $cache_id = $this->buildCacheId($id, $context);
      $this->memoryCache->delete($cache_id);
      unset($this->entities[$cache_id]);
    }
  }

  /**
   * Get Client.
   *
   * @return \Drupal\am_net\AssociationManagementClient|null
   *   The Api Client instance.
   */
  public function getClient() {
    if (is_null($this->client)) {
      $this->client = \Drupal::service('am_net.client');
    }
    return $this->client;
  }

  /**
   * Transform a given string into a machine name.
   *
   * @param string $value
   *   The value to be transformed.
   *
   * @return string|null
   *   The newly transformed value.
   */
  public function generateMachineName($value = NULL) {
    if (empty($value)) {
      return NULL;
    }
    $new_value = strtolower($value);
    $new_value = preg_replace('/[^a-z0-9_]+/', '_', $new_value);
    return preg_replace('/_+/', '_', $new_value);
  }

  /**
   * Builds the cache ID for the passed in entity ID.
   *
   * @param array $id
   *   The ID of the entity to load.
   * @param \Drupal\am_net\AMNetEntityTypeContext $context
   *   The entity type context.
   *
   * @return string|null
   *   The unique Key related to the given entity ID and entity type.
   */
  public function buildCacheId(array $id = [], AMNetEntityTypeContext $context = NULL) {
    if (empty($id) || !$context) {
      return NULL;
    }
    $key[] = $context->getEntityType();
    foreach ($id as $name => $value) {
      $key[] = $this->generateMachineName($name) . ':' . $this->generateMachineName($value);
    }
    return implode('.', $key);
  }

  /**
   * Clear local cache associated to a given event registrations.
   *
   * @param string $code
   *   The AM.net event code.
   * @param string $year
   *   The AM.net event year (two digits).
   *
   * @return bool
   *   TRUE if the operation was completed, otherwise FALSE.
   */
  public function clearEvenRegistrationsCache($code, $year) {
    if (empty($code) || empty($year)) {
      return FALSE;
    }
    $code = $this->generateMachineName($code);
    // Get all the cache names associated with the given event.
    $cache_id = "event.eventcode:$code.eventyear:$year";
    $cache_id = strtolower($cache_id);
    $this->memoryCache->delete($cache_id);
    unset($this->entities[$cache_id]);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetEvent($event_code, $event_year) {
    $event = $this->client->get('/Event', [
      'yr' => $event_year,
      'Code' => $event_code,
    ]);
    if ($event->hasError()) {
      return NULL;
    }
    if ($result = $event->getResult()) {
      return $result;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetEventRegistration($name_id, $event_code, $event_year) {
    $event = $this->client->get('/EventRegistration', [
      'id' => $name_id,
      'yr' => $event_year,
      'code' => $event_code,
    ]);
    if ($event->hasError()) {
      return NULL;
    }
    if ($result = $event->getResult()) {
      return $result;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetProduct($product_code) {
    $event = $this->client->get('/Product', [
      'code' => $product_code,
    ]);
    if ($event->hasError()) {
      return NULL;
    }
    if ($result = $event->getResult()) {
      return $result;
    }

    return NULL;
  }

}
