<?php

namespace Drupal\vscpa_sso;

use Drupal\user\UserInterface;
use Drupal\vscpa_sso\Entity\Name;
use Drupal\vscpa_sso\Entity\Email;
use Drupal\vscpa_sso\Entity\GluuUser;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Drupal\vscpa_sso\Exception\SSOException;
use Drupal\vscpa_sso\Exception\AccessTokenException;
use Drupal\vscpa_sso\Exception\OpenIDConnectClientException;

/**
 * Wrapper for the wonderful Gluu Client HTTP Client.
 */
class GluuClient {

  /**
   * The client ID.
   *
   * @var string
   */
  private $clientID;

  /**
   * The client name.
   *
   * @var string
   */
  private $clientName = 'sso_api_admin';

  /**
   * The client secret value.
   *
   * @var string
   */
  private $clientSecret;

  /**
   * Holds the provider configuration.
   *
   * @var array
   */
  private $providerConfig = [
    'issuer' => 'https://sso.vscpa.com',
    'token_endpoint' => 'oxauth/restv1/token',
    'user_endpoint' => 'identity/restv1/scim/v2/Users',
    'group_endpoint' => 'identity/restv1/scim/v2/Groups',
    'registration_endpoint' => 'oxauth/restv1/register',
  ];

  /**
   * The http proxy if necessary.
   *
   * @var string
   */
  private $httpProxy;

  /**
   * The full system path to the SSL certificate.
   *
   * @var string
   */
  private $certPath;

  /**
   * The bool Verify SSL peer on transactions.
   *
   * @var bool
   */
  private $verifyPeer = TRUE;

  /**
   * The bool Verify peer hostname on transactions.
   *
   * @var bool
   */
  private $verifyHost = TRUE;

  /**
   * The access token.
   *
   * @var string
   */
  private $accessToken;

  /**
   * The if we acquire a refresh token it will be stored here..
   *
   * @var string
   */
  private $refreshToken;

  /**
   * The Token id.
   *
   * @var string
   */
  private $idToken;

  /**
   * The Token response.
   *
   * @var string
   */
  private $tokenResponse;

  /**
   * The array of scopes.
   *
   * @var array
   */
  private $scopes = [];

  /**
   * The token Storage.
   *
   * @var mixed
   */
  private $tokenStorage;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient = NULL;

  /**
   * Constructs a new GluuClient object.
   */
  public function __construct() {
    $config = \Drupal::config('vscpa_sso.settings');
    $provider_url = $config->get('api_provider_url');
    $client_id = $config->get('api_client_id');
    $client_secret = $config->get('api_client_secret');
    $this->setProviderUrl($provider_url);
    $this->clientID = $client_id;
    $this->clientSecret = $client_secret;
    $this->tokenStorage = new SessionTokenStorage();
  }

  /**
   * Set config param.
   *
   * @param array $config
   *   The array of config params.
   */
  public function providerConfigParam(array $config = []) {
    $this->providerConfig = array_merge($this->providerConfig, $config);
  }

  /**
   * Get the Http Proxy.
   *
   * @return string
   *   The Http Proxy.
   */
  public function getHttpProxy() {
    return $this->httpProxy;
  }

  /**
   * Get the cert path.
   *
   * @return string
   *   The cert path.
   */
  public function getCertPath() {
    return $this->certPath;
  }

  /**
   * Get the Verify Peer.
   *
   * @return string
   *   The verify peer.
   */
  public function getVerifyPeer() {
    return $this->verifyPeer;
  }

  /**
   * Get the Verify Host.
   *
   * @return string
   *   The verify host.
   */
  public function getVerifyHost() {
    return $this->verifyHost;
  }

  /**
   * Get the access token.
   *
   * @return string
   *   The access token.
   */
  public function getAccessToken() {
    return $this->accessToken;
  }

  /**
   * Get the refresh token.
   *
   * @return string
   *   The refresh token.
   */
  public function getRefreshToken() {
    return $this->refreshToken;
  }

  /**
   * Get the id token.
   *
   * @return string
   *   The id token.
   */
  public function getIdToken() {
    return $this->idToken;
  }

  /**
   * Get the token response.
   *
   * @return string
   *   The token response.
   */
  public function getTokenResponse() {
    return $this->tokenResponse;
  }

