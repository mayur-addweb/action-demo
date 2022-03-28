<?php

namespace Drupal\vscpa_commerce\PeerReview;

use Drupal\commerce_price\Price;

/**
 * Defines object that represents general AM.net Peer Review Transaction.
 */
class PeerReviewTransaction implements PeerReviewTransactionInterface {

  /**
   * The number.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $amount = NULL;

  /**
   * The flag that determine if should be synched as Adjustment.
   *
   * @var bool
   */
  protected $syncAsAdjustment = FALSE;

  /**
   * The Account Code.
   *
   * @var string
   */
  protected $accountCode = NULL;

  /**
   * The AM.net billing data.
   *
   * @var array
   */
  protected $billing = [
    'NamesId' => '',
    'FirmCode' => '',
    'TransactionDate' => '',
    'Year' => '',
    'TransactionTypeCode' => '',
    'Amount' => '',
    'PaymentMethod' => '',
    'Payor' => '',
    'DepositTo' => '',
    'Note' => '',
    'RecordAdded' => '',
    'RecordAddedBy' => '',
    'TransactionBatch' => '',
    'CashBatchDocument' => '',
    'OffsetFirmCode' => '',
    'Reversed' => '',
    'Account' => '',
  ];

  /**
   * Constructs a new Transaction object.
   *
   * @param array $data
   *   The billing data info from AM.net API.
   */
  public function __construct(array $data = []) {
    $this->billing = $data;
    // Re-set Account code.
    $this->setAccountCode();
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingData() {
    return $this->billing;
  }

  /**
   * {@inheritdoc}
   */
  public function getNote() {
    $note = $this->billing['Note'] ?? NULL;
    if (empty($note) && $this->isFee()) {
      $generated_note = $this->getFeeLabelByAccountCode();
      $note = !empty($generated_note) ? $generated_note : t('Annual Administrative Fee');
    }
    return $note;
  }

  /**
   * {@inheritdoc}
   */
  public function setNote($note = NULL) {
    $this->billing['Note'] = $note;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getYear() {
    return $this->billing['Year'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    return $this->billing['Amount'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedAmount() {
    $amount = $this->getAmount();
    if (empty($amount)) {
      return NULL;
    }
    return '$' . number_format($amount, 2, '.', ',');
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount($amount = NULL) {
    $this->billing['Amount'] = $amount;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionTypeCode() {
    return $this->billing['TransactionTypeCode'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isPayment() {
    return ($this->getTransactionTypeCode() == PeerReviewTransactionInterface::PAYMENT_TRANSACTION_TYPE_CODE);
  }

  /**
   * {@inheritdoc}
   */
  public function isFee() {
    return ($this->getTransactionTypeCode() == PeerReviewTransactionInterface::FEE_TRANSACTION_TYPE_CODE);
  }

  /**
   * {@inheritdoc}
   */
  public function isAdjustment() {
    return ($this->getTransactionTypeCode() == PeerReviewTransactionInterface::ADJUSTMENT_TRANSACTION_TYPE_CODE);
  }

  /**
   * {@inheritdoc}
   */
  public function isPositiveAdjustment() {
    return ($this->isAdjustment() && $this->greaterOrEqualThanZero());
  }

  /**
   * {@inheritdoc}
   */
  public function isNegativeAdjustment() {
    return ($this->isAdjustment() && !$this->greaterOrEqualThanZero());
  }

  /**
   * {@inheritdoc}
   */
  public function isPrinted() {
    return ($this->getTransactionTypeCode() == PeerReviewTransactionInterface::PRINTED_TRANSACTION_TYPE_CODE);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    if (is_null($this->amount)) {
      $amount = $this->getAmount();
      if (empty($amount)) {
        return NULL;
      }
      if (!is_numeric($amount)) {
        return NULL;
      }
      $amount = (string) $amount;
      try {
        $this->amount = new Price($amount, 'USD');
      }
      catch (\Exception $e) {
        return NULL;
      }
    }
    return $this->amount;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrice(Price $price) {
    $this->amount = $price;
    $this->setAmount($price->getNumber());
  }

  /**
   * {@inheritdoc}
   */
  public function isZero() {
    $price = $this->getPrice();
    if (!$price) {
      return TRUE;
    }
    return $price->isZero();
  }

  /**
   * {@inheritdoc}
   */
  public function greaterThanZero() {
    $zero = new Price('0', 'USD');
    $price = $this->getPrice();
    if (!$price) {
      return FALSE;
    }
    return $price->greaterThan($zero);
  }

  /**
   * {@inheritdoc}
   */
  public function greaterOrEqualThanZero() {
    $zero = new Price('0', 'USD');
    $price = $this->getPrice();
    if (!$price) {
      return FALSE;
    }
    return $price->greaterThanOrEqual($zero);
  }

  /**
   * {@inheritdoc}
   */
  public function subtract(PeerReviewTransactionInterface $fee) {
    // Gather prices.
    $price = $this->getPrice();
    $target_price = $fee->getPrice();
    if ($target_price->isNegative()) {
      // Ensure the subtraction of positive values.
      $target_price = $target_price->multiply(-1);
    }
    // Calculate new price.
    $new_price = $price->subtract($target_price);
    $billing_data = $this->getBillingData();
    // Create a new Fee with the price result.
    $new_fee = new static($billing_data);
    $new_fee->setPrice($new_price);
    return $new_fee;
  }

  /**
   * {@inheritdoc}
   */
  public function add(PeerReviewTransactionInterface $fee) {
    // Gather prices.
    $price = $this->getPrice();
    $target_price = $fee->getPrice();
    // Calculate new price.
    $new_price = $price->add($target_price);
    $billing_data = $this->getBillingData();
    // Create a new Fee with the price result.
    $new_fee = new static($billing_data);
    $new_fee->setPrice($new_price);
    return $new_fee;
  }

  /**
   * {@inheritdoc}
   */
  public function getBilledDate() {
    $data = $this->billing['RecordAdded'] ?? NULL;
    if (empty($data)) {
      return NULL;
    }
    $time = strtotime($data);
    return date('Y-m-d', $time);
  }

  /**
   * {@inheritdoc}
   */
  public function syncAsAdjustment() {
    return $this->syncAsAdjustment;
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncAsAdjustment($value = NULL) {
    $this->syncAsAdjustment = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->billing['Account'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountCode() {
    return $this->accountCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeeLabelByAccountCode() {
    if (empty($this->accountCode)) {
      // Use the billing note.
      return NULL;
    }
    if ($this->accountCode == PeerReviewAccountCodesInterface::ANNUAL_ADMINISTRATIVE_FEE) {
      return 'Annual Administrative Fee';
    }
    if ($this->accountCode == PeerReviewAccountCodesInterface::LATE_FEE) {
      return 'Late Fee';
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingItemLabel() {
    $generated_note = $this->getFeeLabelByAccountCode();
    $note = !empty($generated_note) ? $generated_note : $this->getNote();
    return $note;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountCode($code = NULL) {
    if (empty($code)) {
      // Check the code that comes from AM.net.
      $am_net_account = $this->getAccount();
      if (!empty($am_net_account)) {
        $this->accountCode = $am_net_account;
      }
      else {
        // Determine Account Code from the Notes.
        $note = $this->getNote();
        $note = strtolower($note);
        if ($note == 'annual administrative fee') {
          $this->accountCode = PeerReviewAccountCodesInterface::ANNUAL_ADMINISTRATIVE_FEE;
        }
        elseif ($note == 'late fee') {
          $this->accountCode = PeerReviewAccountCodesInterface::LATE_FEE;
        }
        elseif (strpos($note, 'updated to class') !== FALSE) {
          $this->accountCode = PeerReviewAccountCodesInterface::ANNUAL_ADMINISTRATIVE_FEE;
        }
      }
    }
    else {
      $this->accountCode = $code;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isBillingClassCodeAdjustment() {
    $note = $this->getNote();
    if (empty($note)) {
      return FALSE;
    }
    $note = strtolower($note);
    return (strpos($note, 'updated to class') !== FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->getBillingData();
  }

}
