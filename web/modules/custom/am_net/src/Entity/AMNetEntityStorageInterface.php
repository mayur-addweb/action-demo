<?php

namespace Drupal\am_net\Entity;

/**
 * Defines interface for AM.Net entity storage classes.
 */
interface AMNetEntityStorageInterface {

  /**
   * Loads one entity.
   *
   * @param mixed $id
   *   The ID of the entity to load.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityInterface|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function load($id);

  /**
   * Constructs a new entity object, without permanently saving it.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityInterface
   *   A new entity object.
   */
  public function create(array $values = []);

  /**
   * Resets the internal, static entity cache.
   *
   * @param array $ids
   *   (optional) If specified, the cache is reset for the entities with the
   *   given ids only.
   */
  public function resetCache(array $ids = NULL);

  /**
   * Loads one or more entities.
   *
   * @param array $ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityInterface[]
   *   An array of entity objects indexed by their IDs. Returns an empty array
   *   if no matching entities are found.
   */
  public function loadMultiple(array $ids = NULL);

  /**
   * Load entities by their property values.
   *
   * @param array $values
   *   An associative array where the keys are the property names and the
   *   values are the values those properties must have.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityInterface[]
   *   An array of entity objects indexed by their ids.
   */
  public function loadByProperties(array $values = []);

  /**
   * Loads one or more entities by their property values.
   *
   * @param array $values
   *   An associative array where the keys are the property names and the
   *   values are the values those properties must have.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityInterface[]
   *   An array of entity objects indexed by their ids.
   */
  public function loadMultipleByProperties(array $values = []);

  /**
   * Deletes permanently saved entities.
   *
   * @param array $entities
   *   An array of entity objects to delete.
   *
   * @throws \Drupal\am_net\Entity\AMNetEntityStorageInterface
   *   In case of failures, an exception is thrown.
   */
  public function delete(array $entities);

  /**
   * Saves the entity permanently.
   *
   * @param \Drupal\am_net\Entity\AMNetEntityInterface $entity
   *   The entity to save.
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED is returned depending on the operation
   *   performed.
   *
   * @throws \Drupal\am_net\Entity\AMNetEntityStorageInterface
   *   In case of failures, an exception is thrown.
   */
  public function save(AMNetEntityInterface $entity);

}