  /**
   * Get the Scopes.
   *
   * @return array
   *   The scopes.
   */
  public function getScopes() {
    return $this->scopes;
  }

  /**
   * Set http proxy.
   *
   * @param string $httpProxy
   *   The http proxy.
   */
  public function setHttpProxy($httpProxy) {
    $this->httpProxy = $httpProxy;
  }

  /**
   * Set cert path.
   *
   * @param string $certPath
   *   The cert path.
   */
  public function setCertPath($certPath) {
    $this->certPath = $certPath;
  }

  /**
   * Set verify peer.
   *
   * @param string $verifyPeer
   *   The verify peer.
   */
  public function setVerifyPeer($verifyPeer) {
    $this->verifyPeer = $verifyPeer;
  }

  /**
   * Set verify host.
   *
   * @param string $verifyHost
   *   The verify host.
   */
  public function setVerifyHost($verifyHost) {
    $this->verifyHost = $verifyHost;
  }

  /**
   * Set access token.
   *
   * @param string $accessToken
   *   The access token.
   */
  public function setAccessToken($accessToken) {
    $this->accessToken = $accessToken;
  }

  /**
   * Set refresh token.
   *
   * @param string $refreshToken
   *   The refresh token.
   */
  public function setRefreshToken($refreshToken) {
    $this->refreshToken = $refreshToken;
  }

  /**
   * Set id token.
   *
   * @param string $idToken
   *   The id token.
   */
  public function setIdToken($idToken) {
    $this->idToken = $idToken;
  }

  /**
   * Set token response.
   *
   * @param string $tokenResponse
   *   The token response.
   */
  public function setTokenResponse($tokenResponse) {
    $this->tokenResponse = $tokenResponse;
  }

  /**
   * Set scopes.
   *
   * @param array $scopes
   *   The scopes.
   */
  public function setScopes(array $scopes = []) {
    $this->scopes = $scopes;
  }

  /**
   * Get Client name.
   *
   * @return string
   *   The Client name.
   */
  public function getClientName() {
    return $this->clientName;
  }

  /**
   * Set Client Name.
   *
   * @param string $clientName
   *   The client name.
   */
  public function setClientName($clientName) {
    $this->clientName = $clientName;
  }

  /**
   * Set provider url.
   *
   * @param string $provider_url
   *   The provider url.
   */
  public function setProviderUrl($provider_url) {
    $this->providerConfig['issuer'] = $provider_url;
  }

  /**
   * Get provider url.
   *
   * @return string
   *   The provider url.
   */
  public function getProviderUrl() {
    return $this->providerConfig['issuer'] ?? '/';
  }

  /**
   * Get provider config value.
   *
   * @param string $param
   *   The param name.
   *
   * @throws \Drupal\vscpa_sso\Exception\OpenIDConnectClientException
   *   Thrown if the config is not found.
   *
   * @return mixed
   *   The config value.
   */
  private function getProviderConfigValue($param) {
    if (isset($this->providerConfig[$param])) {
      return $this->providerConfig[$param];
    }
    throw new OpenIDConnectClientException("The provider {$param} has not been set. Make sure your provider has a well known configuration available.");
  }

  /**
   * Add default options.
   *
   * @param array $options
   *   The array of options.
   */
  private function getOptions(array &$options = []) {
    $options['verify'] = $this->verifyPeer;
    $options['proxy'] = $this->httpProxy;
    $options['allow_redirects'] = FALSE;
  }

  /**
   * Requests ID and Access tokens.
   *
   * @throws \Drupal\vscpa_sso\Exception\AccessTokenException
   *   Thrown if error is returner from the Gluu API.
   *
   * @return mixed
   *   The config value.
   */
  public function requestTokens() {
    $accessTokenList = $this->tokenStorage->getAccessTokenList($this->clientName);
    if (!empty($accessTokenList)) {
      /* @var \Drupal\vscpa_sso\AccessToken $accessToken */
      foreach ($accessTokenList as $accessToken) {
        if ($accessToken->isExpired()) {
          $this->tokenStorage->deleteAccessToken($this->clientName, $accessToken);
          break;
        }
        return $accessToken;
      }
    }
    // Get token from the API.
    $token_endpoint = $this->getProviderConfigValue('token_endpoint');
    $grant_type = 'client_credentials';
    $options = [
      "form_params" => [
        "grant_type" => $grant_type,
      ],
      "auth" => [
        $this->clientID,
        $this->clientSecret,
      ],
    ];

    $this->getOptions($options);
    $response = $this->post(
      $token_endpoint, $options
    );
    if ($response->getStatusCode() == 200) {
      $accessToken = AccessToken::fromJson((string) $response->getBody());
      $this->tokenStorage->storeAccessToken($this->clientName, $accessToken);
      return $accessToken;
    }
    throw new AccessTokenException("Getting code {$response->getStatusCode()} from SSO server.");
  }

