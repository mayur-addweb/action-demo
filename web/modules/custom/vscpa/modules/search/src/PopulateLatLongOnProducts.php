<?php

namespace Drupal\vscpa_search;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Class to Populate Lat/Long Pair on products.
 */
class PopulateLatLongOnProducts {

  /**
   * The AM.net REST API client.
   *
   * @var \Drupal\am_net\AssociationManagementClient
   */
  protected $client;

  /**
   * The memory cache.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $memoryCache;

  /**
   * The array of pair(Lat/long) indexed by firm code.
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
    $this->memoryCache = $cache_factory->get('amnet.firm.lat_long');
  }

  /**
   * Handle Fetch Lat/Long Pair on products from AM.net.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $entity
   *   The base product.
   * @param bool $force_update
   *   Check if the update on the field Lat/long should be forced.
   *
   * @return bool
   *   TRUE if the product was changes, otherwise FALSE.
   */
  public function populate(ProductInterface &$entity = NULL, $force_update = FALSE) {
    if ($entity->bundle() != 'cpe_event') {
      return FALSE;
    }
    $field = $entity->get('field_search_index_geolocation');
    if (!$field->isEmpty() && !$force_update) {
      return FALSE;
    }
    $location = $entity->get('field_firm');
    if ($location->isEmpty()) {
      // Entity has no location related.
      return FALSE;
    }
    $term_id = $location->getString();
    if (empty($term_id)) {
      // Entity has no location related.
      return FALSE;
    }
    $am_net_id = $this->getFirmAmNetId($term_id);
    if (empty($am_net_id)) {
      // Entity has no location related.
      return FALSE;
    }
    // The value is empty Fetch Lat/Long Pair on products from AM.net.
    $pair = $this->getFromStaticCache($am_net_id);
    if (empty($pair)) {
      // Load Lat/long Pair from Google Maps API.
      $queried_pair = $this->doLoad($am_net_id);
    }
    if (!empty($queried_pair)) {
      $pair = $queried_pair;
      // Add Pair to the cache.
      $this->setStaticCache($pair, $am_net_id);
    }
    // Set the value into the entity.
    if (empty($pair)) {
      // Entity has no location related.
      return FALSE;
    }
    $lat = $pair['Latitude'] ?? NULL;
    $lng = $pair['Longitude'] ?? NULL;
    $entity->set('field_search_index_geolocation', [
      'lat' => $lat,
      'lng' => $lng,
    ]);
    // Field changed.
    return TRUE;
  }

  /**
   * Get the AM.net ID related to a firm term ID.
   *
   * @param string $term_id
   *   The Firm term ID.
   *
   * @return string|null
   *   The firm AM.net ID otherwise NULL.
   */
  public function getFirmAmNetId($term_id = NULL) {
    if (empty($term_id)) {
      return NULL;
    }
    $term_id = trim($term_id);
    $database = \Drupal::database();
    $query = $database->select('taxonomy_term__field_amnet_id', 'field_amnet');
    $query->fields('field_amnet', ['field_amnet_id_value']);
    $query->condition('field_amnet.entity_id', $term_id);
    $result = $query->execute();
    return $result->fetchField(0);
  }

  /**
   * Performs storage-specific loading of the Lat/Long pair.
   *
   * @param string $am_net_id
   *   The base Firm AMNet ID.
   *
   * @return array|null
   *   An Array with the pair Longitude And Latitude, otherwise NULL.
   */
  public function doLoad($am_net_id = NULL) {
    $client = $this->getAmNetClient();
    $levels = $client->get('/Firm', [
      'firm' => $am_net_id,
    ]);
    if ($levels->hasError()) {
      return NULL;
    }
    $lat = NULL;
    $lng = NULL;
    if ($result = $levels->getResult()) {
      $lat = $result['Address']['StreetZipLat'] ?? NULL;
      $lng = $result['Address']['StreetZipLong'] ?? NULL;
    }
    if (empty($lat) || empty($lng)) {
      return NULL;
    }
    return ['Latitude' => $lat, 'Longitude' => $lng];
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetClient() {
    if (!$this->client) {
      $this->client = \Drupal::service('am_net.client');
    }
    return $this->client;
  }

  /**
   * Builds the cache ID for the passed in Zip code.
   *
   * @param string $am_net_id
   *   The base Firm AMNet ID.
   *
   * @return string|null
   *   The unique Key related to the given entity Zip code.
   */
  public function buildCacheId($am_net_id = NULL) {
    if (empty($am_net_id)) {
      return NULL;
    }
    return 'firm_lat_long_pair.' . $am_net_id;
  }

  /**
   * Gets Lat/Long pair from the static cache.
   *
   * @param string $am_net_id
   *   The base Firm AMNet ID.
   *
   * @return array|null
   *   Array of entity from the entity cache.
   */
  protected function getFromStaticCache($am_net_id = NULL) {
    $pair = NULL;
    $cache_id = $this->buildCacheId($am_net_id);
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
   * @param string $am_net_id
   *   The base Firm AMNet ID.
   */
  protected function setStaticCache(array $pair = [], $am_net_id = NULL) {
    $cache_id = $this->buildCacheId($am_net_id);
    $this->memoryCache->set($cache_id, $pair);
    $this->pairs[$cache_id] = $pair;
  }

}
