<?php

namespace Drupal\am_net;

/**
 * Common class for handle individual AM.Net record data.
 */
class AmNetData implements AmNetDataInterface {

  /**
   * The target ID.
   *
   * @var string|null
   */
  protected $targetId = NULL;

  /**
   * The owner ID.
   *
   * @var string|null
   */
  protected $ownerId = NULL;

  /**
   * The AM.Net data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Class constructor for the form.
   *
   * @param string $targetId
   *   The target ID.
   * @param string $ownerId
   *   The owner ID.
   * @param array $data
   *   The AM.Net data.
   */
  public function __construct($targetId = NULL, $ownerId = NULL, array $data = []) {
    $this->targetId = $targetId;
    $this->ownerId = $ownerId;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetId() {
    return $this->targetId;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->ownerId;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

}