  /**
   * Create User on Gluu Server.
   *
   * @param \Drupal\vscpa_sso\Entity\GluuUser $user
   *   The Collection metadata.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser|bool
   *   The saved Gluu User object on success, otherwise FALSE.
   */
  public function createUser(GluuUser $user) {
    $accessToken = $this->requestTokens();
    $endpoint = $this->getProviderConfigValue("user_endpoint");
    $user_json = $user->arrayFromObject(FALSE);
    $user_json_string = $user->json();
    $options = [
      "headers" => [
        "Authorization" => sprintf('Bearer %s', $accessToken->getToken()),
      ],
      'json' => $user_json,
    ];
    try {
      $this->getOptions($options);
      $response = $this->post(
        $endpoint, $options
      );
      if ($response->getStatusCode() == 201) {
        return GluuUser::fromJson((string) $response->getBody());
      }
      $log_error = "Getting code {$response->getStatusCode()} from SSO server while creating an user's information: " . $user_json_string;
    }
    catch (ClientException $ex) {
      if ($ex->getCode() == 409) {
        $log_error = "Create User conflict: Username/External id already been used: " . $user_json_string;
      }
      else {
        $log_error = "Getting code {$ex->getCode()} from SSO server in creating new user." . $user_json_string;
      }
    }
    catch (\Exception $e) {
      $log_error = "Getting code {$e->getCode()} from SSO server in creating new user." . $user_json_string;
    }
    if (!empty($log_error)) {
      // Logs an error.
      \Drupal::logger('vscpa_sso')->error($log_error);
      // Save the log in custom state variable.
      $error = [
        'operation' => 'createUser',
        'log_error' => $log_error,
        'json_entity' => $user_json_string,
        'time' => time(),
      ];
      $this->logSyncError($error, "vscpa_sso.gluu.user.profile.clean.up");
    }
    return FALSE;
  }

  /**
   * Retrieve User from Gluu Server.
   *
   * @param string $user_name
   *   The Account userName.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser|bool
   *   The Gluu User account if exist otherwise FALSE.
   */
  public function getByUserName($user_name = NULL) {
    if (empty($user_name)) {
      return FALSE;
    }
    $query = [
      'filter' => 'userName co "' . $user_name . '"',
    ];
    $collection = $this->getUsers($query);
    if (!$collection || !($collection->getTotalResults() > 0)) {
      return FALSE;
    }
    $resources = $collection->getResources();
    $user = current($resources);
    if (!($user instanceof GluuUser)) {
      return FALSE;
    }
    return $user;
  }

  /**
   * Retrieve User from Gluu Server.
   *
   * @param string $mail
   *   The Account Email.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser|bool
   *   The Gluu User account if exist otherwise FALSE.
   */
  public function getByMail($mail = NULL) {
    if (empty($mail)) {
      return FALSE;
    }
    $mail = strtolower($mail);

    $query = [
      'filter' => 'emails.value eq "' . $mail . '"',
    ];

    $collection = $this->getUsers($query);
    if (!$collection || !($collection->getTotalResults() > 0)) {
      return FALSE;
    }
    $resources = $collection->getResources();
    $user = current($resources);
    if (!($user instanceof GluuUser)) {
      return FALSE;
    }
    return $user;
  }

