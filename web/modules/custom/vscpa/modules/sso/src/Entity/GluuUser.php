<?php

namespace Drupal\vscpa_sso\Entity;

use Drupal\vscpa_sso\Exception\GluuUserException;

/**
 * Defines object that represent Gluu user profile or person entity.
 */
class GluuUser {

  /**
   * Gluu User Schema.
   */
  const USER_SCHEMA = 'urn:ietf:params:scim:schemas:core:2.0:User';

  /**
   * Gluu User extension Schema.
   */
  const USER_EXTENSION_SCHEMA = 'urn:ietf:params:scim:schemas:extension:gluu:2.0:User';

  /**
   * The ID.
   *
   * @var string
   */
  public $id;

  /**
   * The schemas.
   *
   * @var array
   */
  public $schemas = [];

  /**
   * The external Id.
   *
   * @var string
   */
  public $externalId;

  /**
   * The username.
   *
   * @var string
   */
  public $userName;

  /**
   * The name.
   *
   * @var string
   */
  public $name;

  /**
   * The display-name.
   *
   * @var string
   */
  public $displayName;

  /**
   * The nick-name.
   *
   * @var string
   */
  public $nickName;

  /**
   * The profile-url.
   *
   * @var string
   */
  public $profileUrl;

  /**
   * The emails.
   *
   * @var array
   */
  public $emails = [];

  /**
   * The addresses.
   *
   * @var string
   */
  public $addresses;

  /**
   * The phone Numbers.
   *
   * @var array
   */
  public $phoneNumbers = [];

  /**
   * The ims.
   *
   * @var array
   */
  public $ims = [];

  /**
   * The user type.
   *
   * @var string
   */
  public $userType;

  /**
   * The title.
   *
   * @var string
   */
  public $title;

  /**
   * The preferred Language.
   *
   * @var string
   */
  public $preferredLanguage;

  /**
   * The locale.
   *
   * @var string
   */
  public $locale;

  /**
   * The user status.
   *
   * @var bool
   */
  public $active;

  /**
   * The user password.
   *
   * @var string
   */
  public $password;

  /**
   * The user roles.
   *
   * @var string
   */
  public $roles = [];

  /**
   * The user entitlements.
   *
   * @var array
   */
  public $entitlements = [];

  /**
   * The Gluu User extension.
   *
   * @var string
   */
  public $extensionGluuUser;

  /**
   * The Gluu User meta.
   *
   * @var string
   */
  public $meta;

  /**
   * The Gluu User groups.
   *
   * @var array
   */
  public $groups;

  /**
   * Decode Gluu User from Json String.
   *
   * @param string $jsonString
   *   The json string representation of the Gluu User.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser
   *   The Gluu User.
   */
  public static function fromJson($jsonString) {
    $userData = json_decode($jsonString, TRUE);
    if (NULL === $userData && JSON_ERROR_NONE !== json_last_error()) {
      $errorMsg = function_exists('json_last_error_msg') ? json_last_error_msg() : json_last_error();
      throw new GluuUserException(sprintf('unable to decode JSON from storage: %s', $errorMsg));
    }
    return self::map($userData);
  }

  /**
   * Create a new Gluu User from array of user data.
   *
   * @param array $userData
   *   The array of user data.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser
   *   The Gluu User.
   */
  public static function map(array $userData = []) {
    $user = new GluuUser();
    if (!empty($userData)) {
      foreach ($userData as $name => $data) {
        if ($user->isSubObject($name)) {
          $user->{$name} = $user->{'get' . ucfirst($name) . 'Object'}($data);
        }
        else {
          $user->{$name} = $data;
        }
      }
    }
    return $user;
  }

  /**
   * Check if the griven property represent an object.
   *
   * @param string $name
   *   The user property.
   *
   * @return bool
   *   TRUE if the given property represent and Object otherwise FALSE.
   */
  private function isSubObject($name) {
    return in_array($name, ['emails', 'name', 'groups']);
  }

  /**
   * Encode the object to Json.
   *
   * @return string
   *   The Json String representation of the object.
   */
  public function json() {
    return json_encode($this->arrayFromObject());
  }

  /**
   * Convert the Gluu user into an its array representation.
   *
   * @param bool $full
   *   The Full flag.
   *
   * @return array
   *   The array representation of the Gluu User object.
   */
  public function arrayFromObject($full = TRUE) {
    $reflector = new \ReflectionClass($this);
    $properties = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
    if (empty($properties)) {
      return [];
    }
    $array_data = [];
    foreach ($properties as $name) {
      $name = $name->name;
      if ($this->isSubObject($name)) {
        $array_data[$name] = $this->{'get' . ucfirst($name) . 'Array'}($full);
      }
      elseif ($full || $this->{$name}) {
        $array_data[$name] = $this->{$name};
      }
    }
    $array_data['urn:ietf:params:scim:schemas:extension:gluu:2.0:User']['gluuStatus'] = TRUE;
    unset($array_data['meta']);
    unset($array_data['groups']);
    return $array_data;
  }

  /**
   * Get Gluu name object representation.
   *
   * @param string $name
   *   The name.
   *
   * @return Name
   *   The Name object.
   */
  private function getNameObject($name) {
    return Name::map($name);
  }

  /**
   * Get Gluu emails object representations.
   *
   * @param array $emails
   *   The name.
   *
   * @return array
   *   The list of emails objects.
   */
  private function getEmailsObject(array $emails = []) {
    if (empty($emails)) {
      return [];
    }
    $emails_objects = [];
    foreach ($emails as $email) {
      $emails_objects[] = Email::map($email);
    }
    return $emails_objects;
  }

  /**
   * Get Gluu group object representations.
   *
   * @param array $groups
   *   The list of groups.
   *
   * @return array
   *   The list of groups objects.
   */
  private function getGroupsObject(array $groups = []) {
    if (empty($groups)) {
      return [];
    }
    $return = [];
    foreach ($groups as $group) {
      $return[] = Group::map($group);
    }
    return $return;
  }

  /**
   * Get Gluu name array representations.
   *
   * @param bool $full
   *   The flag full.
   *
   * @return mixed
   *   The list of groups objects.
   */
  private function getNameArray($full) {
    if ($this->name instanceof Name) {
      return $this->name->arrayFromObject($full);
    }
    return $this->name;
  }

  /**
   * Get Gluu Emails array representations.
   *
   * @param bool $full
   *   The flag full.
   *
   * @return array
   *   The list of emails objects.
   */
  private function getEmailsArray($full) {
    if (empty($this->emails) || !is_array($this->emails)) {
      return [];
    }
    $emails = [];
    foreach ($this->emails as $email) {
      if ($email instanceof Email) {
        $emails[] = $email->arrayFromObject($full);
      }
      else {
        $emails[] = $email;
      }
    }
    return $emails;
  }

  /**
   * Get Gluu groups array representations.
   *
   * @param bool $full
   *   The flag full.
   *
   * @return array
   *   The list of groups objects.
   */
  private function getGroupsArray($full) {
    if (empty($this->groups) || !is_array($this->groups)) {
      return [];
    }
    $groups = [];
    foreach ($this->groups as $key => $group) {
      if ($group instanceof Group) {
        $groups[] = ($group) ? $group->arrayFromObject($full) : NULL;
      }
      else {
        $groups[] = $group;
      }
    }
    return $groups;
  }

}
