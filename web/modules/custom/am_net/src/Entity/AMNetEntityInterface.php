<?php

namespace Drupal\am_net\Entity;

/**
 * Defines a common interface for all entity AM.net entity objects.
 */
interface AMNetEntityInterface {

  /**
   * Gets The Entity ID key used for Serializes perform get requests.
   *
   * @return string
   *   The Entity ID key.
   */
  public static function getIdKey();

  /**
   * Gets the identifier.
   *
   * @return string|int|null
   *   The entity identifier, or NULL if the object does not yet have an
   *   identifier.
   */
  public function getId();

  /**
   * Alias of getId().
   *
   * @return string|int|null
   *   The entity identifier, or NULL if the object does not yet have an
   *   identifier.
   */
  public function id();

  /**
   * Format the identifier.
   *
   * Clean identifiers due that some identifiers come with spaces or strange
   * characters from AM.net system.
   */
  public function formatId();

  /**
   * Get The format used for Serializes data into the given type.
   *
   * @return string
   *   The format used for Serialize operations the default format is JSON.
   */
  public static function getFormat();

  /**
   * Set The format used for Serializes data into the given type.
   *
   * @param string $format
   *   The format used for Serialize operations.
   */
  public static function setFormat($format);

  /**
   * Determines whether the AM.net entity is new.
   *
   * Usually an entity is new if no ID exists for it yet. However, entities may
   * be enforced to be new with existing IDs too.
   *
   * @return bool
   *   TRUE if the entity is new, or FALSE if the entity has already been saved.
   *
   * @see \Drupal\am_net\AMNetEntityInterface::enforceIsNew()
   */
  public function isNew();

  /**
   * Enforces an AM.net entity to be new.
   *
   * Allows migrations to create entities with pre-defined IDs by forcing the
   * entity to be new before saving.
   *
   * @param bool $value
   *   (optional) Whether the entity should be forced to be new. Defaults to
   *   TRUE.
   *
   * @return $this
   *
   * @see \Drupal\am_net\AMNetEntityInterface::isNew()
   */
  public function enforceIsNew($value = TRUE);

  /**
   * Gets an array of all property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public function toArray();

  /**
   * Gets the label of the entity.
   *
   * @return string|null
   *   The label of the entity, or NULL if there is no label defined.
   */
  public function label();

  /**
   * Loads an entity.
   *
   * @param mixed $id
   *   The id of the entity to load.
   *
   * @return static
   *   The entity object or NULL if there is no entity with the given ID.
   */
  public static function load($id);

  /**
   * Loads one or more entities.
   *
   * @param array $ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return static[]
   *   An array of entity objects indexed by their IDs.
   */
  public static function loadMultiple(array $ids = NULL);

  /**
   * Constructs a new entity object, without permanently saving it.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return static
   *   The entity object.
   */
  public static function create(array $values = []);

  /**
   * Saves an entity permanently in AM.net System.
   *
   * When saving existing entities, the entity is assumed to be complete,
   * partial updates of entities are not supported.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws \Drupal\am_net\Entity\AMNetEntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function save();

  /**
   * Deletes an entity permanently in AM.net System.
   *
   * @throws \Drupal\am_net\Entity\AMNetEntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function delete();

  /**
   * Get the AM.net API.
   *
   * Get the AM.net API endpoint in charge of handle operations
   * over the concrete entity.
   *
   * @return string
   *   The API endpoint for get operation.
   */
  public static function getApiEndPoint();

  /**
   * Get the name of the Create Entity endpoint AM.net API.
   *
   * Create the AM.net API endpoint in charge of handle operations
   * over the concrete entity.
   *
   * @return string
   *   The API endpoint for get operation.
   */
  public static function getCreateEntityApiEndPoint();

  /**
   * Get the name of the Request Type for Create the Entity.
   *
   * @return string
   *   The Request type name.
   */
  public static function getCreateRequestType();

  /**
   * Get the name of the Request Type for Update the Entity.
   *
   * @return string
   *   The Request type name.
   */
  public static function getUpdateRequestType();

  /**
   * Update the AM.net API.
   *
   * Update the AM.net API endpoint in charge of handle operations
   * over the concrete entity.
   *
   * @return string
   *   The API endpoint for get operation.
   */
  public static function getUpdateEntityApiEndPoint();

  /**
   * Gets the load By properties object identifier key.
   *
   * @return string
   *   The object identifier key.
   */
  public static function getLoadByPropertiesObjectIdentifierKey();

  /**
   * Gets the load By properties API Endpoint.
   *
   * @return string
   *   The API endpoint.
   */
  public static function getLoadByPropertiesApiEndpoint();

  /**
   * Acts on an entity before the pre-save.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityStorageInterface $storage
   *   The entity storage object.
   *
   * @throws \Exception
   *   When there is a problem that should prevent saving the entity.
   */
  public function preSave(AMNetEntityStorageInterface $storage);

  /**
   * Acts on a saved entity before the insert or update.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityStorageInterface $storage
   *   The entity storage object.
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  public function postSave(AMNetEntityStorageInterface $storage, $update = TRUE);

  /**
   * Changes the values of an entity before it is created.
   *
   * Load defaults for example.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityStorageInterface $storage
   *   The entity storage object.
   * @param mixed[] $values
   *   An array of values to set, keyed by property name. If the entity type has
   *   bundles the bundle key has to be specified.
   */
  public static function preCreate(AMNetEntityStorageInterface $storage, array &$values);

  /**
   * Acts on a created entity.
   *
   * Used after the entity is created, but before saving the entity.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityStorageInterface $storage
   *   The entity storage object.
   *
   * @see \Drupal\am_net\Entity\AMNetEntityInterface::create()
   */
  public function postCreate(AMNetEntityStorageInterface $storage);

  /**
   * Acts on entities before they are deleted.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityStorageInterface $storage
   *   The entity storage object.
   * @param \Drupal\am_net\Entity\AMNetEntityInterface[] $entities
   *   An array of entities.
   */
  public static function preDelete(AMNetEntityStorageInterface $storage, array $entities);

  /**
   * Acts on deleted entities.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityStorageInterface $storage
   *   The entity storage object.
   * @param \Drupal\am_net\Entity\AMNetEntityInterface[] $entities
   *   An array of entities.
   */
  public static function postDelete(AMNetEntityStorageInterface $storage, array $entities);

  /**
   * Acts on loaded entities.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityStorageInterface $storage
   *   The entity storage object.
   * @param \Drupal\am_net\Entity\AMNetEntityInterface[] $entities
   *   An array of entities.
   */
  public static function postLoad(AMNetEntityStorageInterface $storage, array &$entities);

}
