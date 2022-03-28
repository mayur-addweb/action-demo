<?php

namespace Drupal\vscpa_commerce\PeerReview\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vscpa_commerce\PeerReview\PeerReviewRatesTrait;

/**
 * Peer Review Config form.
 */
class PeerReviewConfigForm extends FormBase {

  use PeerReviewRatesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vscpa_commerce_peer_review_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['rates_item'] = [
      '#type' => 'details',
      '#title' => $this->t('Manage Peer Review Rates'),
      '#weight' => 1,
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['peer_review_rates'],
      ],
      '#prefix' => '<div id="peer-deview-rates-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $rates_item = &$form['rates_item'];
    $rates_item['title'] = [
      '#markup' => '<h4>' . $this->t('Peer Review Rates') . '</h4>',
    ];
    $rates_item['rates'] = [
      '#type' => 'table',
      '#header' => $this->rates->getHeader(),
      '#empty' => $this->t('There are no rates defined.'),
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];
    $rates_item['fetch_rates'] = [
      '#type' => 'submit',
      '#value' => $this->t('Fetch rates changes from AM.Net'),
      '#submit' => [[$this, 'fetchRatesChangesFromAmNet']],
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
          'button--small',
          'button',
          'margin-left-none',
        ],
      ],
    ];
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface $rate */
    foreach ($this->rates as $delta => $rate) {
      $rates_item['rates'][$delta]['billing_class_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Billing Class Code'),
        '#default_value' => $rate->getBillingClassCode(),
        '#size' => 10,
        '#attributes' => [
          'readonly' => 'readonly',
          'disabled' => 'disabled',
        ],
      ];
      $rates_item['rates'][$delta]['fee'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Fee ($)'),
        '#default_value' => $rate->getFee(),
        '#size' => 10,
        '#attributes' => [
          'readonly' => 'readonly',
          'disabled' => 'disabled',
        ],
      ];
      $rates_item['rates'][$delta]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $rate->getLabel(),
        '#size' => 40,
      ];
    }
    // Peer Review Administrative Fees Page.
    $form['peer_review_administrative_fees_node_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Peer Review Fees Page'),
      '#description' => $this->t('Please set the <strong>Node ID</strong> of the Page: "Peer Review Administrative Fees".'),
      '#size' => 30,
      '#weight' => 30,
      '#default_value' => $this->rates->getPeerReviewAdministrativeFeesNodeId(),
      '#required' => TRUE,
    ];
    // Allow users to change their firm size?.
    $form['allow_change_firm_size'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to change their firm size?'),
      '#description' => $this->t("<strong>ALLOW</strong> users to change their firm size via a drop down menu (will impact the fee they pay) and pay accordingly. Rationale: we want to remove barriers from collecting payment, regardless of whether we're collecting less or more money than anticipated."),
      '#default_value' => $this->rates->getAllowChangeFirmSize(),
      '#weight' => 29,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
          'js-form-submit',
          'form-submit',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node_id = $form_state->getValue(['peer_review_administrative_fees_node_id']);
    $this->rates->setPeerReviewAdministrativeFeesNodeId($node_id);
    $allow_change_firm_size = $form_state->getValue(['allow_change_firm_size']);
    $this->rates->setAllowChangeFirmSize($allow_change_firm_size);
    $rates = $form_state->getValue(['rates_item', 'rates']);
    $result = $this->rates->saveFromSubmittedEntries($rates);
    $saved = ($result == SAVED_NEW) || ($result == SAVED_UPDATED);
    if ($saved) {
      $message = $this->t('The Peer Review Changes has been saved.');
      $this->messenger()->addMessage($message);
    }
    else {
      $message = $this->t('The Peer Review Changes could not be saved please try again.');
      $this->messenger()->addError($message);
    }
  }

  /**
   * Form submission handler to Fetch rates changes from AM.Net.
   */
  public function fetchRatesChangesFromAmNet() {
    $result = $this->rates->fetchRatesChangesFromAmNet();
    if ($result) {
      $message = $this->t('The rates have been successfully fetched from AM.net.');
      $this->messenger()->addMessage($message);
    }
    else {
      $message = $this->t('It was not possible to fetch the rates from AM.net at this moment, please try again.');
      $this->messenger()->addError($message);
    }
  }

}
