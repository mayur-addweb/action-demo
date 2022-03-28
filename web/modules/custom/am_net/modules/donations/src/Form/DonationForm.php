<?php

namespace Drupal\am_net_donations\Form;

use Drupal\am_net_donations\Event\DonationEvent;
use Drupal\am_net_donations\Event\DonationEvents;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\am_net_donations\DonationManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Implements the main donation landing page form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class DonationForm extends FormBase {

  /**
   * Membership Class checker.
   *
   * @var \Drupal\am_net_donations\DonationManager|null
   */
  protected $donationManager = NULL;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a DonationForm object.
   *
   * @param \Drupal\am_net_donations\DonationManager $donation_manager
   *   The donation manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(DonationManager $donation_manager, EventDispatcherInterface $event_dispatcher) {
    $this->donationManager = $donation_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_donations.donation_manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_donations.ef.donation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $info = NULL) {
    $form['#tree'] = TRUE;
    $body = $info['body'] ?? NULL;
    if (!empty($body)) {
      $form['body'] = [
        '#markup' => $body,
      ];
    }
    $form['#attributes']['class'][] = 'donation_form';
    $form['donations'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'donations_wrapper',
        ],
      ],
      "#tree" => TRUE,
    ];

    $form['donations']['title'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $this->t('Donation') . '</h3>',
    ];
    // Contribution amount.
    $form['price_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Contribution amount'),
      '#options' => [
        35 => '$35',
        50 => '$50',
        100 => '$100',
        'Other' => $this->t('Other'),
      ],
      '#required' => TRUE,
      '#default_value' => 35,
    ];
    $form['contribution_amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Please enter a donation amount'),
      '#default_value' => ['number' => '35', 'currency_code' => 'USD'],
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('(list whole integers without "$". Ex: 100)'),
      '#states' => [
        'visible' => [
          'select[name="price_option"]' => ['value' => 'Other'],
        ],
        'required' => [
          'select[name="price_option"]' => ['value' => 'Other'],
        ],
      ],
    ];
    // Donation type.
    $form['donation_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Is this a'),
      '#default_value' => 'P',
      '#options' => [
        'P' => $this->t('individual gift'),
        'F' => $this->t('firm/company gift'),
      ],
      '#required' => TRUE,
    ];
    // EF Type.
    $form['fund'] = [
      '#type' => 'select',
      '#title' => $this->t('Please choose which Education Foundation opportunity you wish to support'),
      '#default_value' => 'AF',
      '#options' => [
        'AF' => $this->t('Annual Fund - this would really help!*'),
        'VS' => $this->t('Samuel A. Derieux Memorial Scholarship'),
        'WM' => $this->t('Mares Scholars Fund'),
        'MJ' => $this->t('Murray, Jonson, White & Associates Fund'),
      ],
      '#required' => TRUE,
    ];
    // Donation Destination.
    $form['donation_destination'] = [
      '#type' => 'hidden',
      '#value' => 'EF',
    ];
    // Recurring Container.
    $form['recurring_container'] = [
      '#prefix' => '<div class="container-inline form-inline container-inline-recurring">',
      '#suffix' => '</div>',
    ];
    // Recurring.
    $form['recurring_container']['recurring'] = [
      '#type' => 'checkbox',
      '#title' => '',
      '#default_value' => FALSE,
    ];
    // Recurring Interval.
    $form['recurring_container']['recurring_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Make this donation'),
      '#default_value' => 'MNTH',
      '#options' => [
        'MNTH' => $this->t('Monthly'),
        '3MNT' => $this->t('Quarterly'),
        'YEAR' => $this->t('Annually'),
      ],
      '#required' => FALSE,
      '#attributes' => [
        'class' => [
          'recurring_interval',
        ],
      ],
    ];
    // I wish to remain anonymous.
    $form['anonymous'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I wish to remain anonymous'),
    ];
    $note = $info['note'] ?? NULL;
    $form['note'] = [
      '#type' => 'item',
      '#markup' => $note,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Continue to billing information'),
      '#attributes' => ['class' => ['submit', 'btn-purple']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $this->currentUser();
    $price_option = $form_state->getValue('price_option');
    $contribution_amount = $form_state->getValue('contribution_amount');
    $amount = $price_option == 'Other' ? new Price($contribution_amount['number'], $contribution_amount['currency_code']) : new Price($price_option, 'USD');
    $anonymous = (bool) $form_state->getValue('anonymous');
    $destination = $form_state->getValue('donation_destination');
    $type = $form_state->getValue('donation_type');
    $fund = $form_state->getValue('fund');
    $is_recurring = $form_state->getValue(['recurring_container', 'recurring']);
    $recurring_interval = $form_state->getValue(['recurring_container', 'recurring_interval']);
    $event = new DonationEvent($account, $amount, $anonymous, $destination, $type, $fund, boolval($is_recurring), $recurring_interval);
    $this->eventDispatcher->dispatch(DonationEvents::SUBMIT_DONATION, $event);
    if ($redirect_url = $event->getRedirectUrl()) {
      $form_state->setRedirectUrl($redirect_url);
    }
  }

}
