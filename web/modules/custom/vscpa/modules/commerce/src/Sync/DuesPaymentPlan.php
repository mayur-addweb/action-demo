<?php

namespace Drupal\vscpa_commerce\Sync;

use Drupal\am_net\AssociationManagementClient;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Defines object that represent AM.Net Dues Payment Plan records.
 */
class DuesPaymentPlan {

  /**
   * The AM.net Request Information.
   *
   * @var array
   */
  protected $order = [
    'ID' => '',
    'TranDate' => '',
    'DuesPayment' => '',
    'Cardno' => '',
    'CardExpiresMonth' => '',
    'CardExpiresYear' => '',
    'MS' => '',
    'Note' => '',
    'Year' => '',
    'Payor' => '',
    'PayBy' => '',
    'CCAmount' => '',
    'AuthCode' => '',
    'RefNbr' => '',
    'StoredCardToken' => '',
    'SetupPaymentPlan' => FALSE,
    'AutoRenew' => FALSE,
    'RenewPaymentPlan' => FALSE,
    'SendPaymentPlanConfirmationEmail' => '',
    'Contributions' => [],
  ];

  /**
   * {@inheritdoc}
   */
  public function setSendPaymentPlanConfirmationEmail($value) {
    $this->order['SendPaymentPlanConfirmationEmail'] = $value;
    return $this;
  }

  /**
   * Sets the id.
   *
   * @param string $id
   *   The name record id.
   *
   * @return \Drupal\vscpa_commerce\Sync\DuesPaymentPlan
   *   The called Dues Payment object.
   */
  public function setId($id) {
    $this->order['ID'] = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTranDate($value) {
    $this->order['TranDate'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDuesPayment($value) {
    $this->order['DuesPayment'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDuesPayment() {
    return $this->order['DuesPayment'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardno($value) {
    $this->order['Cardno'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardExpiresMonth($value) {
    $this->order['CardExpiresMonth'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardExpiresYear($value) {
    $this->order['CardExpiresYear'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMarketingSourceCode($value) {
    $this->order['MS'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNote($value) {
    $this->order['Note'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setYear($value) {
    $this->order['Year'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPayor($value) {
    $this->order['Payor'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPayBy($value) {
    $this->order['PayBy'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCcAmount($value) {
    $this->order['CCAmount'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthCode($value) {
    $this->order['AuthCode'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRefNbr($value) {
    $this->order['RefNbr'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoredCardToken($value) {
    $this->order['StoredCardToken'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUpPaymentPlan($value) {
    $this->order['SetupPaymentPlan'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAutoRenew($value) {
    $this->order['AutoRenew'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRenewPaymentPlan($value) {
    $this->order['RenewPaymentPlan'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addContribution($code = '', $anonymous = '', $amt = '', $fund = NULL) {
    $contribution = [
      'code' => $code,
      'anonymous' => $anonymous,
      'amt' => $amt,
    ];
    if (!empty($fund)) {
      $contribution['fund'] = $fund;
    }
    $this->order['Contributions'][] = $contribution;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContributions() {
    return isset($this->order['Contributions']) ? $this->order['Contributions'] : [];
  }

  /**
   * Send the Dues Payment Plan to AM.net.
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
      return $result;
    }
    $endpoint = 'VADuesPayment';
    $am_net_dues_payment = $this->toJson();
    $result['messages']['endpoint'] = $endpoint;
    $result['messages']['amnet_id'] = $am_net_name_id;
    $result['messages']['request'] = $am_net_dues_payment;
    // Send the Dues Payment Plan to AM.net.
    try {
      $response = $client->post($endpoint, [], $am_net_dues_payment);
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
   * {@inheritdoc}
   */
  public function toJson() {
    return json_encode($this->order);
  }

}
