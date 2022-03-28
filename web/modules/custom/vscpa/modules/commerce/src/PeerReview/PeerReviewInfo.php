<?php

namespace Drupal\vscpa_commerce\PeerReview;

use Drupal\commerce_price\Price;

/**
 * Defines object that represents AM.net Peer Review Info.
 */
class PeerReviewInfo implements PeerReviewInfoInterface {

  /**
   * The AM.net API HTTP Client.
   *
   * @var \UnleashedTech\AmNet\Api\Client|null
   */
  protected $client = NULL;

  /**
   * The AM.net Firm ID associated with the current Peer review process.
   *
   * @var string|null
   */
  protected $firmId = NULL;

  /**
   * The previous Billing Code associated with the operation.
   *
   * @var string|null
   */
  protected $previousBillingCode = NULL;

  /**
   * The new Billing Code associated with the operation.
   *
   * @var string|null
   */
  protected $newBillingCode = NULL;

  /**
   * The flag value 'Has Firm Size Changes'?.
   *
   * @var bool
   */
  protected $hasFirmSizeChanges = FALSE;

  /**
   * The Peer Review Rates service.
   *
   * @var \Drupal\vscpa_commerce\PeerReview\PeerReviewRatesInterface
   */
  protected $rates = NULL;

  /**
   * The Peer Review data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function getFirmId() {
    return $this->firmId;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmId($firm_id = NULL) {
    // Clear old data associated with another firm.
    $this->resetData();
    $this->firmId = $firm_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousBillingCode() {
    $billing_code = $this->previousBillingCode;
    if (empty($billing_code)) {
      $billing_code = $this->getBillingClassCode();
    }
    return $billing_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviousBillingCode($billing_class_code = NULL) {
    $this->previousBillingCode = $billing_class_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewBillingCode() {
    $billing_code = $this->newBillingCode;
    if (empty($billing_code)) {
      $billing_code = $this->getBillingClassCode();
    }
    return $billing_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewBillingCode($billing_class_code = NULL) {
    $this->newBillingCode = $billing_class_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFirmSizeChanges() {
    return $this->hasFirmSizeChanges;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirmSizeChanges($changes = FALSE) {
    $this->hasFirmSizeChanges = $changes;
  }

  /**
   * {@inheritdoc}
   */
  public function retrievePeerReviewInfo() {
    $firm_id = $this->getFirmID();
    if (empty($firm_id)) {
      $this->resetData();
      return NULL;
    }
    $client = $this->getAmNetClient();
    if (!$client) {
      $this->resetData();
      return NULL;
    }
    $api_path = "/firm/{$firm_id}/peerreview";
    $response = $client->get($api_path);
    if ($response->hasError()) {
      $this->resetData();
      return NULL;
    }
    $this->data = $response->getResult();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resetData() {
    $this->data = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetAicpaNumber() {
    return $this->data['AicpaNumber'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetStatusCode() {
    return $this->data['StatusCode'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetAicpaName() {
    return $this->data['AicpaName'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetContactEmail() {
    return $this->data['ContactEmail'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetContactPhone() {
    return $this->data['ContactPhone'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingClassCode() {
    return $this->data['BillingClassCode'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function reducePreviousBillingClassCodeBalance($base_balance = NULL) {
    $amount = !is_numeric($base_balance) ? '0' : (string) $base_balance;
    $balance_price = new Price($amount, 'USD');
    if ($balance_price->isNegative() || $balance_price->isZero()) {
      // Stop here.
      return $balance_price->getNumber();
    }
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface[] $items */
    $items = $this->getBalanceByYears();
    // Get the Year of the most recent billed transaction.
    $transaction = current($items);
    $current_fiscal_year = $transaction->getYear();
    // Remove transactions from the given year.
    foreach ($items as $delta => $item) {
      if ($item->getYear() != $current_fiscal_year) {
        continue;
      }
      $price = $item->getPrice();
      if ($price->isNegative() || $price->isZero()) {
        continue;
      }
      // Ensure negative value.
      $price = $price->multiply(-1);
      // Reduce the price from the total balance.
      $balance_price = $balance_price->add($price);
    }
    return $balance_price->getNumber();
  }

  /**
   * {@inheritdoc}
   */
  public function addBillingClassCodeBalance($base_balance = NULL) {
    $amount = !is_numeric($base_balance) ? '0' : (string) $base_balance;
    $balance_price = new Price($amount, 'USD');
    $new_billing_class_code_price = $this->getNewBillingCodePrice();
    $total = $balance_price->add($new_billing_class_code_price);
    return $total->getNumber();
  }

  /**
   * {@inheritdoc}
   */
  public function getNewBillingCodePrice() {
    $zero = new Price('0', 'USD');
    // Get Balance form based on the new billing class code.
    $code = $this->getNewBillingCode();
    if (empty($code)) {
      return $zero;
    }
    $amount = $this->getAmountByBillingCode($code);
    if (empty($amount)) {
      return $zero;
    }
    $amount = (string) $amount;
    return new Price($amount, 'USD');
  }

  /**
   * {@inheritdoc}
   */
  public function getBalance() {
    $balance = $this->data['Balance'] ?? NULL;
    if ($this->hasFirmSizeChanges()) {
      // Reduce balance from previous billing class code.
      $balance = $this->reducePreviousBillingClassCodeBalance($balance);
      // Add balance for the new billing class code.
      $balance = $this->addBillingClassCodeBalance($balance);
    }
    return $balance;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalAmNetBalance() {
    return $this->data['Balance'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isFirmActiveOnPeerReviewSystem() {
    return ($this->getAmNetStatusCode() == PeerReviewInfoInterface::ACTIVE_STATUS_CODE);
  }

  /**
   * {@inheritdoc}
   */
  public function getRawTransactions() {
    return $this->data['Transactions'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactions() {
    $raw_transactions = $this->getRawTransactions();
    if (empty($raw_transactions)) {
      return [];
    }
    $items = [];
    foreach ($raw_transactions as $key => $data) {
      $transaction = new PeerReviewTransaction($data);
      $items[] = $transaction;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function sortTransactionsByYear(array $transactions = []) {
    if (empty($transactions)) {
      return [];
    }
    $items = [];
    /* @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $transaction */
    foreach ($transactions as $key => $transaction) {
      $year = $transaction->getYear();
      if (empty($year)) {
        continue;
      }
      if ($transaction->isPrinted()) {
        continue;
      }
      $items[$year][] = $transaction;
    }
    krsort($items);
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getYearApplicableFees(array $transactions = []) {
    if (empty($transactions)) {
      return NULL;
    }
    $items = $this->extractBalance($transactions);
    $fees = $items['fees'];
    if (empty($fees)) {
      // No fees defined for the given period.
      return NULL;
    }
    $payments = $items['payments'];
    if (empty($payments)) {
      // The user has not completed any payments on the given period.
      return $transactions;
    }
    $result = [];
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $balance */
    foreach ($fees as $delta => $balance) {
      $fee = clone $balance;
      if ($fee->isBillingClassCodeAdjustment()) {
        // Stop here: The Billing Class code was previously changed on AM.net
        // by an admin, in this scenario, the current adjustment should contain
        // all the billing items to pay.
        return [$fee];
      }
      // Try to reduce balance until Zero based on the payments.
      foreach ($payments as $key => $payment) {
        $balance = $balance->subtract($payment);
        if ($balance->isZero()) {
          unset($fees[$delta]);
          unset($payments[$key]);
          break;
        }
      }
      if (!$balance->isZero()) {
        $result[] = $fee;
      }
    }
    if (empty($result)) {
      // The users has perfect match between Billing and payments and
      // therefore user has a Zero Balance.
      return NULL;
    }
    // The balance was not reduced to Zero user has pending fees to pay.
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getYearNet(array $transactions = []) {
    return $this->addFees($transactions);
  }

  /**
   * {@inheritdoc}
   */
  public function addFees(array $transactions = []) {
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $fee */
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $balance */
    $balance = NULL;
    foreach ($transactions as $delta => $fee) {
      if (!$balance) {
        $balance = $fee;
      }
      else {
        $balance = $balance->add($fee);
      }
    }
    if (!$balance) {
      return NULL;
    }
    return $balance->getAmount();
  }

  /**
   * {@inheritdoc}
   */
  public function extractBalance(array $transactions = []) {
    $info = [
      'fees' => [],
      'payments' => [],
    ];
    if (empty($transactions)) {
      return $info;
    }
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $transaction */
    foreach ($transactions as $key => $transaction) {
      if ($transaction->isZero()) {
        // Skip transactions with zero fee.
        continue;
      }
      if ($transaction->isPayment() || $transaction->isNegativeAdjustment()) {
        $info['payments'][] = $transaction;
      }
      elseif ($transaction->isFee() || $transaction->isPositiveAdjustment()) {
        $info['fees'][] = $transaction;
      }
    }
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalanceByTransactions(array $transactions = []) {
    if (empty($transactions)) {
      return [];
    }
    $balance = $this->extractBalance($transactions);
    $fees = $balance['fees'];
    if (empty($fees)) {
      // No fees defined for the given period.
      return [];
    }
    $payments = $balance['payments'];
    if (empty($payments)) {
      // The user has not completed any payments on the given period.
      return $fees;
    }
    $items = [];
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $balance */
    foreach ($fees as $delta => $balance) {
      $fee = clone $balance;
      // Try to reduce balance until Zero based on the payments.
      foreach ($payments as $key => $payment) {
        $balance = $balance->subtract($payment);
        if ($balance->isZero()) {
          unset($fees[$delta]);
          unset($payments[$key]);
          break;
        }
      }
      if (!$balance->isZero()) {
        // The balance was not reduced to Zero user has pending fees to pay.
        $items[] = $fee;
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function hasBalance() {
    // Check that we have items with positive value.
    $balance = $this->getAmount();
    if (!$balance) {
      return FALSE;
    }
    $zero = new Price('0', 'USD');
    return $balance->greaterThan($zero);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionsForSync() {
    $transactions = $this->getBalanceByYears();
    if (!$this->hasFirmSizeChanges()) {
      return $transactions;
    }
    // Get the Year of the most recent billed transaction.
    $transaction = current($transactions);
    $current_fiscal_year = $transaction->getYear();
    $items = [];
    // Remove transactions from the given year.
    foreach ($transactions as $delta => $transaction) {
      if ($transaction->getYear() != $current_fiscal_year) {
        $items[] = $transaction;
      }
      else {
        $items[] = $this->convertTransactionIntoNegativeAdjustment($transaction);
      }
    }
    // Handle Billing Class Code changes for these transactions.
    // Add the actual Billing Item for the current Year.
    $balance = $this->getBalanceBasedOnFirmSizeChanges($current_fiscal_year);
    // Handle billing.
    $billing = clone $balance;
    // Change the note.
    $billing->setAccountCode(PeerReviewAccountCodesInterface::ANNUAL_ADMINISTRATIVE_FEE);
    $note = 'Updated to class ' . $this->getNewBillingCode() . ' on ' . date('Y/m/d');
    $billing->setNote($note);
    // Mark the transaction to be treated as 'Adjustment'.
    $billing->setSyncAsAdjustment(TRUE);
    $items[] = $billing;
    // Include the actual payment for the new Billing Class Code.
    $payment = clone $balance;
    $payment->setAccountCode(PeerReviewAccountCodesInterface::ANNUAL_ADMINISTRATIVE_FEE);
    $payment->setNote('Annual Administrative Fee');
    $payment->setSyncAsAdjustment(FALSE);
    $items[] = $payment;
    // Return Items.
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function convertTransactionIntoNegativeAdjustment(PeerReviewTransactionInterface $transaction = NULL) {
    if (!$transaction) {
      return NULL;
    }
    $note = 'Class ' . $this->getPreviousBillingCode() . ' on ' . date('Y/m/d');
    if ($transaction->isZero()) {
      return NULL;
    }
    $price = $transaction->getPrice();
    if (!$price) {
      return NULL;
    }
    // Ensure negative value.
    if ($price->isPositive()) {
      $price = $price->multiply(-1);
    }
    // Set the new Price.
    $transaction->setPrice($price);
    // Mark the transaction to be treated as 'Adjustment'.
    $transaction->setSyncAsAdjustment(TRUE);
    // Calculate Account Code.
    $transaction->setAccountCode();
    // Change Note.
    $transaction->setNote($note);
    // Return converted transaction.
    return $transaction;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalanceByYears() {
    $transactions = $this->getTransactions();
    $items = $this->sortTransactionsByYear($transactions);
    // Current Year.
    $current_year_transactions = current($items);
    $current_year_applicable_fees = $this->getYearApplicableFees($current_year_transactions);
    $current_year_net = ($current_year_applicable_fees) ? $this->getYearNet($current_year_applicable_fees) : NULL;
    if (!is_null($current_year_net) && ($current_year_net == $this->getOriginalAmNetBalance())) {
      // The user only has dues balance on the current fiscal year.
      return $current_year_applicable_fees;
    }
    // The users has dues balance in other fiscal years we need to collect
    // all the monies.
    $rows = [];
    foreach ($items as $year => $trans) {
      $fees = $this->getBalanceByTransactions($trans);
      if (empty($fees)) {
        continue;
      }
      $rows = array_merge($rows, $fees);
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalanceByYearsBasedOnFirmSizeChanges() {
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface[] $items */
    $items = $this->getBalanceByYears();
    if (empty($items)) {
      return [];
    }
    // Get the Year of the most recent billed transaction.
    $transaction = current($items);
    $current_fiscal_year = $transaction->getYear();
    // Remove transactions from the given year.
    $transactions = [];
    foreach ($items as $delta => $item) {
      if ($item->getYear() == $current_fiscal_year) {
        continue;
      }
      $transactions[] = $item;
    }
    // Add the new billing class fee, associated with the firm's size change.
    $new_balance = $this->getBalanceBasedOnFirmSizeChanges($current_fiscal_year);
    array_unshift($transactions, $new_balance);
    return $transactions;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalanceBasedOnFirmSizeChanges($current_fiscal_year = NULL) {
    // Create a new Peer Review transaction Based on the new billing code.
    $year = !empty($current_fiscal_year) ? $current_fiscal_year : date('Y');
    $data = [
      'NamesId' => '',
      'FirmCode' => $this->getFirmId(),
      'TransactionDate' => date('Y-m-d H:i:s'),
      'Year' => $year,
      'TransactionTypeCode' => PeerReviewTransactionInterface::ADJUSTMENT_TRANSACTION_TYPE_CODE,
      'Amount' => $this->getAmountByBillingCode($this->getNewBillingCode()),
      'PaymentMethod' => '',
      'Payor' => '',
      'DepositTo' => '',
      'Note' => $this->getNoteFromBillingCodeChanges(),
      'RecordAdded' => date('Y-m-d H:i:s'),
      'RecordAddedBy' => '',
      'TransactionBatch' => '',
      'CashBatchDocument' => '',
      'OffsetFirmCode' => '',
      'Reversed' => '',
    ];
    return new PeerReviewTransaction($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getNoteFromBillingCodeChanges() {
    $code = $this->getNewBillingCode();
    if (empty($code)) {
      return NULL;
    }
    $rates = $this->getRates()->getFirmSizeOptions();
    $rate_label = $rates[$code] ?? NULL;
    if (empty($rate_label)) {
      return NULL;
    }
    return 'Annual Administrative Fee | ' . $rate_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmountByBillingCode($code) {
    return $this->getRates()->getAmountByBillingCode($code);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedBalance() {
    $amount = $this->getBalance();
    if (empty($amount)) {
      return NULL;
    }
    return '$' . number_format($amount, 2, '.', ',');
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    $amount = $this->getBalance();
    if (empty($amount)) {
      return NULL;
    }
    if (!is_numeric($amount)) {
      return NULL;
    }
    $amount = (string) $amount;
    try {
      $price = new Price($amount, 'USD');
      return $price;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    $items = [];
    $billing_items = $this->getBalanceByYears();
    /** @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $fee */
    foreach ($billing_items as $delta => $fee) {
      $items[] = $fee->toArray();
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data = NULL) {
    $this->data = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRates(PeerReviewRatesInterface $rates_service) {
    $this->rates = $rates_service;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRates() {
    if (!$this->rates) {
      $this->rates = \Drupal::service('vscpa_commerce.peer_review_rates');
    }
    return $this->rates;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmNetClient($amnet_client = NULL) {
    $this->client = $amnet_client;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmNetClient() {
    if (!$this->client) {
      $this->client = \Drupal::service('am_net.client');
    }
    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingItems() {
    if ($this->hasFirmSizeChanges()) {
      $items = $this->getBalanceByYearsBasedOnFirmSizeChanges();
    }
    else {
      $items = $this->getBalanceByYears();
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->getData();
  }

}
