<?php

namespace Drupal\am_net_firms\MembershipQualification\Validator;

/**
 * Class BaseValidator.
 *
 * @package Drupal\am_net_firms\MembershipQualification\Validator
 */
abstract class BaseValidator implements ValidatorInterface {

  /**
   * The custom error message.
   *
   * @var string
   */
  protected $errorMessage;

  /**
   * BaseValidator constructor.
   *
   * @param string $error_message
   *   Error message.
   */
  public function __construct($error_message) {
    $this->errorMessage = $error_message;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

}
