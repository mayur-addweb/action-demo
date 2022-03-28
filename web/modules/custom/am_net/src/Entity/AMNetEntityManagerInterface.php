<?php

namespace Drupal\am_net\Entity;

/**
 * Provides an interface for AM.net entity type managers.
 */
interface AMNetEntityManagerInterface {

  /**
   * Creates a new storage instance.
   *
   * @param string $entity_type
   *   The entity type for this storage.
   * @param string $entity_class
   *   The entity class name.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityStorageInterface
   *   A storage instance.
   */
  public function getStorage($entity_type, $entity_class);

  /**
   * Gets the AM.net entity type ID based on the class that is called on.
   *
   * Compares the class this is called on against the known entity classes
   * and returns the entity type ID of a direct match or a subclass as fallback,
   * to support entity type definitions that were altered.
   *
   * @param string $class_name
   *   Class name to use for searching the entity type ID.
   *
   * @return string
   *   The entity type ID.
   *
   * @see \Drupal\am_net\Entity\AMNetEntity::load()
   */
  public function getEntityTypeFromClass($class_name);

}
