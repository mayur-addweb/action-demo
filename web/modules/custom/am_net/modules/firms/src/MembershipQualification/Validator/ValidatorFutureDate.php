<?php

namespace Drupal\am_net_firms\MembershipQualification\Validator;

/**
 * Class Validator Future Date.
 *
 * @package Drupal\am_net_firms\MembershipQualification\Validator
 */
class ValidatorFutureDate extends BaseValidator {

  /**
   * {@inheritdoc}
   */
  public function validates($date, array $all_values = []) {
    if (empty($date)) {
      return TRUE;
    }
    return !(strtotime($date) < strtotime('now'));
  }

}