  /**
   * Retrieve User from Gluu Server by external ID.
   *
   * @param string $external_id
   *   The external ID.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser|bool
   *   The Gluu User account if exist otherwise FALSE.
   */
  public function getExternalId($external_id = NULL) {
    if (empty($external_id)) {
      return FALSE;
    }
    $external_id = strtolower($external_id);

    $query = [
      'filter' => 'userName eq "' . $external_id . '"',
    ];

    $collection = $this->getUsers($query);
    if (!$collection || !($collection->getTotalResults() > 0)) {
      return FALSE;
    }
    $resources = $collection->getResources();
    $user = current($resources);
    if (!($user instanceof GluuUser)) {
      return FALSE;
    }
    return $user;
  }

  /**
   * Retrieve User from Gluu Server.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   * @param string $email
   *   The Gluu UID.
   * @param string $am_net_id
   *   The am_net ID.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser|false
   *   The Gluu User, otherwise FALSE.
   */
  public function tryGetGluuAccount(UserInterface $user = NULL, $email = NULL, $am_net_id = NULL) {
    if (!$user && empty($email) && empty($am_net_id)) {
      return FALSE;
    }
    $gluu_uid = $user->get('field_sso_id')->getString();
    if (!empty($gluu_uid)) {
      // User already linked to a Gluu account.
      $user = new GluuUser();
      $user->id = $gluu_uid;
      return $user;
    }
    // Try to get the Gluu account by Email.
    $gluu_account = $this->getByMail($email);
    if ($gluu_account) {
      return $gluu_account;
    }
    $am_net_id = trim($am_net_id);
    // Try to get the Gluu account by AM.net ID.
    $gluu_account = $this->getExternalId($am_net_id);
    return $gluu_account;
  }

  /**
   * Retrieve User from Gluu Server.
   *
   * @param string $uid
   *   The Gluu UID.
   *
   * @throws \InvalidArgumentException
   *   Thrown if none valid Uid is provided information.
   * @throws \Drupal\vscpa_sso\Exception\SSOException
   *   Thrown if SSO server error while fetching an user's information.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser
   *   The Gluu User.
   */
  public function getUser($uid = NULL) {
    if (empty($uid)) {
      throw new \InvalidArgumentException('Please provide a valid gluu user id.');
    }
    $accessToken = $this->requestTokens();
    $endpoint = $this->getProviderConfigValue("user_endpoint");
    $endpoint = "{$endpoint}/{$uid}?att";
    $options = [
      "headers" => [
        "Authorization" => sprintf('Bearer %s', $accessToken->getToken()),
      ],
    ];
    $this->getOptions($options);
    $response = $this->get(
      $endpoint, $options
    );
    if ($response->getStatusCode() == 200) {
      return GluuUser::fromJson((string) $response->getBody());
    }
    throw new SSOException("Getting code {$response->getStatusCode()} from SSO server while fetching an user's information.");
  }

  /**
   * Fetch Users from Gluu Server.
   *
   * @param array $query
   *   The query for filtering users.
   *
   * @throws \Drupal\vscpa_sso\Exception\SSOException
   *   Thrown if SSO server error while fetching an user's information.
   *
   * @return Collection
   *   The Collection of gluu user.
   */
  public function getUsers(array $query = []) {
    $accessToken = $this->requestTokens();
    $endpoint = $this->getProviderConfigValue("user_endpoint");
    $options = [
      "headers" => [
        "Authorization" => sprintf('Bearer %s', $accessToken->getToken()),
      ],
      'query' => $query,
    ];
    $this->getOptions($options);
    $response = $this->get(
      $endpoint, $options
    );
    if (!is_string($response) && ($response->getStatusCode() == 200)) {
      return Collection::fromJson((string) $response->getBody(), 'USER');
    }
    return NULL;
  }

