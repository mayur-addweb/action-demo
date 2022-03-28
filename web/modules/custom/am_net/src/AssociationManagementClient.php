<?php

namespace Drupal\am_net;

use UnleashedTech\AMNet\Api\Client as AssociationManagementApi;
use Drupal\Component\Utility\UrlHelper;

/**
 * Wrapper for the wonderful AM.net HTTP Client.
 */
class AssociationManagementClient {

  use AmNetExpirableCacheTrait, DuesRatesTrait, FirmCodesTrait, UserProfileTrait, LocationCollegesTrait, EventTrait, LegislativeContactsTrait, PaymentPlansTrait, NameTrait;

  /**
   * The AM.net API HTTP Client.
   *
   * @var \UnleashedTech\AMNet\Api\Client|null
   */
  protected $client = NULL;

  /**
   * The (AMS) AM.net API Integration Production Base Url.
   *
   * @var string|null
   */
  protected $apiProdBaseUrl = NULL;

  /**
   * The (AMS) AM.net API Integration Development Base Url.
   *
   * @var string|null
   */
  protected $apiDevBaseUrl = NULL;

  /**
   * The (AMS) AM.net API Integration Production Authentication Api User.
   *
   * @var string|null
   */
  protected $apiProdAuthUser = NULL;

  /**
   * The (AMS) AM.net API Integration Production Authentication Api key.
   *
   * @var string|null
   */
  protected $apiProdAuthKey = NULL;

  /**
   * The (AMS) AM.net API Integration Development Authentication Api User.
   *
   * @var string|null
   */
  protected $apiDevAuthUser = NULL;

  /**
   * The (AMS) AM.net API Integration Development Authentication Api key.
   *
   * @var string|null
   */
  protected $apiDevAuthKey = NULL;

  /**
   * The (AMS) AM.net API Integration connection environment.
   *
   * @var string|null
   */
  protected $apiConnectionEnvironment = NULL;

  /**
   * Constructs a new AssociationManagementClient object.
   */
  public function __construct() {
    $errors = [];
    $auth_user = '';
    $auth_key = '';
    $base_url = '';
    $config = \Drupal::config('am_net.settings');
    // Set the Environment.
    $connection_environment = $config->get('connection_environment');
    if (!strlen($connection_environment) || !in_array($connection_environment, ['production', 'development'])) {
      $errors[] = t('(AMS) AM.net API Error: API Connection Environment cannot be blank.');
    }
    else {
      // Set Api Connection Environment.
      $this->setApiConnectionEnvironment($connection_environment);
      // Set Api access based on Connection Environment.
      if ($this->isDevelopmentConnectionEnvironment()) {
        $prefix = 'api_dev_';
        // Set the Auth User.
        $auth_user = $config->get($prefix . 'auth_user');
        if (!strlen($auth_user)) {
          $errors[] = t('(AMS) AM.net API Error: The development API User cannot be blank.');
        }
        else {
          $this->setApiDevAuthUser($auth_user);
        }
        // Set the Auth key.
        $auth_key = $config->get($prefix . 'auth_key');
        if (!strlen($auth_key)) {
          $errors[] = t('(AMS) AM.net API Error: The development API Key cannot be blank.');
        }
        else {
          $this->setApiDevAuthKey($auth_key);
        }
        // Set the Base Url.
        $base_url = $config->get($prefix . 'base_url');
        if (!strlen($base_url) || !(UrlHelper::isValid($base_url))) {
          $errors[] = t('(AMS) AM.net API Error: The development API Base Url must be valid url.');
        }
        else {
          $this->setApiDevBaseUrl($base_url);
        }
      }
      elseif ($this->isProductionConnectionEnvironment()) {
        $prefix = 'api_prod_';
        // Set the Auth User.
        $auth_user = $config->get($prefix . 'auth_user');
        if (!strlen($auth_user)) {
          $errors[] = t('(AMS) AM.net API Error: The production API User cannot be blank.');
        }
        else {
          $this->setApiProdAuthUser($auth_user);
        }
        // Set the Auth key.
        $auth_key = $config->get($prefix . 'auth_key');
        if (!strlen($auth_key)) {
          $errors[] = t('(AMS) AM.net API Error: The production API Key cannot be blank.');
        }
        else {
          $this->setApiProdAuthKey($auth_key);
        }
        // Set the Base Url.
        $base_url = $config->get($prefix . 'base_url');
        if (!strlen($base_url) || !(UrlHelper::isValid($base_url))) {
          $errors[] = t('(AMS) AM.net API Error: The production API Base Url must be valid url.');
        }
        else {
          $this->setApiProdBaseUrl($base_url);
        }
      }
    }
    if (!empty($errors)) {
      $logger = \Drupal::logger('am_net');
      foreach ($errors as $error) {
        $logger->error($error);
      }
    }
    else {
      // Set Api client.
      if (!empty($base_url) && !empty($auth_user) && !empty($auth_key)) {
        $this->client = new AssociationManagementApi($base_url, $auth_user, $auth_key);
      }
    }
  }

  /**
   * Get Client.
   *
   * @return \UnleashedTech\AMNet\Api\Client|null
   *   The Api Client instance.
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * Check is Development Connection Environment.
   *
   * @return bool
   *   TRUE if the connection environment is development, otherwise FALSE.
   */
  public function isDevelopmentConnectionEnvironment() {
    return $this->getApiConnectionEnvironment() == 'development';
  }

