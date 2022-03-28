<?php

namespace Drupal\vscpa_sso\Entity;

/**
 * Defines object that represent Gluu user profile or person entity.
 */
class Name {

  /**
   * The formatted.
   *
   * @var string
   */
  public $formatted;

  /**
   * The given name.
   *
   * @var string
   */
  public $givenName;

  /**
   * The family name.
   *
   * @var string
   */
  public $familyName;

  /**
   * The middle name.
   *
   * @var string
   */
  public $middleName;

  /**
   * The honorific prefix.
   *
   * @var string
   */
  public $honorificPrefix;

  /**
   * The honorific suffix.
   *
   * @var string
   */
  public $honorificSuffix;

  /**
   * Get the given name.
   *
   * @return string
   *   The given name.
   */
  public function getGivenName() {
    return $this->givenName;
  }

  /**
   * Get the family name.
   *
   * @return string
   *   The family name.
   */
  public function getFamilyName() {
    return $this->familyName;
  }

  /**
   * Get the middle name.
   *
   * @return string
   *   The middle name.
   */
  public function getMiddleName() {
    return $this->middleName;
  }

  /**
   * Get the honorific prefix.
   *
   * @return string
   *   The honorific prefix.
   */
  public function getHonorificPrefix() {
    return $this->honorificPrefix;
  }

  /**
   * Get the honorific suffix.
   *
   * @return string
   *   The honorific suffix.
   */
  public function getHonorificSuffix() {
    return $this->honorificSuffix;
  }

  /**
   * Set the given name.
   *
   * @param string $givenName
   *   The given name.
   *
   * @return \Drupal\vscpa_sso\Entity\Name
   *   The Name Instance.
   */
  public function setGivenName($givenName) {
    $this->givenName = $givenName;
    return $this;
  }

  /**
   * Set the family name.
   *
   * @param string $familyName
   *   The family name.
   *
   * @return \Drupal\vscpa_sso\Entity\Name
   *   The Name Instance.
   */
  public function setFamilyName($familyName) {
    $this->familyName = $familyName;
    return $this;
  }

  /**
   * Set the middle name.
   *
   * @param string $middleName
   *   The middle name.
   *
   * @return \Drupal\vscpa_sso\Entity\Name
   *   The Name Instance.
   */
  public function setMiddleName($middleName) {
    $this->middleName = $middleName;
    return $this;
  }

  /**
   * Set the honorific prefix.
   *
   * @param string $honorificPrefix
   *   The honorific prefix.
   *
   * @return \Drupal\vscpa_sso\Entity\Name
   *   The Name Instance.
   */
  public function setHonorificPrefix($honorificPrefix) {
    $this->honorificPrefix = $honorificPrefix;
    return $this;
  }

  /**
   * Set the honorific suffix.
   *
   * @param string $honorificSuffix
   *   The honorific suffix.
   *
   * @return \Drupal\vscpa_sso\Entity\Name
   *   The Name Instance.
   */
  public function setHonorificSuffix($honorificSuffix) {
    $this->honorificSuffix = $honorificSuffix;
    return $this;
  }

  /**
   * Create a new Gluu Name from json string data.
   *
   * @param array $userData
   *   The array of user data.
   *
   * @return \Drupal\vscpa_sso\Entity\Name|null
   *   The Name Instance.
   */
  public static function map(array $userData = []) {
    if (empty($userData)) {
      return NULL;
    }
    $name_obj = new Name();
    foreach ($userData as $name => $data) {
      $name_obj->{$name} = $data;
    }
    return $name_obj;
  }

  /**
   * Convert the Gluu Name into an its array representation.
   *
   * @param bool $full
   *   The Full flag.
   *
   * @return array
   *   The array representation of the Gluu Name object.
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