  /**
   * Update Users on Gluu Server.
   *
   * @param string $id
   *   The Gluu user id.
   * @param \Drupal\vscpa_sso\Entity\GluuUser $user
   *   The Gluu user entity.
   * @param bool $include_pass
   *   Flag that determine of the password should be updated.
   *
   * @throws \Drupal\vscpa_sso\Exception\SSOException
   *   Thrown if SSO server error while fetching an user's information.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser|bool
   *   The updated gluu user, otherwise FALSE.
   */
  public function updateUser($id = '', GluuUser $user = NULL, $include_pass = FALSE) {
    if (empty($id) || is_null($user)) {
      return FALSE;
    }
    $accessToken = $this->requestTokens();
    $endpoint = $this->getProviderConfigValue("user_endpoint") . '/' . $id;
    $user_array = $user->arrayFromObject(FALSE);
    $password = $user_array['password'] ?? NULL;
    if (empty($password) || ($password == "Hidden for Privacy Reasons")) {
      // Remove Dummy password from the array.
      unset($user_array['password']);
    }
    $user_array = array_filter($user_array);
    $options = [
      "headers" => [
        "Authorization" => sprintf('Bearer %s', $accessToken->getToken()),
      ],
      'json' => $user_array,
    ];
    $exception = FALSE;
    try {
      $this->getOptions($options);
      $response = $this->put($endpoint, $options);
      if ($response->getStatusCode() == 200) {
        return GluuUser::fromJson((string) $response->getBody());
      }
      $log_error = "Getting code {$response->getStatusCode()} from SSO server while updating user's information.";
    }
    catch (\Exception $e) {
      $exception = TRUE;
      $exception_code = $e->getCode();
      if ($e instanceof ClientException) {
        $response = $e->getResponse();
        $response = (string) $response->getBody();
        $log_error = "Update user ClientException({$exception_code}): " . $response;
      }
      elseif ($e instanceof ServerException) {
        $response = $e->getResponse();
        $response = (string) $response->getBody();
        $log_error = "Update user ServerException({$exception_code}): " . $response;
      }
      else {
        $response = $e->getMessage();
        $log_error = "Update user Exception: Getting code {$exception_code} from SSO server in updating user." . $response;
      }
      if ($exception_code == 500) {
        // It is a known error in Gluu API so do not register it.
        $log_error = "";
      }
    }
    if (!empty($log_error)) {
      // Logs an error.
      \Drupal::logger('vscpa_sso')->error($log_error);
      // Save the log in custom state variable.
      $error = [
        'operation' => 'updateUser',
        'log_error' => $log_error,
        'id' => $id,
        'json_entity' => json_encode($user_array),
        'time' => time(),
      ];
      $this->logSyncError($error, "vscpa_sso.gluu.user.profile.clean.up");
    }
    if ($exception && isset($user_array['emails'][0]['value'])) {
      // Check if the email was updated.
      $email = $user_array['emails'][0]['value'];
      return $this->getByMail($email);
    }
    return FALSE;
  }

  /**
   * Log sync Error.
   *
   * @param array $log_error
   *   The detailed log error array.
   * @param string $key
   *   The state key name.
   */
  protected function logSyncError(array $log_error = [], $key = '') {
    if (empty($log_error) || empty($key)) {
      return;
    }
    // Save the log in custom state variable.
    $state = \Drupal::state();
    $values = $state->get($key, []);
    $values[] = $log_error;
    $state->set($key, $values);
  }

  /**
   * Delete Users on Gluu Server.
   *
   * @param string $id
   *   The Gluu user id.
   *
   * @throws \Drupal\vscpa_sso\Exception\SSOException
   *   Thrown if SSO server error while fetching an user's information.
   *
   * @return \Drupal\vscpa_sso\Entity\GluuUser|bool
   *   The updated gluu user, otherwise FALSE.
   */
  public function deleteUser($id = '') {
    if (empty($id)) {
      return FALSE;
    }
    $accessToken = $this->requestTokens();
    $endpoint = $this->getProviderConfigValue("user_endpoint") . '/' . $id;
    $options = [
      "headers" => [
        "Authorization" => sprintf('Bearer %s', $accessToken->getToken()),
      ],
    ];
    try {
      $this->getOptions($options);
      $response = $this->delete($endpoint, $options);
      if ($response->getStatusCode() == 204) {
        return TRUE;
      }
      $log_error = "Getting code {$response->getStatusCode()} from SSO server while updating user's information.";
    }
    catch (ClientException $ex) {
      $log_error = "Getting code {$ex->getCode()} from SSO server in deleting user.";
    }
    catch (ServerException $e) {
      $response = $e->getResponse();
      $body = (string) $response->getBody();
      $log_error = "Getting code {$e->getCode()} from SSO server in deleting user." . $body;
    }
    catch (\Exception $e) {
      $response = $e->getMessage();
      $log_error = "Getting code {$e->getCode()} from SSO server in deleting user." . $response;
    }
    if (!empty($log_error)) {
      // Logs an error.
      \Drupal::logger('vscpa_sso')->error($log_error);
    }
    return FALSE;
  }

