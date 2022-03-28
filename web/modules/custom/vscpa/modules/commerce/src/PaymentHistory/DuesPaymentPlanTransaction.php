<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

use Drupal\am_net\AMNetEntityTypesInterface;
use Drupal\am_net\AMNetEntityTypeContext;

/**
 * Defines object that represents 'Dues Payment Plan' Transaction.
 */
class DuesPaymentPlanTransaction extends Transaction {

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
      'type' => AMNetEntityTypesInterface::PAYMENT_PLANS,
      'is_statically_cacheable' => TRUE,
    ];
    return new AMNetEntityTypeContext($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlacedTime() {
    $default = time();
    $added_date = $this->order['ProcessDate'] ?? NULL;
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
    $payment_status_code = $this->order['PaymentStatusCode'] ?? NULL;
    return ($payment_status_code == 'P');
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
    $total = $this->order['Amount'] ?? NULL;
    if (is_numeric($total)) {
      $total = '$' . number_format($total, 2, ".", ",");
    }
    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentRefNumber() {
    $payment_ref = $this->order['PaymentRefNbr'] ?? NULL;
    if (empty($payment_ref)) {
      return NULL;
    }
    return "<div class='order-item-summary'>{$payment_ref}</div>";
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemsSummary() {
    $applied_to = $this->order['AppliedTo'] ?? NULL;
    if (empty($applied_to)) {
      return NULL;
    }
    $applied_to = trim($applied_to);
    $dues_year = $this->order['Year'] ?? NULL;
    $dues_year = trim($dues_year);
    if (!empty($dues_year)) {
      $dues_year = '— 20' . $dues_year;
    }
    if ($applied_to == 'PF Contribution') {
      $applied_to = 'Payment Plan Administrative Fee ' . $dues_year;
    }
    $title = $applied_to;
    if ($applied_to == 'Dues') {
      $title = 'Membership ' . $applied_to . ' ' . $dues_year;
    }
    $summaries = [];
    $summaries[] = $this->getOrderItemTemplate($title, []);
    if (empty($summaries)) {
      return NULL;
    }
    return implode('</br>', $summaries);
  }

}
