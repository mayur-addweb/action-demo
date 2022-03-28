<?php

namespace Drupal\vscpa_sso;

use Drupal\vscpa_sso\Entity\GluuUser;
use Drupal\vscpa_sso\Exception\GluuUserException;

/**
 * Defines object that represent Gluu Collection of entities.
 */
class Collection {

  /**
   * The total.
   *
   * @var int
   */
  public $totalResults;

  /**
   * The item per page.
   *
   * @var int
   */
  public $itemsPerPage;

  /**
   * The star index.
   *
   * @var int
   */
  public $startIndex;

  /**
   * The schemas.
   *
   * @var array
   */
  public $schemas = [];

  /**
   * The resources.
   *
   * @var mixed
   */
  public $resources;

  /**
   * The type of collection.
   *
   * @var string
   */
  private $type;

  /**
   * Constructs a new Collection object.
   *
   * @param array $collection
   *   The Collection metadata.
   * @param string $type
   *   The Collection type.
   *
   * @throws \Drupal\vscpa_sso\Exception\GluuUserException
   *   Thrown if the metadata parameters are not provided.
   */
  public function __construct(array $collection = [], $type = 'USER') {
    // Set the values.
    $this->type = $type;
    if (!empty($collection)) {
      foreach ($collection as $key => $data) {
        $this->{'set' . ucfirst($key)}($data);
      }
    }
    if (isset($collection['totalResults'])) {
      $this->totalResults = $collection['totalResults'];
    }
    if (isset($collection['itemsPerPage'])) {
      $this->itemsPerPage = $collection['itemsPerPage'];
    }
    if (isset($collection['startIndex'])) {
      $this->startIndex = $collection['startIndex'];
    }
    if (isset($collection['Resources'])) {
      $this->setResources($collection['Resources']);
    }
  }

  /**
   * Decode Collection from Json String.
   *
   * @param string $jsonString
   *   The json string representation of the Collection.
   * @param string $type
   *   The Collection type.
   *
   * @throws \Drupal\vscpa_sso\Exception\GluuUserException
   *   Thrown if the json string not was decoded properly.
   *
   * @return \Drupal\vscpa_sso\Collection
   *   The Collection instance.
   */
  public static function fromJson($jsonString, $type) {
    $resourcesData = json_decode($jsonString, TRUE);
    if (NULL === $resourcesData && JSON_ERROR_NONE !== json_last_error()) {
      $errorMsg = function_exists('json_last_error_msg') ? json_last_error_msg() : json_last_error();
      throw new GluuUserException(sprintf('unable to decode JSON from storage: %s', $errorMsg));
    }
    return new self($resourcesData, $type);
  }

  /**
   * Set Resources.
   *
   * @param array $resources
   *   The array of resources.
   */
  private function setResources(array $resources = []) {
    foreach ($resources as $resource) {
      if ($this->type == 'USER') {
        $this->resources[] = GluuUser::map($resource);
      }
    }
  }

  /**
   * Get Resources.
   *
   * @return array
   *   The Collection resources.
   */
  public function getResources() {
    return $this->resources;
  }

  /**
   * Get Total Results.
   *
   * @return int
   *   The Total Results.
   */
  public function getTotalResults() {
    return $this->totalResults;
  }

  /**
   * Get Items per page.
   *
   * @return int
   *   The items per page.
   */
  public function getItemsPerPage() {
    return $this->itemsPerPage;
  }

  /**
   * Get the start index.
   *
   * @return int
   *   The start index.
   */
  public function getStartIndex() {
    return $this->startIndex;
  }

  /**
   * Get schemas.
   *
   * @return array
   *   The Collection schemas.
   */
  public function getSchemas() {
    return $this->schemas;
  }

  /**
   * Set the total result.
   *
   * @param int $totalResults
   *   The total result.
   */
  public function setTotalResults($totalResults) {
    $this->totalResults = $totalResults;
  }

  /**
   * Set the item per page.
   *
   * @param int $itemsPerPage
   *   The item per page.
   */
  public function setItemsPerPage($itemsPerPage) {
    $this->itemsPerPage = $itemsPerPage;
  }

  /**
   * Set the start index.
   *
   * @param int $startIndex
   *   The start index.
   */
  public function setStartIndex($startIndex) {
    $this->startIndex = $startIndex;
  }

  /**
   * Set the schemas.
   *
   * @param array $schemas
   *   The schemas.
   */
  public function setSchemas(array $schemas = []) {
    $this->schemas = $schemas;
  }

}
