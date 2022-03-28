<?php

namespace Drupal\am_net_membership\DuesPaymentPlan;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Price;

/**
 * The Dues Payment Plan implementation.
 */
class DuesPaymentPlanInfo implements DuesPaymentPlanInfoInterface {

  /**
   * The Dues Payment Plan Info.
   *
   * @var array
   */
  protected $data = [
    'enroll' => FALSE,
    'is_membership_application' => FALSE,
    'is_membership_renewal' => FALSE,
    'membership_fee' => NULL,
    'membership_price' => NULL,
    'end_date_of_current_fiscal_year' => NULL,
    'plan_start_timestamp' => NULL,
    'contributions' => [],
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    $plan = new DuesPaymentPlanInfo();
    $plan->setData($values);
    return $plan;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $values = []) {
    $this->data = $values;
  }

  /**
   * Callback for ajax update dues payment container.
   *
   * Selects the piece of the form we want to use as replacement markup and
   * returns it as a form (renderable array).
   */
  public function updateDuesPaymentContainerCallback($form, FormStateInterface $form_state) {
    $key = ['donations', 'pac'];
    $contribution_amount = $form_state->getValue($key);
    $this->addContributionAmount($contribution_amount, 'pac');
    $key = ['donations', 'ef'];
    $contribution_amount = $form_state->getValue($key);
    $this->addContributionAmount($contribution_amount, 'ef');
    $ajax = $form_state->getUserInput();
    $enroll = $ajax['enroll'] ?? FALSE;
    $this->enroll($enroll);
    $this->addDuesPaymentForm($form, $form_state);
    return $form['dues_payment_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function addContributionAmount($amount = NULL, $type = NULL) {
    if (empty($amount) || !is_numeric($amount)) {
      unset($this->data['contributions'][$type]);
      return;
    }
    $amount = (string) $amount;
    $this->data['contributions'][$type]['fee'] = $amount;
    $this->data['contributions'][$type]['price'] = new Price($amount, 'USD');
  }

  /**
   * {@inheritdoc}
   */
  public function enroll($value = FALSE) {
    $flag = (bool) $value;
    $this->data['enroll'] = $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function addDuesPaymentForm(array &$form, FormStateInterface &$form_state) {
    // Add Ajax Callback into the donations fields.
    $form['donations']['pac']['#ajax'] = [
      'callback' => [$this, 'updateDuesPaymentContainerCallback'],
      'wrapper' => 'dues-payment-plan-container',
      'event' => 'change',
    ];
    $form['donations']['ef']['#ajax'] = [
      'callback' => [$this, 'updateDuesPaymentContainerCallback'],
      'wrapper' => 'dues-payment-plan-container',
      'event' => 'change',
    ];
    // Define clear element.
    $clear = [
      '#markup' => '<div class="clear clearfix"></div>',
      '#allowed_tags' => ['div', 'class'],
    ];
    // Define the container.
    $form['dues_payment_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['dues-payment-container'],
      ],
      '#prefix' => '<div id="dues-payment-plan-container">',
      '#suffix' => '</div>',
    ];
    // Clear.
    $form['dues_payment_container']['clear_1'] = $clear;
    // I want to participate in the VSCPA Dues Payment Plan.
    $form['dues_payment_container']['enroll'] = [
      '#type' => 'checkbox',
      '#title' => t('I want to participate in the VSCPA <strong><i>Flexible Payments</i></strong>.'),
      '#default_value' => $this->isPlanActive(),
      '#description' => NULL,
    ];
    $form['dues_payment_container']['clear_2'] = $clear;
    // Plan info container.
    $form['dues_payment_container']['plan_info'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['dues-payment-plan-info-container'],
      ],
    ];
    // Plan Info.
    $form['dues_payment_container']['plan_info']['months'] = [
      '#markup' => t('If you participate in the in the VSCPA <strong><i>Flexible Payments</i></strong> your membership payment will be divided into <strong>@months</strong> months as follows:', ['@months' => $this->getPlanMonths()]),
      '#allowed_tags' => ['strong', 'i'],
      '#attributes' => [
        'class' => ['dues_plan_info_months'],
      ],
    ];
    $items = [];
    $plan_start_date = $this->getPlanStartDate();
    if (!empty($plan_start_date)) {
      $items[] = [
        '#markup' => "<strong>Initial Payment:</strong> " . $plan_start_date . ".",
        '#allowed_tags' => ['strong'],
      ];
    }
    $plan_end_date = $this->getPlanEndDate();
    if (!empty($plan_end_date)) {
      $items[] = [
        '#markup' => "<strong>Final Payment:</strong> " . $plan_end_date . ".",
        '#allowed_tags' => ['strong'],
      ];
    }
    $form['dues_payment_container']['plan_info']['summary'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
      '#attributes' => ['class' => 'dues-payment-plan-summary'],
      '#wrapper_attributes' => ['class' => 'container'],
    ];
    $form['dues_payment_container']['plan_info']['balance'] = $this->getBalance();
  }

