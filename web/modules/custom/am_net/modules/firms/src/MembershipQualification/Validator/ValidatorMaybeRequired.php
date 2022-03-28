<?php

namespace Drupal\am_net_firms\MembershipQualification\Validator;

/**
 * Class Validator Maybe Required.
 *
 * @package Drupal\am_net_firms\MembershipQualification\Validator
 */
class ValidatorMaybeRequired extends BaseValidator {

  /**
   * The reference field name.
   *
   * @var string
   */
  protected $referenceFieldName;

  /**
   * The reference field value.
   *
   * @var string
   */
  protected $referenceFieldValue;

  /**
   * List of values that are considered as empty.
   *
   * @var string
   */
  protected $emptyValues;

  /**
   * BaseValidator constructor.
   *
   * @param string $error_message
   *   Error message.
   * @param string $reference_field_name
   *   The reference field name.
   * @param string|array $reference_field_value
   *   The reference field value.
   * @param array $empty_values
   *   List of values that are considered as empty.
   */
  public function __construct($error_message, $reference_field_name = '', $reference_field_value = NULL, array $empty_values = []) {
    parent::__construct($error_message);
    $this->referenceFieldName = $reference_field_name;
    $this->referenceFieldValue = $reference_field_value;
    $this->emptyValues = $empty_values;
  }

  /**
   * {@inheritdoc}
   */
  public function validates($value, array $all_values = []) {
    if (empty($this->referenceFieldName) || empty($this->referenceFieldValue)) {
      return TRUE;
    }
    if (!isset($all_values[$this->referenceFieldName])) {
      return TRUE;
    }
    // Check if is necessary validate the field.
    $values = is_array($this->referenceFieldValue) ? $this->referenceFieldValue : [$this->referenceFieldValue];
    $validate = FALSE;
    foreach ($values as $delta => $val) {
      if ($all_values[$this->referenceFieldName] == $val) {
        $validate = TRUE;
      }
    }
    if (!$validate) {
      return TRUE;
    }
    // Check if no is empty.
    if (empty($value)) {
      return FALSE;
    }
    if (empty($this->emptyValues)) {
      return TRUE;
    }
    // Check that the value is not part of the List of values that
    // are considered as empty.
    return !in_array($value, $this->emptyValues);
  }

}
