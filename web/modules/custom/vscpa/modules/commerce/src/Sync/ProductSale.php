<?php

namespace Drupal\vscpa_commerce\Sync;

use Drupal\am_net\AssociationManagementClient;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Defines object that represent AM.Net Product Sale records.
 */
class ProductSale {

  /**
   * The product code.
   *
   * @var string
   */
  protected $productCode;

  /**
   * The AM.net order data.
   *
   * @var array
   */
  protected $order = [
    'ID' => '',
    // "Online" (O) from PRSM list (Preferences: Shipping Method).
    'ShipPref' => 'P',
    'TranDate' => '',
    // Marketing source code.
    'MS' => '',
    // For "WEb registration".
    'regsource' => 'WE',
    'Note' => '',
    'PayBy' => '',
    'CCAmount' => '',
    // 'NoTranDateEdit' Enables a validation rule that requires transaction
    // date to be within 5 days of current date.
    'NoTranDateEdit' => TRUE,
    // Obsolete. Ignore.
    'PostCCtoPending' => FALSE,
    // The amount of Names MOA to apply.
    'NamesKitty' => 0,
    // The amount of Firms MOA to apply.
    'FirmsKitty' => 0,
    // If the society has multiple credit card accounts, this indicates which
    // account was used to process the transaction.
    'CCAccount' => '',
    'PassportADJ' => 0,
    'Passport' => 0,
    'PassportYr' => '',
    'MG' => '',
    'SendConfirmation' => FALSE,
    'SendPurchaseConfirmationEmail' => TRUE,
    'ExclPub' => FALSE,
    'WaitList' => FALSE,
    'RegStatus' => '',
    // @todo Implement coupons at some point.
    'items' => [],
  ];

  /**
   * Sets the id.
   *
   * @param string $id
   *   The name record id.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProductSale
   *   The called Dues Maintenance object.
   */
  public function setId($id) {
    $this->order['ID'] = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode($value) {
    $this->productCode = $value;
    return $this;
  }

  /**
   * Gets the Code.
   *
   * @return string
   *   The name Code.
   */
  public function getCode() {
    return $this->productCode;
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
  public function setTranDate($value) {
    $this->order['TranDate'] = $value;
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
  public function addItem($value) {
    $this->order['items'][] = $value;
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
  public function setCardno($value) {
    $this->order['Cardno'] = $value;
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
  public function setPayor($value) {
    $this->order['Payor'] = $value;
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
  public function setValue($field_name, $value) {
    $this->order[$field_name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toJson() {
    return json_encode($this->order);
  }

  /**
   * Send the Product Sale to AM.net.
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
    $endpoint = 'ProductSale';
    $am_net_event_order = $this->toJson();
    $result['messages']['endpoint'] = $endpoint;
    $result['messages']['amnet_id'] = $am_net_name_id;
    $result['messages']['request'] = $am_net_event_order;
    // Send the Product Sale to AM.net.
    try {
      $response = $client->post($endpoint, [], $am_net_event_order);
      $result['messages']['response'] = $response;
      if ($error_message = $response->getErrorMessage()) {
        $result['messages']['error_message'] = $error_message;
        $logger->error($error_message);
      }
      else {
        $result['item_processed'] = TRUE;
        // Clear MyCpe block cache.
        $key = "am_net_cpe_my_cpe.{$am_net_name_id}";
        \Drupal::state()->delete($key);
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
