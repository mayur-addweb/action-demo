<?php

namespace Drupal\am_net;

/**
 * A base AM.net entity type Context class used to provide entity metadata info.
 */
class AMNetEntityTypeContext {


  /**
   * The AM.net order data.
   *
   * @var array
   */
  protected $data = [
    'type' => NULL,
    'is_statically_cacheable' => TRUE,
  ];

  /**
   * Constructs the AM.net entity type context object.
   *
   * @param array $data
   *   The metadata info for the Entity Type.
   */
  public function __construct(array $data = []) {
    $this->data = $data;
  }

  /**
   * Indicates whether entities should be statically cached.
   *
   * @return bool
   *   TRUE if static caching should be used; FALSE otherwise.
   */
  public function isStaticallyCacheable() {
    return $this->data['is_statically_cacheable'] ?? FALSE;
  }

  /**
   * Get Information about the entity type.
   *
   * @return string|null
   *   The entity type.
   */
  public function getEntityType() {
    return $this->data['type'] ?? NULL;
  }

}
