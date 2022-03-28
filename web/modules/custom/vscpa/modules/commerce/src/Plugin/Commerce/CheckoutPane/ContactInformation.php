<?php

namespace Drupal\vscpa_commerce\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\ContactInformation as ContactInformationCheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add enhancements to the Coupon Redemption Checkout Pane.
 */
class ContactInformation extends ContactInformationCheckoutPaneBase {

  /**
   * Gets the customer user email.
   *
   * @return string
   *   The customer user email,
   */
  public function getCustomerEmail() {
    $email = $this->order->getEmail();
    if (!empty($this->order->getCustomerId())) {
      $email = $this->order->getCustomer()->getEmail();
    }
    return $email;
  }

  /**
   * Check if the current checkout is Guest.
   *
   * @return bool
   *   TRUE if is a guest checkout, otherwise FALSE,
   */
  public function isGuestCheckout() {
    return empty($this->order->getCustomerId());
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    return [
      '#plain_text' => $this->getCustomerEmail(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $email = $this->getCustomerEmail();
    if ($this->isGuestCheckout()) {
      $pane_form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#default_value' => $email,
        '#required' => TRUE,
      ];
      if ($this->configuration['double_entry']) {
        $pane_form['email_confirm'] = [
          '#type' => 'email',
          '#title' => $this->t('Confirm email'),
          '#default_value' => $email,
          '#required' => TRUE,
        ];
      }
    }
    else {
      $pane_form['plain_text'] = [
        '#plain_text' => $email,
      ];
      $pane_form['email'] = [
        '#type' => 'hidden',
        '#value' => $email,
      ];
    }

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    if ($this->isGuestCheckout()) {
      $values = $form_state->getValue($pane_form['#parents']);
      if ($this->configuration['double_entry'] && $values['email'] != $values['email_confirm']) {
        $form_state->setError($pane_form, $this->t('The specified emails do not match.'));
      }
    }
    else {
      // Set the Customer email.
      $email = $this->getCustomerEmail();
      $this->order->setEmail($email);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Set the Customer email.
    $email = $this->getCustomerEmail();
    if ($this->isGuestCheckout()) {
      $values = $form_state->getValue($pane_form['#parents']);
      $email = isset($values['email']) ? $values['email'] : $email;
    }
    $this->order->setEmail($email);
  }

}