  /**
   * Returns the default http client.
   *
   * @return \GuzzleHttp\Client
   *   A guzzle http client instance.
   */
  public function getHttpClient() {
    if (is_null($this->httpClient)) {
      $this->httpClient = \Drupal::httpClient();
    }
    return $this->httpClient;
  }

  /**
   * Execute a GET request against the API.
   *
   * @param string $requestUri
   *   The request uri.
   * @param array $options
   *   The request options.
   *
   * @return \Psr\Http\Message\ResponseInterface|string
   *   A guzzle response.
   */
  public function get($requestUri, array $options = []) {
    $endpoint = $this->getProviderUrl() . '/' . $requestUri;
    $res = '';
    try {
      $res = $this->getHttpClient()->request('GET', $endpoint, $options);
    }
    catch (\Exception $ex) {
      $res = $ex->getMessage();
    }
    return $res;
  }

  /**
   * Execute a PUT request against the API.
   *
   * @param string $requestUri
   *   The request uri.
   * @param array $options
   *   The options.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   A guzzle response.
   */
  public function put($requestUri, array $options = []) {
    $endpoint = $this->getProviderUrl() . '/' . $requestUri;
    return $res = $this->getHttpClient()->request('PUT', $endpoint, $options);
  }

  /**
   * Execute a POST request against the API.
   *
   * @param string $requestUri
   *   The request uri.
   * @param array $options
   *   The options.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   A guzzle response.
   */
  public function post($requestUri, array $options = []) {
    $endpoint = $this->getProviderUrl() . '/' . $requestUri;
    return $res = $this->getHttpClient()->request('POST', $endpoint, $options);
  }

  /**
   * Execute a DELETE request against the API.
   *
   * @param string $requestUri
   *   The request uri.
   * @param array $options
   *   The options.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   A guzzle response.
   */
  public function delete($requestUri, array $options = []) {
    $endpoint = $this->getProviderUrl() . '/' . $requestUri;
    return $res = $this->getHttpClient()->request('DELETE', $endpoint, $options);
  }

  /**
   * Change Gluu Account Login Info.
   *
   * @param string $mail
   *   The account email.
   * @param string $pass
   *   The account pass.
   * @param \Drupal\vscpa_sso\Entity\GluuUser $user
   *   The Gluu Account.
   *
   * @return bool
   *   TRUE if the gluu user was updated successfully, Otherwise FALSE.
   */
  public function changeLoginInfo($mail = NULL, $pass = NULL, GluuUser $user = NULL) {
    if (empty($pass) && empty($mail)) {
      return FALSE;
    }
    if (!$user) {
      return FALSE;
    }
    // Get the current Gluu ID.
    $gluu_uid = $user->id ?? NULL;
    if (empty($gluu_uid)) {
      return FALSE;
    }
    // Define Schemas.
    $schemas[] = GluuUser::USER_SCHEMA;
    $user->schemas = $schemas;
    if (!empty($mail)) {
      $mail = strtolower($mail);
      // Update the Email.
      $email = new Email();
      $email->value = $mail;
      $email->type = 'other';
      $email->primary = TRUE;
      $emails[] = $email;
      $user->emails = $emails;
    }
    // Standardize Username to user the External ID.
    if (isset($user->externalId)) {
      $user->userName = $user->externalId;
    }
    if (!empty($pass)) {
      // Update the password.
      $user->password = $pass;
    }
    // Set Active status:
    $user->active = TRUE;
    // Update the Gluu user.
    $this->setVerifyPeer(FALSE);
    // Return result.
    return $this->updateUser($gluu_uid, $user);
  }

