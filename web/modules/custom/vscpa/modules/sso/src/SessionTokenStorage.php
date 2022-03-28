<?php

namespace Drupal\vscpa_sso;

/**
 * Session Token Storage.
 */
class SessionTokenStorage {

  /**
   * The user private temp store.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $userPrivateTempStore;

  /**
   * Constructs a new SessionTokenStorage object.
   */
  public function __construct() {
    if (PHP_SAPI === 'cli') {
      // Store the token in the state in a drush context.
      $this->userPrivateTempStore = \Drupal::state();
    }
    else {
      $this->userPrivateTempStore = \Drupal::service('user.private_tempstore')->get('vscpa_sso');
    }
  }

  /**
   * Get access token list.
   *
   * @param string $userId
   *   The user id.
   *
   * @return array
   *   The access token list.
   */
  public function getAccessTokenList($userId) {
    $key = sprintf('_gluu_token_%s', $userId);
    $data = $this->userPrivateTempStore->get($key);
    if (empty($data)) {
      return [];
    }
    return $data;
  }

  /**
   * Store access token list.
   *
   * @param string $userId
   *   The user id.
   * @param AccessToken $accessToken
   *   The access token user for comparison.
   */
  public function storeAccessToken($userId, AccessToken $accessToken) {
    $key = sprintf('_gluu_token_%s', $userId);
    $this->userPrivateTempStore->set($key, $accessToken);
  }

  /**
   * Delete access token.
   *
   * @param string $userId
   *   The user id.
   * @param AccessToken $accessToken
   *   The access token user for comparison.
   */
  public function deleteAccessToken($userId, AccessToken $accessToken) {
    $key = sprintf('_gluu_token_%s', $userId);
    foreach ($this->getAccessTokenList($userId) as $k => $v) {
      if ($accessToken->getToken() === $v->getToken()) {
        $this->userPrivateTempStore->delete($key);
      }
    }
  }

}
