<?php

namespace Drupal\am_net;

use Drupal\Component\Serialization\Json;
use Drupal\user\UserInterface;

/**
 * AM.net membership Dues Rates Helper trait implementation.
 */
trait DuesRatesTrait {

  /**
   * Get the Dues Balance related to a given name record ID.
   *
   * @param string $id
   *   The AM.net name Id.
   *
   * @return bool|array
   *   The AMNet Response with the the array list of Dues Balance,
   *   otherwise FALSE.
   */
  public function getUserDuesByNameId($id = NULL) {
    if (empty($id)) {
      return FALSE;
    }
    $id = trim($id);
    // Try to retrieve the data from the local cache.
    $store_key = "user.dues.name_id:$id";
    $data = $this->cacheGet($store_key);
    if (!empty($data)) {
      // Return the cached data.
      return $data;
    }
    // Get the data from the client.
    $_client = $this->getClient();
    if (!$_client) {
      // The service is not reachable.
      return FALSE;
    }
    $query = $_client->get("Person/{$id}/dues");
    if ($query->hasError()) {
      // The service is not reachable.
      return FALSE;
    }
    $data = $query->getResult();
    // Cache the data, Store the data for at most 30 mins.
    $this->cacheSet($store_key, $data, 1800);
    // Return the data.
    return $data;
  }

  /**
   * Get the Dues Balance related to a given person.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   *
   * @return bool|array
   *   The AMNet Response with the the array list of Dues Balance,
   *   otherwise FALSE.
   */
  public function getUserDues(UserInterface $user = NULL) {
    if (!$user) {
      return FALSE;
    }
    // Get the AM.net ID.
    $id = $user->get('field_amnet_id')->getString();
    return $this->getUserDuesByNameId($id);
  }

  /**
   * Get Dues Rates.
   *
   * @param bool $reset
   *   (optional) The flag that determine if the information must be provided
   *   from the cache or if it must be Rebuild.
   *
   * @return bool|array
   *   The AMNet Response with the the array list of Dues Rates,
   *   otherwise FALSE.
   */
  public function getDuesRates($reset = FALSE) {
    $dues_rates = FALSE;
    $state_key = 'am.net.dues.rates';
    $rates = \Drupal::state()->get($state_key, FALSE);
    if ($reset || ($rates == FALSE)) {
      // Rebuild the data.
      /** @var \UnleashedTech\AMNet\Api\Client $_client */
      $_client = $this->getClient();
      $rates_from_amnet = (!is_null($_client)) ? $_client->get('DuesRates') : FALSE;
      if (!$rates_from_amnet->hasError()) {
        $dues_rates = $rates_from_amnet->getResult();
        \Drupal::state()->set($state_key, Json::encode($dues_rates));
      }
      else {
        // Logs the error.
        \Drupal::logger('am_net')->error($rates_from_amnet->getErrorMessage());
        // Return the last cached data.
        if (!empty($rates)) {
          $dues_rates = Json::decode($rates);
        }
      }
    }
    else {
      $dues_rates = Json::decode($rates);
    }
    return $dues_rates;
  }

  /**
   * Get Membership Dues Price.
   *
   * Membership Dues Price is based on “billing class”
   * value and current date.
   *
   * @param string $billing_class_code
   *   The billing class code.
   * @param string $month
   *   The month of the year.
   *
   * @return float|null
   *   The Dues Pro Rated Amount related to the Month
   *   and the Billing Class code.
   */
  public function getMembershipDuePrice($billing_class_code = NULL, $month = NULL) {
    $membership_due_price = NULL;
    $dues_amount = NULL;
    if ($billing_class_code && $month) {
      $dues_rates = $this->getDuesRates();
      if ($dues_rates != FALSE) {
        // Normalize month.
        $month = ucfirst(strtolower($month));
        $dues_amount_key = 'DuesAmount';
        foreach ($dues_rates as $key => $rate) {
          $key = 'BillingClassCode';
          $suffix = 'ProRatedAmount';
          $dues_amount = isset($rate[$dues_amount_key]) ? $rate[$dues_amount_key] : 0;
          $monthKey = $month . $suffix;
          if (isset($rate[$key]) && ($rate[$key] == $billing_class_code) && isset($rate[$monthKey])) {
            $membership_due_price = $rate[$monthKey];
            break;
          }
        }
      }
    }
    $membership_due_price = ($membership_due_price > 0) ? $membership_due_price : $dues_amount;
    return $membership_due_price;
  }

}
