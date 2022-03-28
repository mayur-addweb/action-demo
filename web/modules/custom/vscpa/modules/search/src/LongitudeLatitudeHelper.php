<?php

namespace Drupal\vscpa_search;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * A Helper class to handle PostCode or ZipCode to Lat/Long conversion.
 */
class LongitudeLatitudeHelper {

  /**
   * The memory cache.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $memoryCache;

  /**
   * The array of pair(Lat/long) indexed by zip code.
   *
   * @var array
   */
  protected $pairs = [];

  /**
   * Constructs an Helper instance.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface|null $cache_factory
   *   The memory cache factory.
   */
  public function __construct(KeyValueFactoryInterface $cache_factory = NULL) {
    $this->memoryCache = $cache_factory->get('amnet.zip.lat_long.pair');
  }

  /**
   * Find Longitude And Latitude Of PostCode or ZipCode Using Google Maps API.
   *
   * @param string $code
   *   The base zip code.
   *
   * @return array|null
   *   An Array with the pair Longitude And Latitude, otherwise NULL.
   */
  public function getLatLong($code = NULL) {
    if (empty($code)) {
      return NULL;
    }
    if (!is_numeric($code)) {
      return NULL;
    }
    $code = trim($code);
    $pair = $this->getFromStaticCache($code);
    if (empty($pair)) {
      // Load Lat/long Pair from Google Maps API.
      $queried_pair = $this->doLoad($code);
    }
    if (!empty($queried_pair)) {
      $pair = $queried_pair;
      // Add Pair to the cache.
      $this->setStaticCache($pair, $code);
    }
    return $pair;
  }

  /**
   * Performs storage-specific loading of the Lat/Long pair.
   *
   * @param string $code
   *   The base zip code.
   *
   * @return array|null
   *   An Array with the pair Longitude And Latitude, otherwise NULL.
   */
  public function doLoad($code = NULL) {
    $mapsApiKey = 'AIzaSyAgiZcbeRdmMaQjSnOAM5LG5SZTgjuaNgw';
    $query = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($code) . "&sensor=false&output=json&key=" . $mapsApiKey;
    $data = file_get_contents($query);
    $long = NULL;
    $lat = NULL;
    $log = NULL;
    if ($data) {
      $log = $data;
      $data = json_decode($data);
      $long = $data->results[0]->geometry->location->lng ?? NULL;
      $lat = $data->results[0]->geometry->location->lat ?? NULL;
    }
    if (empty($long) || empty($lat)) {
      $message = 'Failing to get Lat/lng pair from zip code ' . $code . ' | Error Detail: ' . $log;
      \Drupal::logger('vscpa_search')->alert($message);
      return NULL;
    }
    return ['Latitude' => $lat, 'Longitude' => $long];
  }

  /**
   * Builds the cache ID for the passed in Zip code.
   *
   * @param string $code
   *   The base zip code.
   *
   * @return string|null
   *   The unique Key related to the given entity Zip code.
   */
  public function buildCacheId($code = NULL) {
    if (empty($code)) {
      return NULL;
    }
    return 'lat_long_pair.' . $code;
  }

  /**
   * Gets Lat/Long pair from the static cache.
   *
   * @param string $code
   *   The base zip code.
   *
   * @return array|null
   *   Array of entity from the entity cache.
   */
  protected function getFromStaticCache($code = NULL) {
    $pair = NULL;
    $cache_id = $this->buildCacheId($code);
    if (isset($this->pairs[$cache_id])) {
      $pair = $this->pairs[$cache_id];
    }
    elseif ($cached = $this->memoryCache->get($cache_id, FALSE)) {
      $pair = $cached;
      $this->pairs[$cache_id] = $pair;
    }
    return $pair;
  }

  /**
   * Stores Lat/Long Pair in the static entity cache.
   *
   * @param array $pair
   *   The Lat/Long pair to store in the cache.
   * @param string $code
   *   The base zip code.
   */
  protected function setStaticCache(array $pair = [], $code = NULL) {
    $cache_id = $this->buildCacheId($code);
    $this->memoryCache->set($cache_id, $pair);
    $this->pairs[$cache_id] = $pair;
  }

}
