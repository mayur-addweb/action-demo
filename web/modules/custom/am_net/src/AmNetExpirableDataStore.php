<?php

namespace Drupal\am_net;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the 'AmNet Expirable Data Store' service implementation.
 */
class AmNetExpirableDataStore {

  /**
   * The 'KeyValue Store Expirable' service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $store;

  /**
   * The storage data.
   *
   * @var array
   */
  protected $data;

  /**
   * Constructs a 'AmNet Expirable Data Store' service.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface|null $cache_factory
   *   The Expirable cache factory.
   */
  public function __construct(KeyValueExpirableFactoryInterface $cache_factory = NULL) {
    $this->store = $cache_factory->get('amnet.data');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue.expirable')
    );
  }

  /**
   * Deletes multiple items from the cache.
   *
   * @param array $keys
   *   A list of item names to delete.
   */
  public function clearCache(array $keys = []) {
    if (empty($keys)) {
      return;
    }
    // Delete from the static cache.
    foreach ($keys as $key => $delta) {
      unset($this->data[$delta]);
    }
    // Delete from the database.
    $this->store->deleteMultiple($keys);
  }

  /**
   * Get AM.Net Data.
   *
   * @param string $delta
   *   The key used to storage the variable.
   *
   * @return mixed
   *   The data, otherwise null.
   */
  public function getData($delta = NULL) {
    if (empty($delta)) {
      return NULL;
    };
    // Get the data from the static cache.
    $data = $this->data[$delta] ?? NULL;
    if (empty($data)) {
      // Get the data from the database.
      $this->data[$delta] = $this->store->get($delta);
    }
    return $this->data[$delta];
  }

  /**
   * Set the AM.Net Data.
   *
   * @param string $delta
   *   The key used to storage the variable.
   * @param mixed $value
   *   The value to be temporary saved in the cache.
   * @param string $expire_time
   *   The expiration datetime.
   */
  public function setData($delta = NULL, $value = NULL, $expire_time = NULL) {
    if (empty($delta)) {
      return;
    }
    $this->store->setWithExpire($delta, $value, $expire_time);
  }

}
