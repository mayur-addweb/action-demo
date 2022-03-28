<?php

namespace Drupal\am_net_firms\MembershipQualification\Validator;

/**
 * Class ValidatorRequired.
 *
 * @package Drupal\am_net_firms\MembershipQualification\Validator
 */
class ValidatorRequired extends BaseValidator {

  /**
   * {@inheritdoc}
   */
  public function validates($value, array $all_values = []) {
    return is_array($value) ? !empty(array_filter($value)) : !empty($value);
  }

}
