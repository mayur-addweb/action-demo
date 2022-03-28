<?php

namespace Drupal\am_net\Entity;

use Drupal\am_net\AMNetSerializerTrait;

/**
 * A base AM.net entity storage class.
 */
class AMNetEntityStorageBase implements AMNetEntityStorageInterface {

  use AMNetSerializerTrait;

  /**
   * The AM.net API HTTP Client.
   *
   * @var \Drupal\am_net\AssociationManagementClient|null
   */
  protected $client = NULL;

  /**
   * Static cache of entities, keyed by entity ID.
   *
   * @var array
   */
  protected $entities = [];

  /**
   * Entity type ID for this storage.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Name of the entity class.
   *
   * @var string
   */
  protected $entityClass;

  /**
   * Constructs an EntityStorageBase instance.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param string $entity_class
   *   The entity class name.
   */
  public function __construct($entity_type_id = '', $entity_class = '') {
    $this->entityTypeId = $entity_type_id;
    $this->entityClass = $entity_class;
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
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function loadUnchanged($id) {
    $this->resetCache([$id]);
    return $this->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    if (isset($ids)) {
      foreach ($ids as $id) {
        unset($this->entities[$id]);
      }
    }
    else {
      $this->entities = [];
    }
  }

  /**
   * Gets entities from the static cache.
   *
   * @param array $ids
   *   If not empty, return entities that match these IDs.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityInterface[]
   *   Array of entities from the entity cache.
   */
  protected function getFromStaticCache(array $ids) {
    $entities = [];
    // Load any available entities from the internal cache.
    if (!empty($this->entities)) {
      $entities += array_intersect_key($this->entities, array_flip($ids));
    }
    return $entities;
  }

  /**
   * Stores entities in the static entity cache.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityInterface[] $entities
   *   Entities to store in the cache.
   */
  protected function setStaticCache(array $entities) {
    $this->entities += $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    /** @var \Drupal\am_net\Entity\AMNetEntityInterface $entity_class */
    $entity_class = $this->entityClass;
    $entity_class::preCreate($this, $values);

    $entity = $this->doCreate($values);
    $entity->enforceIsNew();

    $entity->postCreate($this);

    return $entity;
  }

  /**
   * Performs storage-specific creation of entities.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityInterface
   *   The concrete entity instance.
   */
  protected function doCreate(array $values) {
    return new $this->entityClass($values, $this->entityTypeId);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $id = !empty($id) ? trim($id) : $id;
    $entities = $this->loadMultiple([$id]);
    return isset($entities[$id]) ? $entities[$id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $entities = [];

    // Create a new variable which is either a prepared version of the $ids
    // array for later comparison with the entity cache, or FALSE if no $ids
    // were passed. The $ids array is reduced as items are loaded from cache,
    // and we need to know if it's empty for this reason to avoid querying the
    // database when all requested entities are loaded from cache.
    $passed_ids = !empty($ids) ? array_flip($ids) : FALSE;
    // Try to load entities from the static cache.
    if ($ids) {
      $entities += $this->getFromStaticCache($ids);
      // If any entities were loaded, remove them from the ids still to load.
      if ($passed_ids) {
        $ids = array_keys(array_diff_key($passed_ids, $entities));
      }
    }

    // Load any remaining entities from the database. This is the case if $ids
    // is set to NULL (so we load all entities) or if there are any ids left to
    // load.
    if ($ids === NULL || $ids) {
      $queried_entities = $this->doLoadMultiple($ids);
    }

    // Pass all entities loaded from the AM.net System
    // through $this->postLoad().
    if (!empty($queried_entities)) {
      $this->postLoad($queried_entities);
      $entities += $queried_entities;
    }

    // Add entities to the cache.
    if (!empty($queried_entities)) {
      $this->setStaticCache($queried_entities);
    }

    // Ensure that the returned array is ordered the same as the original
    // $ids array if this was passed in and remove any invalid ids.
    if ($passed_ids) {
      // Remove any invalid ids from the array.
      $passed_ids = array_intersect_key($passed_ids, $entities);
      foreach ($entities as $entity) {
        $passed_ids[$entity->id()] = $entity;
      }
      $entities = $passed_ids;
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function doLoadMultiple(array $ids = NULL) {
    $entities = [];
    if (!empty($ids) && ($_client = $this->getClient())) {
      /** @var \Drupal\am_net\Entity\AMNetEntityInterface $entity_class */
      $entity_class = $this->entityClass;
      $format = $entity_class::getFormat();
      $entity_id_key = $entity_class::getIdKey();
      $api_endpoint = $entity_class::getApiEndPoint();
      foreach ($ids as $delta => $id) {
        $id = trim($id);
        if (empty($id)) {
          continue;
        }
        $entity = NULL;
        $query = $_client->get($api_endpoint, [$entity_id_key => $id], $format);
        if ($query == FALSE) {
          continue;
        }
        if (!$query->hasError()) {
          $json_entity = $query->getResult();
          $entity = $json_entity;
        }
        else {
          $error_message = $query->getErrorMessage();
          if ($error_message == 'SyncErrorCode: 99 | No data') {
            // Remove the records locally on Drupal.
            if (method_exists($entity_class, 'deleteEntity')) {
              $entity_class::deleteEntity($id);
            }
          }
          $status_code = $query->getStatusCode();
          am_net_resolve_exception($status_code, $error_message);
          $error = [
            'operation' => 'doLoadMultiple',
            'endpoint' => $api_endpoint,
            'id' => $id,
            'format' => $format,
            'entity_id_key' => $entity_id_key,
            'entity_detail' => $entity_class,
            'time' => time(),
            'error_message' => $query->getErrorMessage(),
            'error_code' => (int) $query->getStatusCode(),
            'request_type' => 'GET',
          ];
          $this->logSyncError($error);
        }
        if (!empty($entity)) {
          $entities[] = $entity;
        }
      }
    }
    return $this->mapFromStorageRecords($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = []) {
    $entity_id = NULL;
    /** @var \Drupal\am_net\Entity\AMNetEntityInterface $entity_class */
    $entity_class = $this->entityClass;
    $key = $entity_class::getLoadByPropertiesObjectIdentifierKey();
    if (!empty($key) && !empty($values) && ($_client = $this->getClient())) {
      $query = $_client->get($entity_class::getLoadByPropertiesApiEndpoint(), $values, 'array');
      if (($query != FALSE) && !$query->hasError()) {
        $response = $query->getResult();
        if (isset($response[$key])) {
          $_id = $response[$key];
          $entity_id = strtolower(trim($_id));
        }
      }
    }
    return !is_null($entity_id) ? $this->loadMultiple([$entity_id]) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByProperties(array $values = []) {
    $entities = [];
    $json_entities = '';
    /** @var \Drupal\am_net\Entity\AMNetEntityInterface $entity_class */
    $entity_class = $this->entityClass;
    $format = $entity_class::getFormat();
    if (!empty($values) && ($_client = $this->getClient())) {
      $query = $_client->get($entity_class::getLoadByPropertiesApiEndpoint(), $values, $format);
      if (($query != FALSE) && !$query->hasError()) {
        $json_entities = $query->getResult();
      }
    }
    if (!empty($json_entities)) {
      $entities = $this->deserialize($json_entities, $entity_class . '[]', $format);
    }
    return $entities;
  }

  /**
   * Attaches data to entities upon loading.
   *
   * @param array $entities
   *   Associative array of query results, keyed on the entity ID.
   */
  protected function postLoad(array &$entities) {
    /** @var \Drupal\am_net\Entity\AMNetEntityInterface $entity_class */
    $entity_class = $this->entityClass;
    $entity_class::postLoad($this, $entities);
  }

  /**
   * Maps from storage records to entity objects.
   *
   * @param array $records
   *   Associative array of query results, keyed on the entity ID.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityInterface[]
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records) {
    $entities = [];
    /** @var \Drupal\am_net\Entity\AMNetEntityInterface $entity_class */
    $entity_class = $this->entityClass;
    $format = $entity_class::getFormat();
    foreach ($records as $json_record) {
      /** @var \Drupal\am_net\Entity\AMNetEntityInterface $entity */
      $entity = $this->deserialize($json_record, $entity_class, $format);
      $entity->formatId();
      $entities[$entity->id()] = $entity;
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, AMNetEntityInterface $entity) {
    // @todo.
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no entities were passed, do nothing.
      return;
    }
    // Ensure that the entities are keyed by ID.
    $keyed_entities = [];
    foreach ($entities as $entity) {
      $keyed_entities[$entity->id()] = $entity;
    }
    // Perform the delete and reset the static cache for the deleted entities.
    $this->doDelete($keyed_entities);
    $this->resetCache(array_keys($keyed_entities));
  }

  /**
   * {@inheritdoc}
   */
  public function doDelete($entities) {
    // @todo.
  }

  /**
   * {@inheritdoc}
   */
  public function save(AMNetEntityInterface $entity) {
    // Track if this entity is new.
    $is_new = $entity->isNew();
    // Execute presave logic and invoke the related hooks.
    $id = $this->doSave($entity, $is_new);
    return $id;
  }

  /**
   * Performs save entity processing.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityInterface $entity
   *   The saved entity.
   * @param bool $is_new
   *   Specifies whether the entity is being updated or created.
   *
   * @return bool|string
   *   The processed entity identifier.
   */
  protected function doSave(AMNetEntityInterface $entity, $is_new) {
    $result = FALSE;
    $id = $entity->id();
    $id = trim($id);
    $json_entity = $this->serialize($entity);
    /** @var \Drupal\am_net\Entity\AMNetEntityInterface $entity_class */
    $entity_class = $this->entityClass;
    $entity_detail = $entity_class;
    if (!empty($json_entity) && ($_client = $this->getClient())) {
      $queryParams = [];
      if ($is_new) {
        // New.
        $endpoint = $entity_class::getCreateEntityApiEndPoint();
        $request_type = $entity_class::getCreateRequestType();
      }
      else {
        // Update.
        $endpoint = $entity_class::getUpdateEntityApiEndPoint();
        $request_type = $entity_class::getUpdateRequestType();
        $queryParams = ['id' => $id];
        $entity_detail .= "({$id})";
      }
      if (method_exists($_client, $request_type)) {
        /* @var \UnleashedTech\AMNet\Api\AMNetResponseInterface $query */
        if ($query = $_client->$request_type($endpoint, $queryParams, $json_entity)) {
          if (!$query->hasError()) {
            $client_result = $query->getResult();
            if ($is_new) {
              $result = (is_array($client_result) && !empty($client_result)) ? current(array_values($client_result)) : TRUE;
            }
            else {
              $result = SAVED_UPDATED;
            }
          }
          else {
            // Logs an error.
            $error = [
              'operation' => 'doSave',
              'endpoint' => $endpoint,
              'id' => $id,
              'queryParams' => $queryParams,
              'json_entity' => $json_entity,
              'entity_detail' => $entity_detail,
              'time' => time(),
              'error_message' => $query->getErrorMessage(),
              'error_code' => (int) $query->getStatusCode(),
              'request_type' => $request_type,
            ];
            $this->logSyncError($error);
          }
        }
      }
    }
    return $result;
  }

  /**
   * Log sync Error.
   *
   * @param array $log_error
   *   The detailed log error array.
   */
  protected function logSyncError(array $log_error = []) {
    // Add Request source.
    $request = \Drupal::request();
    $log_error['current_uri'] = $request->getRequestUri();
    $log_error['host'] = $request->getHost();
    // Save the log in custom state variable.
    $state = \Drupal::state();
    $key = "am_net.sync.error";
    $values = $state->get($key, []);
    $values[] = $log_error;
    $state->set($key, $values);
    // Log a Drupal error.
    $message = $log_error['entity_detail'] . ' ' . $log_error['error_message'];
    \Drupal::logger('am_net')->error($message);
    // Show friendly message to front-end users.
    $message = "<p>Oops! This is awkward. Something went wrong!</p><p>If performing a transaction involving a credit card, please contact us immediately at (800) 733-8272 or vscpa@vscpa.com for assistance.</p><p>Otherwise, please try again later, and -- if you continue to experience difficulty -- contact us via the information above.</p><p>Thank you!</p>";
    $code = $log_error['error_code'];
    $key = 'entity.' . $log_error['id'];
    am_net_entity_set_message($key, $message, $code);
  }

}
