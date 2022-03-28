<?php

namespace Drupal\vscpa_sso\Entity;

/**
 * Defines object that represent Gluu user group.
 */
class Group {

  /**
   * The schemas.
   *
   * @var array
   */
  public $schemas = [];

  /**
   * The ID.
   *
   * @var string
   */
  public $id;

  /**
   * The display name.
   *
   * @var string
   */
  public $displayName;

  /**
   * The members list.
   *
   * @var array
   */
  public $members;

  /**
   * The Gluu Group meta.
   *
   * @var string
   */
  public $meta;

  /**
   * The users list.
   *
   * @var array
   */
  public $users;

  /**
   * The value.
   *
   * @var string
   */
  public $value;

  /**
   * Create a new Gluu Group from array data.
   *
   * @param array $userData
   *   The array of user data.
   *
   * @return \Drupal\vscpa_sso\Entity\Group|null
   *   The Email Instance.
   */
  public static function map(array $userData = []) {
    if (empty($userData)) {
      return NULL;
    }
    $name_obj = new Group();
    foreach ($userData as $name => $data) {
      $name_obj->{$name} = $data;
    }
    return $name_obj;
  }

  /**
   * Convert the Gluu Group into an its array representation.
   *
   * @param bool $full
   *   The Full flag.
   *
   * @return array
   *   The array representation of the Gluu Group object.
   */
  public function arrayFromObject($full = TRUE) {
    $array_data = [];
    $reflector = new \ReflectionClass($this);
    $properties = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
    foreach ($properties as $name) {
      $name = $name->name;
      if ($full || $this->{$name}) {
        $array_data[$name] = $this->{$name};
      }
    }
    return $array_data;
  }

}