  /**
   * Check is Development Connection Environment.
   *
   * @return bool
   *   TRUE if the connection environment is production, otherwise FALSE.
   */
  public function isProductionConnectionEnvironment() {
    return $this->getApiConnectionEnvironment() == 'production';
  }

  /**
   * Get Api production base url.
   *
   * @return null|string
   *   The Api production base url.
   */
  public function getApiProdBaseUrl() {
    return $this->apiProdBaseUrl;
  }

  /**
   * Set Api production base url.
   *
   * @param null|string $apiProdBaseUrl
   *   The Api production base url.
   */
  public function setApiProdBaseUrl($apiProdBaseUrl = NULL) {
    $this->apiProdBaseUrl = $apiProdBaseUrl;
  }

  /**
   * Get Api development base url.
   *
   * @return null|string
   *   The Api development base url.
   */
  public function getApiDevBaseUrl() {
    return $this->apiDevBaseUrl;
  }

  /**
   * Set Api development base url.
   *
   * @param null|string $apiDevBaseUrl
   *   The Api development base url.
   */
  public function setApiDevBaseUrl($apiDevBaseUrl = NULL) {
    $this->apiDevBaseUrl = $apiDevBaseUrl;
  }

  /**
   * Get Api production Authentication User.
   *
   * @return null|string
   *   The Api production Authentication User.
   */
  public function getApiProdAuthUser() {
    return $this->apiProdAuthUser;
  }

  /**
   * Set Api production Authentication User.
   *
   * @param null|string $apiProdAuthUser
   *   The Api production Authentication User.
   */
  public function setApiProdAuthUser($apiProdAuthUser = NULL) {
    $this->apiProdAuthUser = $apiProdAuthUser;
  }

  /**
   * Get Api production Authentication key.
   *
   * @return null|string
   *   The Api production Authentication key.
   */
  public function getApiProdAuthKey() {
    return $this->apiProdAuthKey;
  }

  /**
   * Set Api production Authentication key.
   *
   * @param null|string $apiProdAuthKey
   *   The Api production Authentication key.
   */
  public function setApiProdAuthKey($apiProdAuthKey = NULL) {
    $this->apiProdAuthKey = $apiProdAuthKey;
  }

  /**
   * Get Api development Authentication User.
   *
   * @return null|string
   *   The Api development Authentication User.
   */
  public function getApiDevAuthUser() {
    return $this->apiDevAuthUser;
  }

  /**
   * Set Api development Authentication User.
   *
   * @param null|string $apiDevAuthUser
   *   The Api development Authentication User.
   */
  public function setApiDevAuthUser($apiDevAuthUser = NULL) {
    $this->apiDevAuthUser = $apiDevAuthUser;
  }

  /**
   * Get Api development Authentication Key.
   *
   * @return null|string
   *   The Api development Authentication Key.
   */
  public function getApiDevAuthKey() {
    return $this->apiDevAuthKey;
  }

  /**
   * Set Api development Authentication Key.
   *
   * @param null|string $apiDevAuthKey
   *   The Api development Authentication Key.
   */
  public function setApiDevAuthKey($apiDevAuthKey = NULL) {
    $this->apiDevAuthKey = $apiDevAuthKey;
  }

  /**
   * Get Api Connection Environment.
   *
   * @return null|string
   *   The Api Connection Environment.
   */
  public function getApiConnectionEnvironment() {
    return $this->apiConnectionEnvironment;
  }

  /**
   * Set Api Connection Environment.
   *
   * @param null|string $apiConnectionEnvironment
   *   The Api Connection Environment.
   */
  public function setApiConnectionEnvironment($apiConnectionEnvironment = NULL) {
    $this->apiConnectionEnvironment = $apiConnectionEnvironment;
  }

  /**
   * Execute a GET request against the API.
   *
   * @param string $apiPath
   *   The Api Path.
   * @param array $queryParams
   *   The array query params.
   * @param string $format
   *   The format used on the response.
   *
   * @return \UnleashedTech\AMNet\Api\AMNetResponseInterface
   *   The GET request response.
   */
  public function get($apiPath, array $queryParams = [], $format = 'array') {
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    $query = (!is_null($_client)) ? $_client->get($apiPath, $queryParams, $format) : NULL;
    return $query;
  }

  /**
   * Execute a POST request against the API.
   *
   * @param string $apiPath
   *   The Api Path.
   * @param array $queryParams
   *   The array query params.
   * @param string $data
   *   The serialized JSON data.
   * @param string $format
   *   The format used on the response.
   *
   * @return \UnleashedTech\AMNet\Api\AMNetResponseInterface
   *   The POST request response.
   */
  public function post($apiPath, array $queryParams = [], $data = '', $format = 'array') {
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    $query = (!is_null($_client)) ? $_client->post($apiPath, $queryParams, $data, $format) : NULL;
    return $query;
  }

  /**
   * Execute a PUT request against the API.
   *
   * @param string $apiPath
   *   The Api Path.
   * @param array $queryParams
   *   The array query params.
   * @param string $data
   *   The serialized JSON data.
   * @param string $format
   *   The format used on the response.
   *
   * @return \UnleashedTech\AMNet\Api\AMNetResponseInterface|null
   *   The PUT request response when the operation was successfully completed,
   *   otherwise NULL Otherwise Null.
   */
  public function put($apiPath, array $queryParams = [], $data = '', $format = 'array') {
    /** @var \UnleashedTech\AMNet\Api\Client $_client */
    $_client = $this->getClient();
    $query = (!is_null($_client)) ? $_client->put($apiPath, $queryParams, $data, $format) : NULL;
    return $query;
  }

}
