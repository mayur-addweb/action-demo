<?php

namespace Drupal\vscpa_commerce\Sync;

use Drupal\am_net\AssociationManagementClient;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Defines object that represent AM.Net Dues Maintenance records.
 */
class DuesMaintenance {

  /**
   * The AM.net order data.
   *
   * @var array
   */
  protected $order = [
    'ID' => '',
    'Firm' => '',
    'TranDate' => '',
    'AC' => '',
    'OC' => '',
    'MS' => '',
    'Fund' => '',
    'Anonymous' => 'N',
    'Note' => '',
    'regsource' => 'WE',
    'Year' => '',
    'Email' => '',
    'PayBy' => '',
    'Reinstate' => '',
    'ReinstateTY' => '',
    'DuesPayment' => '',
    'CCAmount' => '',
    'DuesBilling' => '',
    'PostZeroDues' => '',
    'NoTranDateEdit' => TRUE,
    'PostCCtoPending' => FALSE,
    'NamesKitty' => 0,
    'FirmsKitty' => 0,
    'CCAccount' => '',
    'DuesPdThru' => '',
    'SendPaymentReceipt' => '',
    'SendMembershipApplicationConfirmation' => '',
    'SendMembershipRenewalEmail' => '',
  ];

  /**
   * Sets the id.
   *
   * @param string $id
   *   The name record id.
   *
   * @return \Drupal\vscpa_commerce\Sync\DuesMaintenance
   *   The called Dues Maintenance object.
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
   * {@inheritdoc}
   */
  public function setAc($value) {
    $this->order['AC'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($value) {
    $this->order['Email'] = $value;
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
  public function setOc($value) {
    $this->order['OC'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirm($value) {
    $this->order['Firm'] = $value;
    return $this;
  }

  /**
   * Gets the Firm id.
   *
   * @return string
   *   The firm record id.
   */
  public function getFirmId() {
    return $this->order['Firm'];
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
  public function setReinstate($value) {
    $this->order['Reinstate'] = $value;
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
  public function setReinstateTy($value) {
    $this->order['ReinstateTY'] = $value;
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
  public function setDuesPdThru($value) {
    $this->order['DuesPdThru'] = $value;
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
  public function setDuesBilling($value) {
    $this->order['DuesBilling'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDuesAdjustment($value) {
    $this->order['DuesAdjustment'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostZeroDues($value) {
    $this->order['PostZeroDues'] = $value;
    return $this;
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
  public function setFund($value) {
    $this->order['Fund'] = $value;
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
  public function setExp($value) {
    $this->order['Exp'] = $value;
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
  public function setRecurringProfileId($value) {
    $this->order['RecurringProfileId'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSendPaymentReceipt($value) {
    $this->order['SendPaymentReceipt'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSendMembershipApplicationConfirmation($value) {
    $this->order['SendMembershipApplicationConfirmation'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSendMembershipRenewalEmail($value) {
    $this->order['SendMembershipRenewalEmail'] = $value;
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
    $this->order['contributions'][] = $contribution;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContributions() {
    return isset($this->order['contributions']) ? $this->order['contributions'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function distributeContributions() {
    $contributions = $this->getContributions();
    if (empty($contributions)) {
      return [];
    }
    $donation_distributions = [];
    foreach ($contributions as $contribution) {
      $donation_distributions[] = [
        'Amount' => $contribution['amt'],
        'IsDues' => FALSE,
        'IsContribution' => TRUE,
        'ContributionCode' => $contribution['code'],
        'DepositTo' => '',
        'AppliedTo' => $contribution['code'],
      ];
    }
    return $donation_distributions;
  }

  /**
   * {@inheritdoc}
   */
  public function toJson() {
    return json_encode($this->order);
  }

  /**
   * Send the Dues Maintenance to AM.net.
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
    $endpoint = 'DuesMaintenance';
    $am_net_membership_order = $this->toJson();
    $result['messages']['endpoint'] = $endpoint;
    $result['messages']['amnet_id'] = $am_net_name_id;
    $result['messages']['request'] = $am_net_membership_order;
    // Send the Dues Maintenance to AM.net.
    try {
      $response = $client->post($endpoint, [], $am_net_membership_order);
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
