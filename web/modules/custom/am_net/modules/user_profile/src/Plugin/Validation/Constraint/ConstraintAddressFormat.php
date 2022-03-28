<?php

namespace Drupal\am_net_user_profile\Plugin\Validation\Constraint;

use Drupal\address\Plugin\Validation\Constraint\AddressFormatConstraint as AddressFormatConstraintBase;

/**
 * Address format constraint.
 */
class ConstraintAddressFormat extends AddressFormatConstraintBase {

  /**
   * The blank message.
   *
   * @var string
   */
  public $blankMessage = '@name field must be blank.';

  /**
   * The not blank message.
   *
   * @var string
   */
  public $notBlankMessage = '@name field is required.';

  /**
   * The invalid message.
   *
   * @var string
   */
  public $invalidMessage = 'Please enter a ZIP code associated with the selected state.';

}
