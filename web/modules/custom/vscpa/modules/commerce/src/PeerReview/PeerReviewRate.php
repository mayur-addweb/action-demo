<?php

namespace Drupal\vscpa_commerce\PeerReview;

/**
 * Defines object that represents individual AM.net Peer Review Rate.
 */
class PeerReviewRate implements PeerReviewRateInterface {

  /**
   * The AM.net rate info.
   *
   * @var array
   */
  protected $info = [
    'billing_class_code' => NULL,
    'fee' => NULL,
    'label' => NULL,
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    if (!isset($values['billing_class_code'])) {
      throw new \InvalidArgumentException('The Billing Class Code is required.');
    }
    if (!isset($values['fee'])) {
      throw new \InvalidArgumentException('The Annual Administrative Fee is required.');
    }
    if (!isset($values['label'])) {
      throw new \InvalidArgumentException('The Annual Administrative Label is required.');
    }
    $rate = new static();
    $rate->setBillingClassCode($values['billing_class_code']);
    $rate->setFee($values['fee']);
    $rate->setLabel($values['label']);
    return $rate;
  }

  /**
   * Set Peer Review Rate base data.
   *
   * @param array $data
   *   The rates data info from AM.net API or locally cached.
   *
   * @throws \InvalidArgumentException
   *   If missing required property.
   */
  public function setData(array $data = []) {
    if (!isset($data['billing_class_code'])) {
      throw new \InvalidArgumentException('The Billing Class Code is required.');
    }
    if (!isset($data['fee'])) {
      throw new \InvalidArgumentException('The Annual Administrative Fee is required.');
    }
    if (!isset($data['label'])) {
      throw new \InvalidArgumentException('The Annual Administrative Label is required.');
    }
    $this->info = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingClassCode() {
    return $this->info['billing_class_code'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFee() {
    return $this->info['fee'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->info['label'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedLabel() {
    return $this->getLabel() . ' - (<strong>' . $this->getFormattedRateFee() . '</strong>)';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedRateFee() {
    $amount = $this->getFee();
    if (empty($amount)) {
      return NULL;
    }
    return '$' . number_format($amount, 2, '.', ',');
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingClassCode($billing_class_code = NULL) {
    $this->info['billing_class_code'] = $billing_class_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFee($fee = NULL) {
    $this->info['fee'] = $fee;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label = NULL) {
    $this->info['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray($label = NULL) {
    return $this->info;
  }

}
