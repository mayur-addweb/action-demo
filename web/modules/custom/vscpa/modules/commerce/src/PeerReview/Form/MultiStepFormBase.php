<?php

namespace Drupal\vscpa_commerce\PeerReview\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vscpa_commerce\PeerReview\PeerReviewRatesInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Peer Review multi step form base.
 */
abstract class MultiStepFormBase extends FormBase {

  /**
   * The Peer Review Rates service.
   *
   * @var \Drupal\vscpa_commerce\PeerReview\PeerReviewRatesInterface
   */
  protected $rates = NULL;

  /**
   * The Peer Review Info Service.
   *
   * @var \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   */
  protected $info = NULL;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Class constructor for Peer Review Config form.
   *
   * @param \Drupal\vscpa_commerce\PeerReview\PeerReviewRatesInterface $rates_service
   *   The Peer Review Rates Service.
   * @param \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface $info
   *   The Peer Review Info Service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(PeerReviewRatesInterface $rates_service, PeerReviewInfoInterface $info, EventDispatcherInterface $event_dispatcher) {
    $this->rates = $rates_service;
    $this->info = $info;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vscpa_commerce.peer_review_rates'),
      $container->get('vscpa_commerce.peer_review_info'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Get Firm ID from current user.
   *
   * @return string|null
   *   The Firm ID linked to the current user.
   */
  public function getFirmIdFromCurrentUser() {
    $account = $this->currentUser();
    $user = User::load($account->id());
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $value */
    $value = $user->get('field_firm');
    $firms = $value->referencedEntities();
    if (empty($firms)) {
      return NULL;
    }
    $firm = current($firms);
    if ($firm && ($firm instanceof TermInterface)) {
      $amnet_id = $firm->get('field_amnet_id')->getString();
      return $amnet_id;
    }
    return NULL;
  }

  /**
   * Rebuild PeerReview Info.
   */
  public function rebuildPeerReviewInfo() {
    // Retrieve info from AM.net.
    $firm_id = $this->getFirmIdFromCurrentUser();
    $this->info->setRates($this->rates);
    $this->info->setFirmId($firm_id);
    $this->info->retrievePeerReviewInfo();
  }

  /**
   * Apply Firm Size Changes.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   */
  public function applyFirmSizeChanges(FormStateInterface $form_state) {
    // Gather the peer_review_info instance that is in the form already.
    $new_billing_class_code = $form_state->get('new_billing_class_code');
    if (empty($new_billing_class_code)) {
      return;
    }
    if ($this->info->getPreviousBillingCode() == $new_billing_class_code) {
      return;
    }
    $this->info->setFirmSizeChanges(TRUE);
    $this->info->setNewBillingCode($new_billing_class_code);
  }

  /**
   * Get the Billing Class Label.
   *
   * @return string|null
   *   The Billing Class Label, otherwise NULL.
   */
  public function getBillingClassLabel($code = NULL) {
    if (empty($code)) {
      return NULL;
    }
    $rates = $this->rates->getFirmSizeOptions();
    $rate_label = $rates[$code] ?? NULL;
    if (empty($rate_label)) {
      return NULL;
    }
    return $rate_label;
  }

  /**
   * Get the Current Billing Class Label.
   *
   * @return string|null
   *   The current billing class label, otherwise NULL.
   */
  public function getCurrentBillingClassLabel() {
    $billing_class_code = $this->info->getNewBillingCode();
    return $this->getBillingClassLabel($billing_class_code);
  }

  /**
   * Get the Previous Billing Class Label.
   *
   * @return string|null
   *   The previous billing class label, otherwise NULL.
   */
  public function getPreviousBillingClassLabel() {
    $billing_class_code = $this->info->getPreviousBillingCode();
    return $this->getBillingClassLabel($billing_class_code);
  }

  /**
   * Get the Firm changes description.
   *
   * @return string|null
   *   The firm changes description.
   */
  public function getFirmChangesDescription() {
    $current_billing_class_label = $this->getCurrentBillingClassLabel();
    if (!$this->info->hasFirmSizeChanges()) {
      return "<strong>Current Firm size:</strong> " . $current_billing_class_label;
    }
    return "<strong>Previous Firm size:</strong> " . $this->getPreviousBillingClassLabel() . '</br>' . "<strong>New Firm size:</strong> " . $current_billing_class_label;
  }

  /**
   * Get the Balance.
   *
   * @return array
   *   The Peer Review Balance table.
   */
  public function getBalance() {
    $rates = [
      '#type' => 'table',
      '#header' => [
        'item' => t('Item'),
        'year' => t('Year'),
        'billed' => t('Billing Date'),
        'amount' => t('Amount'),
        'subtotal' => t('Subtotal'),
      ],
      '#empty' => $this->t('No pending balance was found for this Firm.'),
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface[] $items */
    $items = $this->info->getBillingItems();
    /** @var \Drupal\commerce_price\Price $subtotal */
    $subtotal = NULL;
    $subtotal_price = NULL;
    foreach ($items as $delta => $fee) {
      $note = $fee->getBillingItemLabel();
      if ($fee->isBillingClassCodeAdjustment()) {
        $note = 'Annual Administrative Fee | ' . $note;
      }
      $rates[$delta]['item'] = [
        '#markup' => $note,
      ];
      $rates[$delta]['year'] = [
        '#markup' => $fee->getYear(),
      ];
      $rates[$delta]['billed'] = [
        '#markup' => $fee->getBilledDate(),
      ];
      $rates[$delta]['amount'] = [
        '#markup' => $fee->getFormattedAmount(),
      ];
      $current_price = $fee->getPrice();
      if (empty($subtotal)) {
        $subtotal = $current_price;
      }
      else {
        $subtotal = $subtotal->add($current_price);
      }
      $subtotal_price = '$' . number_format($subtotal->getNumber(), 2, '.', ',');
      $rates[$delta]['subtotal'] = [
        '#markup' => $subtotal_price,
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
              '#markup' => "<strong class='text-float-right'>Total:</strong> ",
              '#allowed_tags' => ['strong', 'class'],
            ],
            'colspan' => 1,
          ],
          [
            'data' => [
              '#markup' => "<h4 class='total-price-peer-review'>{$subtotal_price}</h4>",
              '#allowed_tags' => ['h4', 'class'],
            ],
            'colspan' => 1,
          ],
        ],
      ],
    ];
    return $rates;
  }

}
