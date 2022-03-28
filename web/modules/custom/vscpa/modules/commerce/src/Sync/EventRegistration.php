<?php

namespace Drupal\vscpa_commerce\Sync;

use Drupal\am_net\AssociationManagementClient;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Defines object that represent AM.Net Event Registration records.
 */
class EventRegistration {

  /**
   * The AM.net order data.
   *
   * @var array
   */
  protected $order = [
    'PromoCode' => '',
    'ID' => '',
    'TranDate' => '',
    // Marketing source code.
    'MS' => '',
    // For "WEb registration".
    'regsource' => 'WE',
    'Note' => '',
    'Yr' => '',
    'Code1' => '',
    'PayBy' => '',
    // 'NoTranDateEdit' Enables a validation rule that requires transaction
    // date to be within 5 days of current date.
    'NoTranDateEdit' => FALSE,
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
    'ExclPub' => FALSE,
    'WaitList' => FALSE,
    'RegStatus' => '',
    'fees' => [],
    'sessions' => [],
  ];

  /**
   * Sets the id.
   *
   * @param string $id
   *   The name record id.
   *
   * @return \Drupal\vscpa_commerce\Sync\EventRegistration
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
  public function setTranDate($value) {
    $this->order['TranDate'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNoTranDateEdit($value) {
    $this->order['NoTranDateEdit'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode1($value) {
    $this->order['Code1'] = $value;
    return $this;
  }

  /**
   * Gets the Code1.
   *
   * @return string
   *   The name Code1.
   */
  public function getCode1() {
    return $this->order['Code1'];
  }

  /**
   * {@inheritdoc}
   */
  public function setYr($value) {
    $this->order['Yr'] = $value;
    return $this;
  }

  /**
   * Gets the Yr.
   *
   * @return string
   *   The name Yr.
   */
  public function getYr() {
    return $this->order['Yr'];
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
  public function addFee($value) {
    $this->order['fees'][] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addSession($value) {
    $this->order['sessions'][] = $value;
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
    if (!empty($value)) {
      $value = (string) $value;
    }
    $this->order['CCAmount'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toJson() {
    return json_encode($this->order);
  }

  /**
   * Send the Event registration to AM.net.
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
    $endpoint = 'EventsServer';
    $am_net_event_order = $this->toJson();
    $result['messages']['endpoint'] = $endpoint;
    $result['messages']['amnet_id'] = $am_net_name_id;
    $result['messages']['request'] = $am_net_event_order;
    // Send the Event registration to AM.net.
    try {
      $response = $client->post($endpoint, [], $am_net_event_order);
      $result['messages']['response'] = $response;
      if ($error_message = $response->getErrorMessage()) {
        $result['messages']['error_message'] = $error_message;
        $logger->error($error_message);
      }
      else {
        $result['item_processed'] = TRUE;
        $state = \Drupal::state();
        // Clear MyCpe block cache.
        $key = "am_net_cpe_my_cpe.{$am_net_name_id}";
        $state->delete($key);
        // Clear AM.Net Event Registrations Cache.
        $event_code = $this->getCode1();
        $event_year = $this->getYr();
        if (!empty($event_code) && !empty($event_year)) {
          $state_key = "am.net.event.registrations.{$event_year}.{$event_code}";
          $state->delete($state_key);
        }
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
