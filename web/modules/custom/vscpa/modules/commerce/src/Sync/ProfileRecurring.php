<?php

namespace Drupal\vscpa_commerce\Sync;

use Drupal\am_net\AssociationManagementClient;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Defines object that represent AM.Net Profile Recurring records.
 */
class ProfileRecurring {

  /**
   * The AM.net profile data.
   *
   * @var array
   */
  protected $profile = [
    'ProfileName' => '',
    'ProfileStart' => '',
    'ProfileEnd' => '',
    'RecurringPeriodCode' => '',
    'ReferenceTransationNumber' => '',
    'ReferenceTransactionAdded' => '',
    'CardExpires' => '',
    'CardNumber' => '',
    'Payor' => '',
    'Distribution' => [],
  ];

  /**
   * Sets the ProfileName.
   *
   * @param string $value
   *   The profile name.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function setProfileName($value) {
    $this->profile['ProfileName'] = $value;
    return $this;
  }

  /**
   * Sets the ProfileStart.
   *
   * @param string $value
   *   The profile start.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function setProfileStart($value) {
    $this->profile['ProfileStart'] = $value;
    return $this;
  }

  /**
   * Sets the ProfileEnd.
   *
   * @param string $value
   *   The profile end.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function setProfileEnd($value) {
    $this->profile['ProfileEnd'] = $value;
    return $this;
  }

  /**
   * Sets the RecurringPeriodCode.
   *
   * @param string $value
   *   The recurring period code.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function setRecurringPeriodCode($value) {
    $this->profile['RecurringPeriodCode'] = $value;
    return $this;
  }

  /**
   * Sets the ReferenceTransationNumber.
   *
   * @param string $value
   *   The reference transation number.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function setReferenceTransationNumber($value) {
    $this->profile['ReferenceTransationNumber'] = $value;
    return $this;
  }

  /**
   * Sets the ReferenceTransactionAdded.
   *
   * @param string $value
   *   The reference transaction added.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function setReferenceTransactionAdded($value) {
    $this->profile['ReferenceTransactionAdded'] = $value;
    return $this;
  }

  /**
   * Sets the CardExpires.
   *
   * @param string $value
   *   The card expires.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function setCardExpires($value) {
    $this->profile['CardExpires'] = $value;
    return $this;
  }

  /**
   * Sets the CardNumber.
   *
   * @param string $value
   *   The card number.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function setCardNumber($value) {
    $this->profile['CardNumber'] = $value;
    return $this;
  }

  /**
   * Sets the Payor.
   *
   * @param string $value
   *   The Payor name.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function setPayor($value) {
    $this->profile['Payor'] = $value;
    return $this;
  }

  /**
   * Add Distribution.
   *
   * @param string $amount
   *   The amount.
   * @param bool $is_dues
   *   Flag that indicate if the contrib is dues.
   * @param bool $is_contribution
   *   Flag that indicate if is a contrib.
   * @param string $contribution_code
   *   The contribution code.
   * @param string $deposit_to
   *   The deposit to.
   * @param string $applied_to
   *   The applied to.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function addDistribution($amount = '', $is_dues = FALSE, $is_contribution = FALSE, $contribution_code = '', $deposit_to = '', $applied_to = '') {
    $this->profile['Distribution'][] = [
      'Amount' => $amount,
      'IsDues' => $is_dues,
      'IsContribution' => $is_contribution,
      'ContributionCode' => $contribution_code,
      'DepositTo' => $deposit_to,
      'AppliedTo' => $applied_to,
    ];
    return $this;
  }

  /**
   * Add group of Distributions.
   *
   * @param array $distributions
   *   The distributions array.
   *
   * @return \Drupal\vscpa_commerce\Sync\ProfileRecurring
   *   The called profile recurring object.
   */
  public function addDistributions(array $distributions = []) {
    if (empty($distributions)) {
      return $this;
    }
    foreach ($distributions as $distribution) {
      // Sanitize data.
      $amount = $distribution['Amount'] ?? '';
      $is_dues = $distribution['IsDues'] ?? FALSE;
      $is_contribution = $distribution['IsContribution'] ?? FALSE;
      $contribution_code = $distribution['ContributionCode'] ?? '';
      $deposit_to = $distribution['DepositTo'] ?? '';
      $applied_to = $distribution['AppliedTo'] ?? '';
      // Add Distribution.
      $this->addDistribution($amount, $is_dues, $is_contribution, $contribution_code, $deposit_to, $applied_to);
    }
    return $this;
  }

  /**
   * Get Json representation of the object.
   *
   * @return string
   *   The string json representation of the object.
   */
  public function toJson() {
    return json_encode($this->profile);
  }

  /**
   * Send the recurring to AM.net.
   *
   * @param string $am_net_name_id
   *   The Name record ID.
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The AM.net API HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The 'vscpa_commerce' logger channel.
   *
   * @return string|null
   *   The recurring profile ID from the API, otherwise  NULL.
   */
  public function sync($am_net_name_id = '', AssociationManagementClient $client = NULL, LoggerChannelInterface $logger = NULL) {
    if (empty($am_net_name_id) || is_null($client) || is_null($logger)) {
      return NULL;
    }
    $recurring_profile_Id = NULL;
    $endpoint = "Person/{$am_net_name_id}/recurring";
    $am_net_profile_request = $this->toJson();
    am_net_logger('Call ProfileRecurring:sync', $am_net_name_id);
    am_net_logger($am_net_profile_request, $am_net_name_id);
    // Send the recurring to AM.net.
    try {
      if ($profile_response = $client->post($endpoint, [], $am_net_profile_request)) {
        if ($profile_response->hasError()) {
          $profile_error = $profile_response->getErrorMessage();
          $logger_error = 'Call ProfileRecurring:sync:profile_response:ErrorMessage: ' . $profile_error;
          am_net_logger($logger_error, $am_net_name_id);
          $logger->error($profile_error);
        }
        else {
          $request_result = $profile_response->getResult();
          $recurring_profile_Id = $request_result['RecurringProfileId'] ?? NULL;
          $logger_msg = 'Call ProfileRecurring:sync:profile_response:getResult(RecurringProfileId): ' . $recurring_profile_Id;
          am_net_logger($logger_msg, $am_net_name_id);
        }
      }
    }
    catch (\Exception $e) {
      $logger->error($e->getMessage());
    }
    return $recurring_profile_Id;
  }

}