  /**
   * Create User from am.net person data.
   *
   * @param array $data
   *   The submitted data.
   * @param bool $return_object
   *   The flag to return object.
   *
   * @return bool|\Drupal\vscpa_sso\Entity\GluuUser
   *   TRUE if the Gluu user was created successfully, Otherwise FALSE.
   */
  public function createUserFromPersonData(array $data = [], $return_object = FALSE) {
    if (empty($data)) {
      return FALSE;
    }
    // Create Gluu User.
    $user = new GluuUser();
    // Define Schemas.
    $schemas[] = GluuUser::USER_SCHEMA;
    $user->schemas = $schemas;
    // Set Email.
    $mail_address = $data['mail'] ?? NULL;
    $mail_address = strtolower($mail_address);
    $email = new Email();
    $email->value = $mail_address;
    $email->type = 'other';
    $email->primary = TRUE;
    $emails[] = $email;
    $user->emails = $emails;
    // Set the password.
    $pass = $data['pass'] ?? NULL;
    $user->password = $pass;
    // Set Active status:
    $user->active = TRUE;
    // Set user name - Standardize Username to be the AMNet ID or the email.
    $user_name = $data['username'] ?? $mail_address;
    $user->userName = $user_name;
    // Set nick name.
    $nickname = $data['nickname'] ?? NULL;
    $user->nickName = $nickname;
    // Set Locale.
    $user->preferredLanguage = 'en-us';
    $user->locale = 'en_US';
    // Set the Names.
    $display_name = [];
    $name = new Name();
    $family_name = $data['familyname'] ?? NULL;
    if (!empty($family_name)) {
      $name->familyName = $family_name;
      $display_name[] = $family_name;
    }
    $given_name = $data['givenname'] ?? NULL;
    if (!empty($given_name)) {
      $name->givenName = $given_name;
      $display_name[] = $given_name;
    }
    $user->name = $name;
    // Display name.
    if (!empty($display_name)) {
      $user->displayName = implode(' ', $display_name);
    }
    // External ID.
    $external_id = $data['external_id'] ?? NULL;
    $user->externalId = $external_id;
    $user->profileUrl = $external_id;
    // Save the Gluu user.
    $this->setVerifyPeer(FALSE);
    $gluu_user = $this->createUser($user);
    if ($return_object) {
      return $gluu_user;
    }
    // Return result.
    return ($gluu_user != FALSE);
  }

  /**
   * Create User from submitted data.
   *
   * @param array $data
   *   The submitted data.
   *
   * @return bool
   *   TRUE if the gluu user was created successfully, Otherwise FALSE.
   */
  public function createUserFromSubmittedData(array $data = []) {
    if (empty($data)) {
      return FALSE;
    }
    $mail_address = $data['mail'] ?? NULL;
    if (empty($mail_address)) {
      return FALSE;
    }
    $user_name = $data['name'] ?? NULL;
    if (empty($user_name)) {
      return FALSE;
    }
    $pass = $data['pass'] ?? NULL;
    if (empty($pass)) {
      return FALSE;
    }
    // Create Gluu User.
    $user = new GluuUser();
    // Define Schemas.
    $schemas[] = GluuUser::USER_SCHEMA;
    $user->schemas = $schemas;
    // Set Email.
    $email = new Email();
    $mail_address = strtolower($mail_address);
    $email->value = $mail_address;
    $email->type = 'other';
    $email->primary = TRUE;
    $emails[] = $email;
    $user->emails = $emails;
    // Set the password.
    $user->password = $pass;
    // Set Active status:
    $user->active = TRUE;
    // Set user name.
    $user->userName = $user_name;
    $user->nickName = $user_name;
    // Set Locale.
    $user->preferredLanguage = 'en-us';
    $user->locale = 'en_US';
    // Set the Names.
    $display_name = [];
    $name = new Name();
    $family_name = $data['field_familyname'][0]['value'] ?? NULL;
    if (!empty($family_name)) {
      $name->familyName = $family_name;
      $display_name[] = $family_name;
    }
    $given_name = $data['field_givenname'][0]['value'] ?? NULL;
    if (!empty($given_name)) {
      $name->givenName = $given_name;
      $display_name[] = $given_name;
    }
    $user->name = $name;
    // Display name.
    if (!empty($display_name)) {
      $user->displayName = implode(' ', $display_name);
    }
    // External ID.
    $user->externalId = $mail_address;
    $user->profileUrl = $mail_address;
    // Save the Gluu user.
    $this->setVerifyPeer(FALSE);
    $gluu_user = $this->createUser($user);
    // Return result.
    return ($gluu_user != FALSE);
  }

}
