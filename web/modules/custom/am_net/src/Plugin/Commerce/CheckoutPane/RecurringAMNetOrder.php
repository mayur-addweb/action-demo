<?php

namespace Drupal\am_net\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a checkout pane for users to opt-in to recurring membership orders.
 *
 * @CommerceCheckoutPane(
 *   id = "am_net_recurring_order_optin",
 *   label = @Translation("Recurring Order Opt-in"),
 *   default_step = "review",
 *   wrapper_element = "container",
 * )
 */
class RecurringAMNetOrder extends CheckoutPaneBase {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentActiveUser = NULL;

  /**
   * The dues payment plan helper.
   *
   * @var \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanHelper
   */
  protected $duesPaymentPlanHelper = NULL;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'text' => 'Check here to submit this as a recurring order.',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['text'])) {
      $summary = $this->t('Text: @text', [
        '@text' => $this->configuration['text'],
      ]);
    }
    else {
      $summary = $this->t('Text: (empty)');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text'),
      '#description' => $this->t('The text to display as the label to the opt-in checkbox.'),
      '#default_value' => $this->configuration['text'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['text'] = $values['text'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    if (!$this->order->hasField('field_am_net_recurring')) {
      return FALSE;
    }
    // 1. Remove recurring opt-in on total zero.
    $order_total = $this->order->getTotalPrice();
    if ($order_total->isZero()) {
      return FALSE;
    }
    // 2. Remove recurring opt-in... admins will be using corporate cards,
    // which  we don't allow for recurring due to VSCPA accounting controls.
    if ($this->isCurrentUserFirmAdmin()) {
      return FALSE;
    }
    // 3. Remove recurring opt-in if users does not have the membership product
    // in the Cart.
    if (!$this->hasMembershipProductInTheCart()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Check if current user is firm admin.
   *
   * @return bool
   *   TRUE if the current user is firm admin, otherwise false.
   */
  public function isCurrentUserFirmAdmin() {
    $user = $this->currentUser();
    if (is_null($user)) {
      return FALSE;
    }
    $user_roles = $user->getRoles();
    return in_array('firm_administrator', $user_roles);
  }

  /**
   * The current active user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user account instance.
   */
  public function currentUser() {
    if (is_null($this->currentActiveUser)) {
      $this->currentActiveUser = \Drupal::currentUser();
    }
    return $this->currentActiveUser;
  }

  /**
   * Check if Membership Product is in the cart.
   *
   * @return bool
   *   TRUE if Membership Product is in the cart, otherwise false.
   */
  public function hasMembershipProductInTheCart() {
    $order = $this->order;
    if (!$order) {
      return FALSE;
    }
    $items = $order->getItems();
    if (empty($items)) {
      return FALSE;
    }
    foreach ($items as $delta => $item) {
      $variation = $item->getPurchasedEntity();
      if (!$variation) {
        continue;
      }
      /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
      $product = $variation->getProduct();
      if (!$product) {
        continue;
      }
      if ($product->bundle() == 'membership') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $options = $this->getRecurringOptions();
    $default_value = 0;
    if (!empty($options)) {
      $default_value = key($options);
    }
    $pane_form['recurring_optin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Are You Interested in Auto Renewing Your Membership?'),
      '#default_value' => $default_value,
      '#attributes' => ['class' => ['recurring-membership-summary']],
      '#options' => $this->getRecurringOptions(),
      '#required' => TRUE,
    ];
    $pane_form['recurring_optin_summary'] = [
      '#markup' => '<div class="recurring-membership-summary"><ul class="recurring-terms"><li><strong>*</strong> You will receive a confirmation email after processing payment(s). To update payment information or cancel, call VSCPA at (800) 733-8272.</li> <li><strong>**</strong> Your membership dues and voluntary contribution(s) will be divided into installments, paid accordingly: the first installment will be processed today. Remaining payments will be processed on the same date as that of the initial payment and use the same payment method as the initial payment. The final payment will be processed in the April associated with the VSCPA membership year you are currently paying for.</li></ul></div>',
      '#allowed_tags' => ['div', 'ul', 'li', 'class', 'strong'],
      '#attributes' => ['class' => ['recurring-membership-summary']],
    ];
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $selected_value = $form_state->getValue([
      'am_net_recurring_order_optin',
      'recurring_optin',
    ]);
    $value = in_array($selected_value, [0, 1]) ? TRUE : FALSE;
    $this->order->set('field_am_net_recurring', $value);
  }

  /**
   * Get recurring OptIn Default value.
   *
   * @return bool
   *   TRUE if the option is checked, otherwise false.
   */
  public function getRecurringOptInDefaultValue() {
    // Application: set default value of recurring profile to ON, i.e. opted in.
    if ($this->isApplication()) {
      return TRUE;
    }
    return ($this->order->get('field_am_net_recurring')->value);
  }

  /**
   * Check if current user does have the role member.
   *
   * @return bool
   *   TRUE if the current user does have the role member, otherwise false.
   */
  public function isApplication() {
    $user = $this->currentUser();
    if (is_null($user)) {
      return FALSE;
    }
    $user_roles = $user->getRoles();
    return !in_array('member', $user_roles);
  }

  /**
   * The dues payment plan helper.
   *
   * @return array
   *   The array of recurring options.
   */
  public function getRecurringOptions() {
    $options = [];
    $order_contains_active_payment_plan = $this->duesPaymentPlanHelper()->orderContainsActivePaymentPlan($this->order);
    if (!$order_contains_active_payment_plan) {
      // Add 'Single Payment' Option.
      $options[0] = $this->t('<strong>Auto Renewal </strong> — I wish to pay future membership dues (and any voluntary contributions) automatically via a single payment, processed on May 1 each year.<strong>*</strong>');
    }
    else {
      // Add 'Flexible Payments' Option.
      $options[1] = $this->t('<strong>Auto Renewal </strong> — I wish to pay future membership dues (and any voluntary contributions) automatically as VSCPA Flexible Payments.<strong>*</strong> <strong>**</strong>');
    }
    // Add 'No Thanks' Option.
    $options[2] = $this->t('<strong>No thanks</strong>, I currently do not wish to pay future membership dues automatically (either on a one-time or flexible payment basis) and understand VSCPA will send me reminder communications about renewing my membership.');
    return $options;
  }

  /**
   * The dues payment plan helper.
   *
   * @return \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanHelper
   *   The dues payment plan helper.
   */
  public function duesPaymentPlanHelper() {
    if (is_null($this->duesPaymentPlanHelper)) {
      $this->duesPaymentPlanHelper = \Drupal::service('am_net_membership.dues_payment_plan.helper');
    }
    return $this->duesPaymentPlanHelper;
  }

}
