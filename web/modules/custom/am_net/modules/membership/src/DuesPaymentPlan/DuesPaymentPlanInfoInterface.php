<?php

namespace Drupal\am_net_membership\DuesPaymentPlan;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a common interface for Dues Payment Plan implementation.
 */
interface DuesPaymentPlanInfoInterface {

  /**
   * Constructs a new DuesPaymentPlan object from an array of values.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return static
   *   The DuesPaymentPlanInfo object.
   */
  public static function create(array $values = []);

  /**
   * Add Dues Payment Form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addDuesPaymentForm(array &$form, FormStateInterface &$form_state);

  /**
   * Set the Data.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   */
  public function setData(array $values = []);

  /**
   * Set the base membership fee.
   *
   * @param string $fee
   *   The base membership fee.
   */
  public function setBaseMembershipFee($fee = NULL);

  /**
   * Gets the base membership fee.
   *
   * @return string
   *   the base membership fee.
   */
  public function getBaseMembershipFee();

  /**
   * Set the flag: 'is membership application'.
   *
   * @param bool $value
   *   The flag value.
   */
  public function setMembershipApplication($value = FALSE);

  /**
   * Set the flag: 'is membership renewal'.
   *
   * @param bool $value
   *   The flag value.
   */
  public function setMembershipRenewal($value = FALSE);

  /**
   * Get the flag value: 'is membership application'.
   *
   * @return bool
   *   The flag value.
   */
  public function isMembershipApplication();

  /**
   * Get the flag the: 'is membership renewal'.
   *
   * @return bool
   *   The flag value.
   */
  public function isMembershipRenewal();

  /**
   * Set the flag: 'Enroll'.
   *
   * @param bool $value
   *   The flag value.
   */
  public function enroll($value = FALSE);

  /**
   * Get the flag value: 'Enroll'.
   *
   * @return bool
   *   The flag value.
   */
  public function isPlanActive();

  /**
   * Set end date of current fiscal year.
   *
   * @param string $date
   *   The end date of current fiscal year.
   */
  public function setEndDateOfCurrentFiscalYear($date = NULL);

  /**
   * Get end date of current fiscal year.
   *
   * @return string
   *   The end date of current fiscal year.
   */
  public function getEndDateOfCurrentFiscalYear();

  /**
   * Get the timestamp related to the end date of current fiscal year.
   *
   * @return int|null
   *   The end date of current fiscal year - timestamp.
   */
  public function getEndDateOfCurrentFiscalYearTimestamp();

  /**
   * Add contribution amount.
   *
   * @param string $amount
   *   The contribution amount.
   * @param string $type
   *   The contribution type.
   */
  public function addContributionAmount($amount = NULL, $type = NULL);

  /**
   * Get contribution amount.
   *
   * @param string $type
   *   The contribution type.
   *
   * @return \Drupal\commerce_price\Price
   *   The contribution rate.
   */
  public function getContributionAmount($type = NULL);

  /**
   * Get the Dues Payment amount.
   *
   * @return string
   *   The Dues Payment amount.
   */
  public function getDuesPayment();

  /**
   * Get the Adjusted Dues Payment amount.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The membership order item.
   *
   * @return string
   *   The Adjusted Dues Payment amount.
   */
  public function getAdjustedDuesPayment(OrderItemInterface $order_item = NULL);

  /**
   * Get monthly contribution rate.
   *
   * @param int $months
   *   The number of months.
   * @param string $type
   *   The contribution type.
   *
   * @return \Drupal\commerce_price\Price
   *   The Monthly Contribution rate.
   */
  public function getMonthlyContribution($months = NULL, $type = NULL);

  /**
   * Get the base membership price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The base membership price.
   */
  public function getMembershipPrice();

  /**
   * Get the first installment membership price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The first installment membership price.
   */
  public function getFirstInstallmentMembershipPrice();

  /**
   * Get the first installment contribution rate.
   *
   * @param string $type
   *   The contribution type.
   *
   * @return \Drupal\commerce_price\Price
   *   The contribution rate.
   */
  public function getFirstInstallmentMonthlyContribution($type = NULL);

  /**
   * Get the Balance.
   *
   * @return array
   *   The Dues Payment Plan Balance table.
   */
  public function getBalance();

  /**
   * Get the Plan Start Date.
   *
   * @return string|null
   *   The Dues Payment Plan Start Date.
   */
  public function getPlanStartDate($format = NULL);

  /**
   * Get the Plan End Date.
   *
   * @return string
   *   The Dues Payment Plan End Date.
   */
  public function getPlanEndDate();

  /**
   * Get the Plan End DateTime.
   *
   * @return string
   *   The Dues Payment Plan end DateTime.
   */
  public function getPlanEndDateTime();

  /**
   * Get the CCAmount value.
   *
   * @return string
   *   The CCAmount value.
   */
  public function getCcAmount();

  /**
   * Gets an array of all property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public function toArray();

  /**
   * Format a given Amount.
   *
   * @param string $amount
   *   The given amount.
   *
   * @return string
   *   The Formatted Amount, NULL otherwise.
   */
  public function formatAmount($amount = NULL);

  /**
   * Get the number of Months related to the Plan.
   *
   * @return int
   *   The Dues Payment Plan Months.
   */
  public function getPlanMonths();

}
