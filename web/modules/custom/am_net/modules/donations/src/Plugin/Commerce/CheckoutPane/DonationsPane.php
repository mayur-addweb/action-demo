<?php

namespace Drupal\am_net_donations\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;

/**
 * Provides the Donations Pane.
 *
 * @CommerceCheckoutPane(
 *   id = "donations",
 *   label = @Translation("Request optional Donations from Customer."),
 *   admin_label = @Translation("Request optional Donations from Customer."),
 *   default_step = "order_information",
 *   wrapper_element = "container",
 * )
 */
class DonationsPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_submit_button' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['display_submit_button'])) {
      $summary = $this->t('Submit button: Displayed');
    }
    else {
      $summary = $this->t('Submit button: Hidden');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['display_submit_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display submit button'),
      '#default_value' => $this->configuration['display_submit_button'],
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
      $this->configuration['display_submit_button'] = !empty($values['display_submit_button']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['#tree'] = TRUE;
    $pane_form['#attributes']['class'][] = 'donation_form';
    $pane_form['donations'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'donations_wrapper',
        ],
      ],
      "#tree" => TRUE,
    ];
    $pane_form['donations']['title'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $this->t('Donation') . '</h3>',
    ];
    $pane_form['donations']['description'] = [
      '#type' => 'item',
      '#markup' => "Please enter a donation amount or $0.",
    ];
    $pane_form['donations']['pac'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('VSCPA PAC'),
      '#default_value' => ['number' => '35', 'currency_code' => 'USD'],
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('PAC (VSCPA Political Action Committee)'),
    ];
    $pane_form['donations']['ef'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('VSCPA Educational Foundation contribution'),
      '#default_value' => ['number' => '35', 'currency_code' => 'USD'],
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('VSCPA Educational Foundation contribution'),
    ];
    $pane_form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Continue to billing information'),
      '#attributes' => ['class' => ['submit', 'btn-purple']],
      '#access' => $this->configuration['display_submit_button'],
    ];
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $donation = $form_state->getUserInput();

  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    $isVisible = FALSE;
    // Check if the order contains Donation product.
    $membership_items = $this->getDonationItems($this->order->getItems());
    if (empty($membership_items)) {
      $isVisible = TRUE;
    }
    return $isVisible;
  }

  /**
   * Get Donation Items from order items.
   *
   * @param array $order_items
   *   The order items.
   *
   * @return array
   *   The Donations entities array.
   */
  public function getDonationItems(array $order_items) {
    $membership_items = [];
    foreach ($order_items as $order_item) {
      // Get Purchased Entity.
      $variation = $order_item->getPurchasedEntity();
      if (!is_null($variation) && ($variation instanceof ProductVariationInterface)) {
        $variation_type = $variation->get('type')->entity->id();
        if ($variation_type == 'donation') {
          $membership_items = array_merge($membership_items, [$variation]);
        }
      }
    }
    return $membership_items;
  }

}
