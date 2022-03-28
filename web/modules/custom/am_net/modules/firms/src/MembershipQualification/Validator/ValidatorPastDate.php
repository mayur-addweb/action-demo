<?php

namespace Drupal\am_net_firms\MembershipQualification\Validator;

/**
 * Class Validator Past Date.
 *
 * @package Drupal\am_net_firms\MembershipQualification\Validator
 */
class ValidatorPastDate extends BaseValidator {

  /**
   * {@inheritdoc}
   */
  public function validates($date, array $all_values = []) {
    if (empty($date)) {
      return TRUE;
    }
    return (strtotime($date) < strtotime('now'));
  }

}