  /**
   * {@inheritdoc}
   */
  public function isPlanActive() {
    return $this->data['enroll'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlanMonths() {
    $number_of_months_in_a_calendar_year = 12;
    $dues_month_number = $this->getCurrentDuesMonthNumber();
    return $number_of_months_in_a_calendar_year - $dues_month_number + 1;
  }

  /**
   * Get the Plan Start Timestamp.
   *
   * @return int
   *   The Dues Payment Plan Start Date Time - The Unix timestamp..
   */
  public function getCurrentDuesMonthNumber() {
    $month = date('m');
    if ($month == 5) {
      // May is "Month number 1 of the dues year".
      return 1;
    }
    elseif ($month > 5) {
      return $month - 4;
    }
    elseif ($month < 5) {
      return $month + 8;
    }
  }

  /**
   * Get the Plan Start Timestamp.
   *
   * @return int
   *   The Dues Payment Plan Start Date Time - The Unix timestamp..
   */
  public function getPlanStartTimestamp() {
    if (is_null($this->data['plan_start_timestamp'])) {
      try {
        $plan_start = new \DateTime('now');
        $this->data['plan_start_timestamp'] = $plan_start->getTimestamp();
      }
      catch (\Exception $e) {
        return NULL;
      }
    }
    return $this->data['plan_start_timestamp'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDateOfCurrentFiscalYearTimestamp() {
    $date = $this->getEndDateOfCurrentFiscalYear();
    if (empty($date)) {
      return NULL;
    }
    $timestamp = strtotime($date);
    return $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDateOfCurrentFiscalYear() {
    $date = $this->data['end_date_of_current_fiscal_year'] ?? NULL;
    if (!empty($date)) {
      $date_time = strtotime($date);
      $date = date('Y-m-d', strtotime('+1 month', $date_time));
    }
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlanStartDate($format = NULL) {
    try {
      $plan_start = new \DateTime();
      $plan_start->setTimestamp($this->getPlanStartTimestamp());
      $format = (empty($format)) ? 'F j, Y' : $format;
      return $plan_start->format($format);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPlanEndDateTime() {
    $start_time = $this->getPlanStartDateTime();
    $months = $this->getPlanMonths();
    $months--;
    $diff_time = "+$months month";
    $end_date_time = strtotime($diff_time, $start_time);
    return $end_date_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlanEndDate() {
    try {
      $plan_end_timestamp = $this->getPlanEndDateTime();
      $date_time = new \DateTime();
      $date_time->setTimestamp($plan_end_timestamp);
      return $date_time->format('F j, Y');
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBalance() {
    $rates = [
      '#type' => 'table',
      '#header' => [
        'id' => [
          'data' => [
            '#markup' => t('Installment'),
          ],
          'class' => ['text-center'],
        ],
        'item' => t('Items'),
        'billed' => t('Billing Date'),
        'amount' => [
          'data' => [
            '#markup' => t('Amount'),
          ],
          'class' => ['text-center'],
        ],
        'subtotal' => [
          'data' => [
            '#markup' => t('Subtotal'),
          ],
          'class' => ['text-center'],
        ],
      ],
      '#empty' => t('No dues plan balance can be applied.'),
      '#attributes' => [
        'class' => [],
      ],
    ];
    /** @var \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanTransactionInterface[] $items */
    $items = $this->getBillingItems();
    /** @var \Drupal\commerce_price\Price $subtotal */
    $subtotal = NULL;
    $subtotal_price = NULL;
    foreach ($items as $delta => $fee) {
      $rates[$delta]['id'] = [
        'data' => [
          '#markup' => "<div class='text-center'>" . $fee->getId() . "</div> ",
          '#allowed_tags' => ['class', 'div'],
        ],
      ];
      $rates[$delta]['item'] = [
        '#markup' => "<div class='billing-item-summary'>" . $fee->getBillingItemLabel() . "</div> ",
        '#allowed_tags' => ['class', 'div', 'strong', 'br'],
      ];
      $rates[$delta]['billed'] = [
        '#markup' => $fee->getBilledDate(),
      ];
      $rates[$delta]['amount'] = [
        '#markup' => "<div class='text-center'>" . $fee->getFormattedAmount() . "</div> ",
        '#allowed_tags' => ['class', 'div'],
      ];
      $current_price = $fee->getTotalPrice();
      if (empty($subtotal)) {
        $subtotal = $current_price;
      }
      else {
        $subtotal = $subtotal->add($current_price);
      }
      if ($subtotal) {
        $subtotal_price = '$' . number_format($subtotal->getNumber(), 2, '.', ',');
      }
      else {
        $subtotal_price = NULL;
      }
      $rates[$delta]['subtotal'] = [
        '#markup' => "<div class='text-center'>" . $subtotal_price . "</div> ",
        '#allowed_tags' => ['class', 'div'],
      ];
    }
    $rates['#footer'] = [
      [
        'class' => ['footer-class'],
        'data' => [
          [
            'colspan' => 3,
          ],
          [
            'data' => [
              '#markup' => "<strong class='label-total text-center'>Total:</strong>",
              '#allowed_tags' => ['strong', 'class'],
            ],
            'colspan' => 1,
          ],
          [
            'data' => [
              '#markup' => "<h4 class='total-price-peer-review text-center'>{$subtotal_price}</h4>",
              '#allowed_tags' => ['h4', 'class'],
            ],
            'colspan' => 1,
          ],
        ],
      ],
    ];
    return $rates;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingItems() {
    if ($this->isMembershipApplication()) {
      return $this->getBillingItemsMembershipApplication();
    }
    elseif ($this->isMembershipRenewal()) {
      return $this->getBillingItemsMembershipRenewal();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isMembershipApplication() {
    $flag = $this->data['is_membership_application'];
    return (bool) $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingItemsMembershipApplication() {
    $items = [];
    if ($this->membershipPriceIsZero()) {
      return $items;
    }
    $months = $this->getPlanMonths();
    $monthly_fee = $this->getFirstInstallmentMembershipPrice();
    $next_billing_date_time = $this->getPlanStartDateTime();
    $final_month_fee = $this->getFinalInstallmentMembershipPrice();
    $zero = new Price(0, 'USD');
    $monthly_rate_ef = $this->getMonthlyContribution($months, 'ef');
    $monthly_rate_pac = $this->getMonthlyContribution($months, 'pac');
    for ($i = 1; $i <= $months; $i++) {
      $month_label = date('F, Y', $next_billing_date_time);
      $billed_date = date('F j, Y', $next_billing_date_time);
      $billing_items = [];
      if ($i == $months) {
        // Use the final scheduled payment to fix any under/over payment issues.
        $billing_items[] = [
          'label' => t('Membership monthly fee'),
          'amount' => $final_month_fee->getNumber(),
        ];
      }
      else {
        $billing_items[] = [
          'label' => t('Membership monthly fee'),
          'amount' => $monthly_fee->getNumber(),
        ];
      }
      if ($monthly_rate_ef->greaterThan($zero)) {
        $billing_items[] = [
          'label' => t('Ed Fund contribution'),
          'amount' => $monthly_rate_ef->getNumber(),
        ];
      }
      if ($monthly_rate_pac->greaterThan($zero)) {
        $billing_items[] = [
          'label' => t('PAC contribution'),
          'amount' => $monthly_rate_pac->getNumber(),
        ];
      }
      $transaction = [
        'id' => $i,
        'month' => $month_label,
        'billed_date' => $billed_date,
        'billing_items' => $billing_items,
      ];
      $items[] = new DuesPaymentPlanTransaction($transaction);
      $next_billing_date = date('Y-m-d', strtotime('+1 month', $next_billing_date_time));
      $next_billing_date_time = strtotime($next_billing_date);
    }
    return $items;
  }

  /**
   * Gets whether the current membership price is zero.
   *
   * @return bool
   *   TRUE if the membership price is zero, FALSE otherwise.
   */
  public function membershipPriceIsZero() {
    if (empty($this->data['membership_price'])) {
      return TRUE;
    }
    /** @var \Drupal\commerce_price\Price $price */
    $price = $this->data['membership_price'];
    return $price->isZero();
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipPrice() {
    return $this->data['membership_price'] ?? new Price(0, 'USD');
  }

  /**
   * Get the Plan Start Date Time.
   *
   * @return string
   *   The Dues Payment Plan Start Date Time.
   */
  public function getPlanStartDateTime() {
    return $this->getPlanStartTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getMonthlyContribution($months = NULL, $type = NULL) {
    if (empty($months) || !is_numeric($months)) {
      return new Price(0, 'USD');
    }
    $amount = $this->getContributionAmount($type);
    if (empty($amount) || (!($amount instanceof Price)) || $amount->isZero()) {
      return new Price(0, 'USD');
    }
    return $amount->divide($months);
  }

  /**
   * {@inheritdoc}
   */
  public function getContributionAmount($type = NULL) {
    if (empty($type)) {
      return NULL;
    }
    return $this->data['contributions'][$type]['price'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isMembershipRenewal() {
    $flag = $this->data['is_membership_renewal'];
    return (bool) $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingItemsMembershipRenewal() {
    $items = [];
    if ($this->membershipPriceIsZero()) {
      return $items;
    }
    $total_months = 12;
    $total_membership_price = $this->getMembershipPrice();
    $monthly_fee = $total_membership_price->divide($total_months);
    $monthly_fee = $this->round($monthly_fee);
    $months = $this->getPlanMonths();
    $first_month_fee = $this->getFirstInstallmentMembershipPrice();
    $final_month_fee = $this->getFinalInstallmentMembershipPrice();
    $next_billing_date_time = $this->getPlanStartDateTime();
    $zero = new Price(0, 'USD');
    $monthly_rate_ef = $this->getMonthlyContribution($months, 'ef');
    $monthly_rate_pac = $this->getMonthlyContribution($months, 'pac');
    for ($i = 1; $i <= $months; $i++) {
      $month_label = date('F, Y', $next_billing_date_time);
      $billed_date = date('F j, Y', $next_billing_date_time);
      $billing_items = [];
      if ($i == 1) {
        // Add first monthly fee with money catch-up.
        $billing_items[] = [
          'label' => t('Membership monthly fee'),
          'amount' => $first_month_fee->getNumber(),
        ];
      }
      if ($i == $months) {
        // Use the final scheduled payment to fix any under/over payment issues.
        $billing_items[] = [
          'label' => t('Membership monthly fee'),
          'amount' => $final_month_fee->getNumber(),
        ];
      }
      else {
        $billing_items[] = [
          'label' => t('Membership monthly fee'),
          'amount' => $monthly_fee->getNumber(),
        ];
      }
      if ($monthly_rate_ef->greaterThan($zero)) {
        $billing_items[] = [
          'label' => t('Ed Fund contribution'),
          'amount' => $monthly_rate_ef->getNumber(),
        ];
      }
      if ($monthly_rate_pac->greaterThan($zero)) {
        $billing_items[] = [
          'label' => t('PAC contribution'),
          'amount' => $monthly_rate_pac->getNumber(),
        ];
      }
      $transaction = [
        'id' => $i,
        'month' => $month_label,
        'billed_date' => $billed_date,
        'billing_items' => $billing_items,
      ];
      $items[] = new DuesPaymentPlanTransaction($transaction);
      $next_billing_date = date('Y-m-d', strtotime('+1 month', $next_billing_date_time));
      $next_billing_date_time = strtotime($next_billing_date);
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function setBaseMembershipFee($fee = NULL) {
    if (empty($fee)) {
      $this->data['membership_fee'] = NULL;
      $this->data['membership_price'] = NULL;
    }
    else {
      $this->data['membership_fee'] = $fee;
      $this->data['membership_price'] = new Price($fee, 'USD');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDateOfCurrentFiscalYear($date = NULL) {
    $this->data['end_date_of_current_fiscal_year'] = $date;
  }

  /**
   * {@inheritdoc}
   */
  public function setMembershipApplication($value = FALSE) {
    $flag = (bool) $value;
    $this->data['is_membership_application'] = $flag;
    $this->data['is_membership_renewal'] = !$flag;
  }

  /**
   * {@inheritdoc}
   */
  public function setMembershipRenewal($value = FALSE) {
    $flag = (bool) $value;
    $this->data['is_membership_renewal'] = $flag;
    $this->data['is_membership_application'] = !$flag;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getDuesPayment() {
    return $this->getBaseMembershipFee();
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustedDuesPayment(OrderItemInterface $item = NULL) {
    $base_membership_fee = $this->getDuesPayment();
    if (!is_numeric($base_membership_fee)) {
      return '0';
    }
    $order_item = clone $item;
    // Apply Adjustments if applies.
    $adjustments = $order_item->getAdjustments();
    if (empty($adjustments)) {
      return $base_membership_fee;
    }
    // Clear Adjustments.
    $order_item->setAdjustments([]);
    // Set the base price.
    $price = new Price($base_membership_fee, 'USD');
    $order_item->setUnitPrice($price, TRUE);
    // Apply Offers.
    $this->applyOffer($order_item);
    $adjusted_total_price = $order_item->getAdjustedTotalPrice();
    return $adjusted_total_price->getNumber();
  }

  /**
   * {@inheritdoc}
   */
  public function applyOffer(OrderItemInterface &$order_item = NULL) {
    $order = $order_item->getOrder();
    $coupons = $order->get('coupons')->referencedEntities();
    if (empty($coupons)) {
      // No coupons, return base price.
      return NULL;
    }
    /* @var \Drupal\commerce_promotion\Entity\Coupon $coupon */
    // Coupons apply discount.
    // Multiple coupon applications is not allowed on VSCPA business logic.
    $coupon = current($coupons);
    // Get the promotion.
    $promotion = ($coupon) ? $coupon->getPromotion() : NULL;
    if (!$promotion) {
      return NULL;
    }
    // Get the offer.
    $offer = ($promotion) ? $promotion->getOffer() : NULL;
    if (!$offer) {
      return NULL;
    }
    $offer->apply($order_item, $promotion);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseMembershipFee() {
    return $this->data['membership_fee'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustedCcAmount(OrderItemInterface $order_item = NULL) {
    // Apply Adjustments if applies.
    $adjustments = $order_item->getAdjustments();
    if (empty($adjustments)) {
      return $this->getCcAmount();
    }
    // Add the First Installment related to the Membership Price.
    $price = $this->getFirstInstallmentMembershipPrice();
    $order_item->setUnitPrice($price, TRUE);
    /* @var \Drupal\commerce_price\Price $amount */
    $amount = $order_item->getAdjustedTotalPrice();
    // Add monthly contribution of type: EF.
    $donation = $this->getFirstInstallmentMonthlyContribution('ef');
    $amount = $amount->add($donation);
    // Add monthly contribution of type: PAC.
    $donation = $this->getFirstInstallmentMonthlyContribution('pac');
    $amount = $amount->add($donation);
    // Return the result.
    return $amount->getNumber();
  }

  /**
   * {@inheritdoc}
   */
  public function getCcAmount() {
    /* @var \Drupal\commerce_price\Price $amount */
    // Add the First Installment related to the Membership Price.
    $amount = $this->getFirstInstallmentMembershipPrice();
    // Add monthly contribution of type: EF.
    $donation = $this->getFirstInstallmentMonthlyContribution('ef');
    $amount = $amount->add($donation);
    // Add monthly contribution of type: PAC.
    $donation = $this->getFirstInstallmentMonthlyContribution('pac');
    $amount = $amount->add($donation);
    // Return the result.
    return $amount->getNumber();
  }

  /**
   * {@inheritdoc}
   */
  public function round(Price $amount = NULL) {
    $zero = new Price(0, 'USD');
    if (!$amount) {
      return $zero;
    }
    if (!$amount->greaterThan($zero)) {
      return $zero;
    }
    $fee = $amount->getNumber();
    $rounded_fee = (string) round($fee, 2);
    return new Price($rounded_fee, 'USD');
  }

  /**
   * {@inheritdoc}
   */
  public function getFinalInstallmentMembershipPrice() {
    $zero = new Price(0, 'USD');
    if ($this->membershipPriceIsZero()) {
      return $zero;
    }
    $total_membership_price = $this->getMembershipPrice();
    $months = $this->getPlanMonths();
    if ($months == 0) {
      $monthly_price = $this->getMembershipPrice();
      return $this->round($monthly_price);
    }
    if ($this->isMembershipApplication()) {
      $monthly_price = $this->getMembershipPrice()->divide($months);
    }
    elseif ($this->isMembershipRenewal()) {
      $total_months = 12;
      $monthly_price = $this->getMembershipPrice()->divide($total_months);
    }
    $monthly_price = $this->round($monthly_price);
    $amount_charged = $monthly_price->multiply(($months - 1));
    return $total_membership_price->subtract($amount_charged);
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstInstallmentMembershipPrice() {
    $zero = new Price(0, 'USD');
    if ($this->membershipPriceIsZero()) {
      return $zero;
    }
    $months = $this->getPlanMonths();
    if ($months == 0) {
      $monthly_price = $this->getMembershipPrice();
      return $this->round($monthly_price);
    }
    if ($this->isMembershipApplication()) {
      $monthly_price = $this->getMembershipPrice()->divide($months);
      return $this->round($monthly_price);
    }
    elseif ($this->isMembershipRenewal()) {
      $total_months = 12;
      $monthly_fee = $this->getMembershipPrice()->divide($total_months);
      $first_month_fee = $monthly_fee;
      if ($months < $total_months) {
        $diff = $total_months - $months;
        $diff++;
        // Catch-up money from Previous months.
        $first_month_fee = $first_month_fee->multiply($diff);
      }
      return $this->round($first_month_fee);
    }
    return $zero;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstInstallmentMonthlyContribution($type = NULL) {
    $zero = new Price(0, 'USD');
    if (empty($type)) {
      return $zero;
    }
    $type = strtolower($type);
    $months = $this->getPlanMonths();
    $amount = $this->getMonthlyContribution($months, $type);
    return $this->round($amount);
  }

  /**
   * {@inheritdoc}
   */
  public function formatAmount($amount = NULL) {
    if (empty($amount)) {
      return NULL;
    }
    return '$' . number_format($amount, 2, '.', ',');
  }

}
