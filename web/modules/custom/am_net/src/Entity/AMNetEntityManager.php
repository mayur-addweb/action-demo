<?php

namespace Drupal\am_net\Entity;

/**
 * Provides a wrapper around many other services relating to AM.net entities.
 */
class AMNetEntityManager implements AMNetEntityManagerInterface {

  /**
   * The AM.net entities storage.
   *
   * @var \Drupal\am_net\Entity\AMNetEntityStorageInterface[]
   */
  protected $storageTypes = [];

  /**
   * {@inheritdoc}
   */
  public function getStorage($entity_type, $entity_class) {
    if (!isset($this->storageTypes[$entity_type])) {
      $this->storageTypes[$entity_type] = new AMNetEntityStorageBase($entity_type, $entity_class);
    }
    return $this->storageTypes[$entity_type];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeFromClass($class_name) {
    $re = '/(?#! splitCamelCase Rev:20140412)
    # Split camelCase "words". Two global alternatives. Either g1of2:
      (?<=[a-z])      # Position is after a lowercase,
      (?=[A-Z])       # and before an uppercase letter.
    | (?<=[A-Z])      # Or g2of2; Position is after uppercase,
      (?=[A-Z][a-z])  # and before upper-then-lower case.
    /x';
    $elements = preg_split($re, $class_name);
    $items = array_map('strtolower', $elements);
    return implode('_', $items);
  }

}
