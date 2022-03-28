<?php

namespace Drupal\am_net_firms\MembershipQualification\Validator;

/**
 * Interface ValidatorInterface.
 *
 * @package Drupal\am_net_firms\MembershipQualification\Validator
 */
interface ValidatorInterface {

  /**
   * Returns bool indicating if validation is ok.
   */
  public function validates($value, array $all_values = []);

  /**
   * Returns error message.
   */
  public function getErrorMessage();

}
