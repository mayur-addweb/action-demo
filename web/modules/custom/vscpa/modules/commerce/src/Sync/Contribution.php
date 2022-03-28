<?php

namespace Drupal\vscpa_commerce\Sync;

use Drupal\am_net\AssociationManagementClient;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Defines object that represent AM.Net Donation Contributions records.
 */
class Contribution extends DuesMaintenance {

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
    'SendEFContributionReceipt' => '',
    'SendPACContributionReceipt' => '',
  ];

  /**
   * {@inheritdoc}
   */
  public function setSendEfContributionReceipt($value) {
    $this->order['SendEFContributionReceipt'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSendPacContributionReceipt($value) {
    $this->order['SendPACContributionReceipt'] = $value;
    return $this;
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
   *   The called dues maintenance object.
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
    $am_net_firm_id = $this->getFirmId();
    $am_net_record_id = !empty($am_net_name_id) ? $am_net_name_id : $am_net_firm_id;
    if (empty($am_net_record_id)) {
      return $result;
    }
    $endpoint = 'DuesMaintenance';
    $am_net_membership_order = $this->toJson();
    am_net_logger('Call DuesMaintenance:sync', $am_net_record_id);
    am_net_logger($am_net_membership_order, $am_net_record_id);
    // Send the Dues Maintenance to AM.net.
    try {
      $response = $client->post($endpoint, [], $am_net_membership_order);
      if ($error_message = $response->getErrorMessage()) {
        $logger_error = 'Call DuesMaintenance:sync:response:ErrorMessage: ' . $error_message;
        am_net_logger($logger_error, $am_net_record_id);
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
