<?php

namespace Drupal\vscpa_commerce\PeerReview;

use Drupal\Core\State\StateInterface;

/**
 * Defines object that represents AM.net Peer Review Rates.
 */
class PeerReviewRates implements PeerReviewRatesInterface, \Iterator {

  /**
   * The AM.net API HTTP Client.
   *
   * @var \UnleashedTech\AMNet\Api\Client|null
   */
  protected $client = NULL;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The rate list.
   *
   * @var \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface[]
   */
  protected $rates = [];

  /**
   * The default rate list coming from AM.net.
   *
   * @var array
   */
  protected $defaultValues = [
    [
      "billing_class_code" => '1',
      "fee" => '190',
      "label" => "Sole proprietor, Engagement review.",
    ],
    [
      "billing_class_code" => '2',
      "fee" => '240',
      "label" => "Sole proprietor, System review.",
    ],
    [
      "billing_class_code" => '3',
      "fee" => '350',
      "label" => "2-5 professionals, Engagement review.",
    ],
    [
      "billing_class_code" => '4',
      "fee" => '450',
      "label" => "2-5 professionals, System review.",
    ],
    [
      "billing_class_code" => '5',
      "fee" => '500',
      "label" => "6-10 professionals, Engagement review.",
    ],
  ];

  /**
   * Class constructor for Peer Review Rates service.
   *
   * @param \Drupal\Core\State\StateInterface $state_service
   *   The State Service.
   * @param mixed $amnet_client
   *   The AM.net API HTTP Client.
   */
  public function __construct(StateInterface $state_service, $amnet_client = NULL) {
    $this->state = $state_service;
    $this->client = $amnet_client;
    $this->buildRateList();
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return [
      'billing_class_code' => t('Billing Class Code'),
      'fee' => t('Annual Administrative Fee'),
      'label' => t('Label'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatEntries(array $rates = []) {
    $items = [];
    if (empty($rates)) {
      return $items;
    }
    foreach ($rates as $delta => $values) {
      try {
        $peer_review_rate = PeerReviewRate::create($values);
      }
      catch (\Exception $e) {
        continue;
      }
      $items[] = $peer_review_rate;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $key = PeerReviewRatesInterface::STORE_KEY;
    $items = [];
    foreach ($this->rates as $delta => $rate) {
      $items[] = $rate->toArray();
    }
    $value = $items;
    $this->state->set($key, $value);
    return SAVED_UPDATED;
  }

  /**
   * {@inheritdoc}
   */
  public function saveFromSubmittedEntries(array $rates = []) {
    $this->rates = $this->formatEntries($rates);
    return $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getRateListFromLocal() {
    $key = PeerReviewRatesInterface::STORE_KEY;
    $items = $this->state->get($key, NULL);
    if (empty($items)) {
      return NULL;
    }
    $rates = [];
    foreach ($items as $delta => $values) {
      try {
        $peer_review_rate = PeerReviewRate::create($values);
      }
      catch (\Exception $e) {
        continue;
      }
      $rates[] = $peer_review_rate;
    }
    return $rates;
  }

  /**
   * Format AM.Net Rates.
   *
   * @param array $list
   *   The array of AM.net rates.
   *
   * @return array
   *   Formatted Peer Review rates Array.
   */
  public function formatAmNetRates(array $list = []) {
    $fee_account_code = 1;
    $rates = [];
    $billing_classes = $list['BillingClasses'] ?? NULL;
    foreach ($billing_classes as $delta => $item) {
      $billing_class_code = $item['BillingClassCode'] ?? NULL;
      $rate_fee = NULL;
      $fees = $item['Rates'] ?? NULL;
      foreach ($fees as $fee) {
        $account_code = $fee['AccountCode'] ?? NULL;
        $amount = $fee['Amount'] ?? NULL;
        if ($account_code == $fee_account_code) {
          $rate_fee = $amount;
          break;
        }
      }
      $rates[] = [
        "billing_class_code" => $billing_class_code,
        "fee" => $rate_fee,
        "label" => 'TBD',
      ];
    }
    return $rates;
  }

  /**
   * {@inheritdoc}
   */
  public function getRateListFromAmNet() {
    $response = $this->client->get('/PeerReviewRates');
    if ($response->hasError()) {
      return FALSE;
    }
    $raw_rates = $response->getResult();
    $amnet_rates = $this->formatAmNetRates($raw_rates);
    $rates = [];
    foreach ($amnet_rates as $delta => $values) {
      try {
        $peer_review_rate = PeerReviewRate::create($values);
      }
      catch (\Exception $e) {
        continue;
      }
      $rates[] = $peer_review_rate;
    }
    return $rates;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchRatesChangesFromAmNet() {
    $amn_net_rates = $this->getRateListFromAmNet();
    if (empty($amn_net_rates)) {
      // It was not possible to fetch the rates from AM.net at this moment,
      // The user needs to try again.
      return FALSE;
    }
    $local_rates = $this->getRateListFromLocal();
    if (empty($local_rates)) {
      // Local rates has non been configured yet, use AM.Net rates.
      $this->rates = $amn_net_rates;
      // Save the changes.
      $this->save();
      return TRUE;
    }
    // Compare Local rates with AM.net rates and save the result.
    $amn_net_rates = $this->indexRatesByBillingCode($amn_net_rates);
    $local_rates = $this->indexRatesByBillingCode($local_rates);
    $rates = [];
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface $rate */
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface $remote_rate */
    foreach ($local_rates as $delta => $rate) {
      $key = $rate->getBillingClassCode();
      if (!isset($amn_net_rates[$key])) {
        continue;
      }
      $remote_rate = $amn_net_rates[$key];
      $rate->setFee($remote_rate->getFee());
      $rates[] = $rate;
    }
    $this->rates = $rates;
    // Save the changes.
    $this->save();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function indexRatesByBillingCode(array $rates = []) {
    if (empty($rates)) {
      return [];
    }
    $items = [];
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface $rate */
    foreach ($rates as $delta => $rate) {
      $key = $rate->getBillingClassCode();
      $items[$key] = $rate;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getPeerReviewAdministrativeFeesNodeId() {
    $key = PeerReviewRatesInterface::STORE_KEY_PEER_REVIEW_ADMINISTRATIVE_FEES_NODE_ID;
    return $this->state->get($key, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function setPeerReviewAdministrativeFeesNodeId($node_id = NULL) {
    $key = PeerReviewRatesInterface::STORE_KEY_PEER_REVIEW_ADMINISTRATIVE_FEES_NODE_ID;
    $this->state->set($key, $node_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowChangeFirmSize() {
    $key = PeerReviewRatesInterface::STORE_KEY_ALLOW_CHANGE_FIRM_SIZE;
    return $this->state->get($key, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowChangeFirmSize($flag = NULL) {
    $key = PeerReviewRatesInterface::STORE_KEY_ALLOW_CHANGE_FIRM_SIZE;
    $this->state->set($key, $flag);
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmSizeOptions() {
    $options = [];
    foreach ($this->rates as $delta => $rate) {
      $key = $rate->getBillingClassCode();
      $options[$key] = $rate->getFormattedLabel();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getRateByBillingClassCode($code) {
    if (empty($code)) {
      return NULL;
    }
    foreach ($this->rates as $delta => $rate) {
      if ($rate->getBillingClassCode() == $code) {
        return $rate;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmountByBillingCode($code) {
    if (empty($code)) {
      return NULL;
    }
    $rate = $this->getRateByBillingClassCode($code);
    if (empty($rate)) {
      return NULL;
    }
    return $rate->getFee();
  }

  /**
   * Build list of all peer review rates.
   */
  public function buildRateList() {
    $rates = $this->getRateListFromLocal();
    if (empty($rates)) {
      $rates = $this->getRateListFromAmNet();
    }
    $this->rates = $rates;
  }

  /**
   * Return the rates count.
   *
   * @return int
   *   The rates count.
   */
  public function getPaymentCount() {
    return count($this->rates);
  }

  /**
   * Return the current element in the array of rates.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface
   *   Return the current element in an array of rates.
   */
  public function current() {
    return current($this->rates);
  }

  /**
   * Move forward to next element item in the array of rates.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface
   *   Advance the internal pointer of an array.
   */
  public function next() {
    return next($this->rates);
  }

  /**
   * Return the key of the current element.
   *
   * @return int|string|null
   *   Fetch a key from an array.
   */
  public function key() {
    return key($this->rates);
  }

  /**
   * Checks if current position is valid.
   *
   * @return bool
   *   TRUE if current position is valid, otherwise FALSE.
   */
  public function valid() {
    return FALSE !== current($this->rates);
  }

  /**
   * Rewind the Iterator to the first element.
   */
  public function rewind() {
    reset($this->rates);
  }

}
