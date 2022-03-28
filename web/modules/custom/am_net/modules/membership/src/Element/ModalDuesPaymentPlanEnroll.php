<?php

namespace Drupal\am_net_membership\Element;

use Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for handle Modal Dues Payment Plan.
 *
 * @FormElement("modal_dues_payment_plan_enroll")
 */
class ModalDuesPaymentPlanEnroll extends FormElement {

  /**
   * Processes a 'Modal Dues Payment Plan' form element.
   *
   * @param array $element
   *   Render array representing from $elements.
   *
   * @return array
   *   Render array representing from $elements.
   */
  public static function processRegistration(array $element) {
    $payment_plan = $element['#payment_plan'] ?? NULL;
    if (!$payment_plan || !($payment_plan instanceof DuesPaymentPlanInfoInterface)) {
      return $element;
    }
    // Set the Balance table.
    $element['#balance'] = $payment_plan->getBalance();
    // Set the plan start date.
    $element['#plan_start_date'] = $payment_plan->getPlanStartDate();
    // Set the plan end date.
    $element['#plan_end_date'] = $payment_plan->getPlanEndDate();
    // Set the 'Annual Membership dues'.
    $fee = $payment_plan->getBaseMembershipFee();
    $element['#annual_membership_dues'] = $payment_plan->formatAmount($fee);
    // Set the 'PAC Donation'.
    $contribution = $payment_plan->getContributionAmount('pac');
    $mount = $contribution ? $contribution->getNumber() : NULL;
    $donation = $mount ? $payment_plan->formatAmount($mount) : NULL;
    $element['#pac_donation'] = $donation;
    // Set the 'EF Donation'.
    $contribution = $payment_plan->getContributionAmount('ef');
    $mount = $contribution ? $contribution->getNumber() : NULL;
    $donation = $mount ? $payment_plan->formatAmount($mount) : NULL;
    $element['#ef_donation'] = $donation;
    // Set the plan months.
    $element['#plan_months'] = $payment_plan->getPlanMonths();
    // Returns render element.
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $data = [];
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'modal_dues_payment_plan_enroll',
      '#input' => TRUE,
      '#payment_plan' => NULL,
      '#ef_contribution_amount' => NULL,
      '#pac_contribution_amount' => NULL,
      '#end_date_of_current_fiscal_year' => NULL,
      '#base_membership_price' => NULL,
      '#plan_start_date' => NULL,
      '#plan_end_date' => NULL,
      '#annual_membership_dues' => NULL,
      '#pac_donation' => NULL,
      '#ef_donation' => NULL,
      '#plan_months' => NULL,
      '#user_id' => NULL,
      '#balance' => NULL,
      '#pre_render' => [
        [$class, 'processRegistration'],
      ],
      '#attached' => [
        'library' => ['am_net_membership/modal_dues_payment_plan_enroll_widget'],
      ],
    ];
  }

}
