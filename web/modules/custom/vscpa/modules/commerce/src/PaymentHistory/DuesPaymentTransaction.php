<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

use Drupal\am_net\AMNetEntityTypesInterface;
use Drupal\am_net\AMNetEntityTypeContext;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines object that represents 'Dues Payment' Transaction.
 */
class DuesPaymentTransaction extends Transaction {

  /**
   * The AM.net order data.
   *
   * @var array
   */
  protected $order = [];

  /**
   * Set Target Entities IDs.
   */
  public function setTargetEntitiesIds() {
    $id = [];
    $this->targetEntityIDs[] = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeContext() {
    $data = [
      'type' => AMNetEntityTypesInterface::DUES_PAYMENT_PLANS,
      'is_statically_cacheable' => TRUE,
    ];
    return new AMNetEntityTypeContext($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlacedTime() {
    $default = time();
    $added_date = $this->order['TransactionDate'] ?? NULL;
    if (empty($added_date)) {
      return $default;
    }
    return strtotime($added_date);
  }

  /**
   * Is Transaction Paid.
   *
   * @return bool
   *   TRUE if the transaction if Paid, otherwise FALSE.
   */
  public function isTransactionPaid() {
    $payment_status_code = $this->order['PaymentRef'] ?? NULL;
    return (!empty($payment_status_code));
  }

  /**
   * {@inheritdoc}
   */
  public function getCredits() {
    return '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal() {
    $total = NULL;
    if ($this->isDuesTransaction()) {
      $total = $this->order['DuesAmount'] ?? NULL;
    }
    elseif ($this->isContributionTransaction()) {
      $total = $this->order['ContributionAmount'] ?? NULL;
    }
    if (is_numeric($total)) {
      $total = '$' . number_format($total, 2, ".", ",");
    }
    return $total;
  }

  /**
   * Check if is Dues transaction.
   *
   * @return bool
   *   TRUE if the transaction if Dues transaction, otherwise FALSE.
   */
  public function isDuesTransaction() {
    $flag = $this->order['IsDuesTransaction'] ?? FALSE;
    return $flag;
  }

  /**
   * Check if is Contribution transaction.
   *
   * @return bool
   *   TRUE if the transaction if Dues transaction, otherwise FALSE.
   */
  public function isContributionTransaction() {
    $flag = $this->order['IsContributionTransaction'] ?? FALSE;
    return $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentRefNumber() {
    $payment_ref = $this->order['PaymentRef'] ?? NULL;
    if (empty($payment_ref)) {
      return NULL;
    }
    return "<div class='order-item-summary'>{$payment_ref}</div>";
  }

  /**
   * {@inheritdoc}
   * Genertae Receipt download link for Dues & Donation
   */
  public function getOperations() {
    //dump($this->order);
    $date = new DrupalDateTime($this->order['TransactionDate']);
    $transaction_date = $date->format('Y-m-d');
    $names_id = $this->order['NamesId'] ?? NULL;
    if (empty($this->order)){
      return [];
    }
    $links = [];
    $params = [
      'id' => $names_id,
      'transaction_date' => $transaction_date,
    ];
    // Add Receipt link.
    $links[] = [
      'title' => t('<span class="glyphicon glyphicon-file" aria-hidden="true"></span> Receipt'),
      'url' => Url::fromRoute('vscpa_commerce.download_dues_receipt', $params),
      'attributes' => [
        'class' => [
          'receipt_link',
        ],
      ],
    ];

    return [
      '#theme' => 'links',
      '#attributes' => [
        'class' => [
          'order-history-operations',
        ],
      ],
      '#links' => $links,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemsSummary() {
    $dues_year = $this->order['DuesYear'] ?? NULL;
    $title = NULL;
    $type = NULL;
    if ($this->isDuesTransaction()) {
      $title = 'Membership Dues</br>Year: ' . $dues_year;
      $type = 'Dues';
    }
    elseif ($this->isContributionTransaction()) {
      $code = $this->order['ContributionTypeCode'] ?? NULL;
      $title = 'Dues Contribution</br>Year: ' . $dues_year . ' - Type: ' . $code;
      $type = 'Donation';
    }
    if (empty($title)) {
      return NULL;
    }
    return "<div class='order-item-summary dues-transactions'><strong class='label inline'>{$type}:</strong> {$title}</div>";
  }

}
