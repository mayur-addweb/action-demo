<?php

namespace Drupal\am_net\Entity;

/**
 * Defines a base entity class for AM.net objects.
 */
abstract class AMNetEntity implements AMNetEntityInterface {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Boolean indicating whether the AM.net entity should be forced to be new.
   *
   * @var bool
   */
  protected $enforceIsNew;

  /**
   * The AM.net API endpoint for handle operations over the concrete entity.
   *
   * @var string
   */
  protected static $apiEndPoint;

  /**
   * The format used for Serializes and De-serializes data into the given type.
   *
   * @var string
   */
  protected static $format = 'json';

  /**
   * Constructs an AM.net Entity object.
   *
   * @param array $values
   *   An array of values to set, keyed by property name. If the entity type
   *   has bundles, the bundle key has to be specified.
   */
  public function __construct(array $values = []) {
    if (!empty($values)) {
      // Set initial values.
      foreach ($values as $key => $value) {
        if (isset($this->$key)) {
          $this->$key = $value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return isset($this->id) ? $this->id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function formatId() {
    $_id = $this->id();
    if (!empty($_id)) {
      $_id = strtolower(trim($_id));
      $this->setId($_id);
    }
    return isset($this->id) ? $this->id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || !$this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function enforceIsNew($value = TRUE) {
    $this->enforceIsNew = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return isset($this->label) ? $this->label : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    $entity_manager = \Drupal::service('am_net.entity_manager');
    $class_name = get_called_class();
    return $entity_manager->getStorage($entity_manager->getEntityTypeFromClass($class_name), $class_name)->create($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id = NULL) {
    $entity_manager = \Drupal::service('am_net.entity_manager');
    $class_name = get_called_class();
    return $entity_manager->getStorage($entity_manager->getEntityTypeFromClass($class_name), $class_name)->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(array $ids = NULL) {
    $entity_manager = \Drupal::service('am_net.entity_manager');
    $class_name = get_called_class();
    return $entity_manager->getStorage($entity_manager->getEntityTypeFromClass($class_name), $class_name)->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $entity_manager = \Drupal::service('am_net.entity_manager');
    $class_name = get_called_class();
    return $entity_manager->getStorage($entity_manager->getEntityTypeFromClass($class_name), $class_name)->save($this);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->isNew()) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFormat() {
    return self::$format;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdKey() {
    return 'id';
  }

  /**
   * {@inheritdoc}
   */
  public static function setFormat($format) {
    self::$format = $format;
  }

  /**
   * {@inheritdoc}
   */
  public static function getApiEndPoint() {
    return self::$apiEndPoint;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCreateRequestType() {
    return 'put';
  }

  /**
   * {@inheritdoc}
   */
  public static function getUpdateRequestType() {
    return 'post';
  }

  /**
   * {@inheritdoc}
   */
  public static function getCreateEntityApiEndPoint() {
    return self::$apiEndPoint;
  }

  /**
   * {@inheritdoc}
   */
  public static function getUpdateEntityApiEndPoint() {
    return self::$apiEndPoint;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLoadByPropertiesObjectIdentifierKey() {
    return self::getIdKey();
  }

  /**
   * {@inheritdoc}
   */
  public static function getLoadByPropertiesApiEndpoint() {
    return NULL;
  }

  /**
   * Set the AM.net API endpoint.
   *
   * Set the AM.net API endpoint in charge of handle operations
   * over the concrete entity.
   *
   * @param string $apiEndPoint
   *   The API endpoint.
   */
  public function setApiEndPoint($apiEndPoint) {
    $this->apiEndPoint = $apiEndPoint;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(AMNetEntityStorageInterface $storage) {
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(AMNetEntityStorageInterface $storage, $update = TRUE) {
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(AMNetEntityStorageInterface $storage, array &$values) {
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(AMNetEntityStorageInterface $storage) {
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(AMNetEntityStorageInterface $storage, array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(AMNetEntityStorageInterface $storage, array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(AMNetEntityStorageInterface $storage, array &$entities) {
  }

  /**
   * Gets the AM.net entity manager.
   *
   * @return \Drupal\am_net\Entity\AMNetEntityManagerInterface
   *   The AM.net entity manager instance from the container.
   */
  protected function entityManager() {
    return \Drupal::service('am_net.entity_manager');
  }

}
