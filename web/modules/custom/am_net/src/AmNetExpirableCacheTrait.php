<?php

namespace Drupal\am_net;

/**
 * Provides methods for getting name Expirable cache service.
 */
trait AmNetExpirableCacheTrait {

  /**
   * The 'AmNet Expirable Data Store' service.
   *
   * @var \Drupal\am_net\AmNetExpirableDataStore
   */
  protected $cache = NULL;

  /**
   * Get Data from the cache.
   *
   * @param string $store_key
   *   The key used to storage the variable.
   *
   * @return mixed
   *   The data, otherwise null.
   */
  public function cacheGet($store_key = NULL) {
    return $this->getCache()->getData($store_key);
  }

  /**
   * The 'AmNet Expirable Data Store' service.
   *
   * @return \Drupal\am_net\AmNetExpirableDataStore
   *   The 'AmNet Expirable Data Store' service.
   */
  public function getCache() {
    if (!$this->cache) {
      $this->cache = \Drupal::service('amnet.expirable.store');
    }
    return $this->cache;
  }

  /**
   * Set Cache Data.
   *
   * @param string $store_key
   *   The key used to storage the variable.
   * @param mixed $value
   *   The value to be temporary saved in the cache.
   * @param string $expire_time
   *   The expiration datetime.
   */
  public function cacheSet($store_key = NULL, $value = NULL, $expire_time = NULL) {
    $this->getCache()->setData($store_key, $value, $expire_time);
  }

}
