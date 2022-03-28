<?php

namespace Drupal\am_net;

/**
 * AM.Net Cache Helper class.
 */
class AmNetCacheHelper {

  /**
   * Clear local cache associated to a given name ID.
   *
   * @param string $name_id
   *   The given name ID.
   *
   * @return bool
   *   TRUE if the operation was completed, otherwise FALSE.
   */
  public static function clearNameCache($name_id = NULL) {
    if (empty($name_id)) {
      return FALSE;
    }
    // Get all the cache names associated with the given name ID.
    $cache_key = '%name_id:' . $name_id;
    $keys = self::getCacheKeys($cache_key);
    if (empty($keys)) {
      return TRUE;
    }
    // Clear the cache.
    \Drupal::service('amnet.expirable.store')->clearCache($keys);
    return TRUE;
  }

  /**
   * Clear local cache associated to a given event registrations.
   *
   * @param string $cache_key
   *   The cache key string.
   *
   * @return bool
   *   TRUE if the operation was completed, otherwise FALSE.
   */
  public static function getCacheKeys($cache_key = NULL) {
    if (empty($cache_key)) {
      return FALSE;
    }
    // Get all the cache names associated with the given name ID.
    $database = \Drupal::database();
    $query = $database->select('key_value_expire', 'cache');
    $query->fields('cache', ['name']);
    $query->condition('collection', 'amnet.data');
    $query->condition('name', $cache_key, 'LIKE');
    $keys = $query->execute()->fetchAllKeyed(0, 0);
    return $keys;
  }

}
