<?php

namespace Drupal\vscpa_commerce\Sync;

use Drupal\am_net\AssociationManagementClient;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Defines object that represent AM.Net Review Payment records.
 */
class ReviewPayment {

  /**
   * The AM.net Review Payment data.
   *
   * @var array
   */
  protected $order = [
    'ID' => '',
    'Firm' => '',
    'Ac' => '',
    'Adjustment' => '',
    'CCAccount' => '',
    'Year' => '',
    'Cardno' => '',
    'Exp' => 'N',
    'PayBy' => '',
    'Payor' => 'WE',
    'RefNbr' => '',
    'Note' => '',
    'PostCCtoPending' => '',
    'CCAmount' => '',
    'TranDate' => '',
    'SendPaymentReceipt' => FALSE,
    'PaymentReceiptDeliveryEmail' => '',
    'BillingClassCode' => '',
  ];

  /**
   * Sets the id.
   *
   * @param string $id
   *   The name record id.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setId($id) {
    $this->order['ID'] = $id;
    return $this;
  }

  /**
   * Gets the id.
   *
   * @return string
   *   The name record id.
   */
  public function getId() {
    return $this->order['ID'];
  }

  /**
   * Sets the firm id.
   *
   * @param string $firm
   *   The firm id.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setFirmId($firm) {
    $this->order['Firm'] = $firm;
    return $this;
  }

  /**
   * Gets the firm id.
   *
   * @return string
   *   The firm id.
   */
  public function getFirmId() {
    return $this->order['Firm'];
  }

  /**
   * Get the Note.
   *
   * @return string
   *   The note value.
   */
  public function getNote() {
    return $this->order['Note'];
  }

  /**
   * Sets AC(Account Code).
   *
   * @param string $code
   *   The Account Code value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setAc($code = NULL) {
    $this->order['Ac'] = $code;
    return $this;
  }

  /**
   * Sets Adjustment.
   *
   * @param string $value
   *   The Adjustment value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setAdjustment($value) {
    $this->order['Adjustment'] = $value;
    return $this;
  }

  /**
   * Sets CCAccount.
   *
   * @param string $value
   *   The AC value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setCcAccount($value) {
    $this->order['CCAccount'] = $value;
    return $this;
  }

  /**
   * Sets Year.
   *
   * @param string $value
   *   The Year value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setYear($value) {
    $this->order['Year'] = $value;
    return $this;
  }

  /**
   * Sets Card No.
   *
   * @param string $value
   *   The Cardno value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setCardno($value) {
    $this->order['Cardno'] = $value;
    return $this;
  }

  /**
   * Sets Exp.
   *
   * @param string $value
   *   The Exp value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setExp($value) {
    $this->order['Exp'] = $value;
    return $this;
  }

  /**
   * Sets PayBy.
   *
   * @param string $value
   *   The PayBy value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setPayBy($value) {
    $this->order['PayBy'] = $value;
    return $this;
  }

  /**
   * Sets Payor.
   *
   * @param string $value
   *   The Payor value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setPayor($value) {
    $this->order['Payor'] = $value;
    return $this;
  }

  /**
   * Sets RefNbr.
   *
   * @param string $value
   *   The RefNbr value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setRefNbr($value) {
    $this->order['RefNbr'] = $value;
    return $this;
  }

  /**
   * Sets Note.
   *
   * @param string $value
   *   The Note value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setNote($value) {
    if (!empty($value)) {
      $value = (string) $value;
    }
    $this->order['Note'] = $value;
    return $this;
  }

  /**
   * Sets PostCctoPending.
   *
   * @param string $value
   *   The PostCctoPending value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setPostCctoPending($value) {
    $this->order['PostCCtoPending'] = $value;
    return $this;
  }

  /**
   * Sets CcAmount.
   *
   * @param string $value
   *   The CcAmount value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setCcAmount($value) {
    $this->order['CCAmount'] = $value;
    return $this;
  }

  /**
   * Sets TranDate.
   *
   * @param string $value
   *   The TranDate value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setTranDate($value) {
    $this->order['TranDate'] = $value;
    return $this;
  }

  /**
   * Sets SendPaymentReceipt.
   *
   * @param string $value
   *   The SendPaymentReceipt value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setSendPaymentReceipt($value) {
    $this->order['SendPaymentReceipt'] = $value;
    return $this;
  }

  /**
   * Sets PaymentReceiptDeliveryEmail.
   *
   * @param string $value
   *   The PaymentReceiptDeliveryEmail value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setPaymentReceiptDeliveryEmail($value) {
    $this->order['PaymentReceiptDeliveryEmail'] = $value;
    return $this;
  }

  /**
   * Sets BillingClassCode.
   *
   * @param string $value
   *   The Billing Class Code value.
   *
   * @return \Drupal\vscpa_commerce\Sync\ReviewPayment
   *   The called Review Payment object.
   */
  public function setBillingClassCode($value) {
    $this->order['BillingClassCode'] = $value;
    return $this;
  }

  /**
   * Converts the object into JSON.
   *
   * @return string
   *   The JSON representation of the object.
   */
  public function toJson() {
    return json_encode($this->order);
  }

  /**
   * Send the Peer Review Payment to AM.net.
   *
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The AM.net API HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The 'vscpa_commerce' logger channel.
   *
   * @return array
   *   The detailed result.
   */
  public function sync(AssociationManagementClient $client = NULL, LoggerChannelInterface $logger = NULL) {
    $result = [
      'item_processed' => FALSE,
      'messages' => [],
    ];
    if (is_null($client) || is_null($logger)) {
      return $result;
    }
    $am_net_name_id = $this->getId();
    if (empty($am_net_name_id)) {
      $am_net_name_id = $this->getFirmId();
    }
    if (empty($am_net_name_id)) {
      return $result;
    }
    $endpoint = 'ReviewPayment';
    $am_net_peer_review_order = $this->toJson();
    $result['messages']['endpoint'] = $endpoint;
    $result['messages']['amnet_id'] = $am_net_name_id;
    $result['messages']['request'] = $am_net_peer_review_order;
    // Send the Review Payment to AM.net.
    try {
      $response = $client->post($endpoint, [], $am_net_peer_review_order);
      $result['messages']['response'] = $response;
      if ($error_message = $response->getErrorMessage()) {
        $result['messages']['error_message'] = $error_message;
        $logger->error($error_message);
      }
      else {
        $result['item_processed'] = TRUE;
      }
    }
    catch (\Exception $e) {
      $error_message = $e->getMessage();
      $result['messages']['error_message'] = $error_message;
      $logger->error($error_message);
    }
    return $result;
  }

}
