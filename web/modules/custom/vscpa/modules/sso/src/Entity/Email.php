<?php

namespace Drupal\vscpa_sso\Entity;

/**
 * Defines object that represent Gluu user Email.
 */
class Email {

  /**
   * The email value.
   *
   * @var string
   */
  public $value;

  /**
   * The email type.
   *
   * @var string
   */
  public $type;

  /**
   * Flag primary.
   *
   * @var bool
   */
  public $primary;

  /**
   * Get value.
   *
   * @return string
   *   The value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Get type.
   *
   * @return string
   *   The type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Get primary value.
   *
   * @return bool
   *   The primary value.
   */
  public function getPrimary() {
    return $this->primary;
  }

  /**
   * Set value.
   *
   * @param string $value
   *   The value.
   *
   * @return \Drupal\vscpa_sso\Entity\Email
   *   The Email Instance.
   */
  public function setValue($value) {
    $this->value = $value;
    return $this;
  }

  /**
   * Set type.
   *
   * @param string $type
   *   The type.
   *
   * @return \Drupal\vscpa_sso\Entity\Email
   *   The Email Instance.
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * Set the primary value.
   *
   * @param bool $primary
   *   The primary.
   *
   * @return \Drupal\vscpa_sso\Entity\Email
   *   The Email Instance.
   */
  public function setPrimary($primary) {
    $this->primary = $primary;
    return $this;
  }

  /**
   * Create a new Gluu Email from json string data.
   *
   * @param array $userData
   *   The array of user data.
   *
   * @return \Drupal\vscpa_sso\Entity\Email|null
   *   The Email Instance.
   */
  public static function map(array $userData = []) {
    if (empty($userData)) {
      return NULL;
    }
    $email = new Email();
    foreach ($userData as $name => $data) {
      $email->{$name} = $data;
    }
    return $email;
  }

  /**
   * Convert the Gluu Email into an its array representation.
   *
   * @param bool $full
   *   The Full flag.
   *
   * @return array
   *   The array representation of the Gluu Email object.
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
