<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

/**
 * AmNet Event Registration Transactions object representation.
 */
class EventRegistrationTransactions extends AmNetTransactions {

  /**
   * The state prefix.
   *
   * @var string
   */
  protected $statePrefix = 'am_net_event_registration_transactions';

  /**
   * {@inheritdoc}
   */
  public function loadRecords($am_net_name_id) {
    $registrations = [];
    $endpoint = "/Person/{$am_net_name_id}/registrations";
    $response = $this->client->get($endpoint);
    if (!$response->hasError()) {
      $registrations = $response->getResult();
    }
    return $registrations;
  }

  /**
   * {@inheritdoc}
   */
  public function createTransaction(array $order = []) {
    return new EventRegistrationTransaction($order);
  }

}
