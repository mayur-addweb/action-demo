<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

use Drupal\am_net\AssociationManagementClient;
use Drupal\user\UserInterface;

/**
 * Class Payment List.
 *
 * Returns list of all financial activity(Dues, Donations/Contributions, and
 * Event Registrations and Product Sale) for a given user.
 */
class PaymentList implements \Iterator {

  /**
   * The transactions list.
   *
   * @var \Drupal\vscpa_commerce\PaymentHistory\TransactionInterface[]
   */
  protected $transactions = [];

  /**
   * The manager for event registration transactions.
   *
   * @var \Drupal\vscpa_commerce\PaymentHistory\EventRegistrationTransactions
   */
  protected $eventRegistrationTransactions = NULL;

  /**
   * The manager for event registration transactions.
   *
   * @var \Drupal\vscpa_commerce\PaymentHistory\ProductSalesTransactions
   */
  protected $productSalesTransactions = NULL;

  /**
   * The manager for 'Dues Payment Plan' transactions.
   *
   * @var \Drupal\vscpa_commerce\PaymentHistory\DuesPaymentPlanTransactions
   */
  protected $duesPaymentPlanTransactions = NULL;

  /**
   * The manager for 'Dues Payment' transactions.
   *
   * @var \Drupal\vscpa_commerce\PaymentHistory\DuesPaymentTransactions
   */
  protected $duesPaymentTransactions = NULL;

  /**
   * Payment List constructor.
   *
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The AM.net REST API client.
   */
  public function __construct(AssociationManagementClient $client) {
    // Initialize internal variables.
    $this->eventRegistrationTransactions = new EventRegistrationTransactions();
    $this->eventRegistrationTransactions->setClient($client);
    $this->productSalesTransactions = new ProductSalesTransactions();
    $this->productSalesTransactions->setClient($client);
    $this->duesPaymentPlanTransactions = new DuesPaymentPlanTransactions();
    $this->duesPaymentPlanTransactions->setClient($client);
    $this->duesPaymentTransactions = new DuesPaymentTransactions();
    $this->duesPaymentTransactions->setClient($client);
  }

  /**
   * Build list of all financial activity for a given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user used to retrieve financial activity from AM.net.
   */
  public function buildPaymentList(UserInterface $user = NULL) {
    if (!$user->hasField('field_amnet_id')) {
      return;
    }
    $am_net_name_id = $user->get('field_amnet_id')->getString();
    if (empty($am_net_name_id)) {
      return;
    }
    // Load Event Registration Transactions.
    $this->eventRegistrationTransactions->addTransactions($am_net_name_id, $this->transactions);
    // Load Product sales Transactions.
    $this->productSalesTransactions->addTransactions($am_net_name_id, $this->transactions);
    // Load 'Dues Payment Plan' Transactions.
    $this->duesPaymentPlanTransactions->addTransactions($am_net_name_id, $this->transactions);
    // Load 'Dues Payment' Transactions.
    $this->duesPaymentTransactions->addTransactions($am_net_name_id, $this->transactions);
    // Sort transaction by placed time.
    krsort($this->transactions);
  }

  /**
   * Return the transactions count.
   *
   * @return int
   *   The transactions count.
   */
  public function getPaymentCount() {
    return count($this->transactions);
  }

  /**
   * Return the current element in the array of transactions.
   *
   * @return \Drupal\vscpa_commerce\PaymentHistory\TransactionInterface
   *   Return the current element in an array of transactions.
   */
  public function current() {
    return current($this->transactions);
  }

  /**
   * Move forward to next element item in the array of transactions.
   *
   * @return \Drupal\vscpa_commerce\PaymentHistory\TransactionInterface
   *   Advance the internal pointer of an array.
   */
  public function next() {
    return next($this->transactions);
  }

  /**
   * Return the key of the current element.
   *
   * @return int|string|null
   *   Fetch a key from an array.
   */
  public function key() {
    return key($this->transactions);
  }

  /**
   * Checks if current position is valid.
   *
   * @return bool
   *   TRUE if current position is valid, otherwise FALSE.
   */
  public function valid() {
    return FALSE !== current($this->transactions);
  }

  /**
   * Rewind the Iterator to the first element.
   */
  public function rewind() {
    reset($this->transactions);
  }

  /**
   * Get table header.
   *
   * @return array
   *   The table header user for list transaction activity.
   */
  public function getHeader() {
    return [
      t('Order Items'),
      t('Transaction Date'),
      t('CPE Credit Hours Awarded'),
      t('Payment Ref #'),
      t('Total'),
      t('Operations'),
    ];
  }

}
