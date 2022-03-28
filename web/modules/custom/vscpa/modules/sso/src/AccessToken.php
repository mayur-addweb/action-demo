<?php

namespace Drupal\vscpa_sso;

use Drupal\vscpa_sso\Exception\AccessTokenException;

/**
 * Define Gluu Access Token object.
 */
class AccessToken {

  /**
   * The accessToken.
   *
   * @var string
   */
  private $accessToken;

  /**
   * The token type.
   *
   * @var string
   */
  private $tokenType;

  /**
   * The expires in date.
   *
   * @var int|null
   */
  private $expiresIn = NULL;

  /**
   * The buffer.
   *
   * @var string|null
   */
  private $buffer = 10;

  /**
   * Constructs a new AccessToken object.
   *
   * @param array $tokenData
   *   The token data.
   */
  public function __construct(array $tokenData = []) {
    $requiredKeys = ['access_token', 'token_type', 'expires_in'];
    foreach ($requiredKeys as $requiredKey) {
      if (!array_key_exists($requiredKey, $tokenData)) {
        throw new AccessTokenException(sprintf('missing key "%s"', $requiredKey));
      }
    }
    $this->setAccessToken($tokenData['access_token']);
    $this->setTokenType($tokenData['token_type']);

    // Set optional keys.
    if (array_key_exists('expires_in', $tokenData)) {
      $this->setExpiresIn($tokenData['expires_in']);
    }
  }

  /**
   * Get the access token.
   *
   * @return string
   *   The access token.
   *
   * @see https://tools.ietf.org/html/rfc6749#section-5.1
   */
  public function getToken() {
    return $this->accessToken;
  }

  /**
   * Get the token type.
   *
   * @return string
   *   The token type
   *
   * @see https://tools.ietf.org/html/rfc6749#section-7.1
   */
  public function getTokenType() {
    return $this->tokenType;
  }

  /**
   * Get the expires in.
   *
   * @return int|null
   *   The expires in.
   *
   * @see https://tools.ietf.org/html/rfc6749#section-5.1
   */
  public function getExpiresIn() {
    return $this->expiresIn;
  }

  /**
   * Check if token is expired.
   *
   * @return bool
   *   TRUE if the token is expired otherwise FALSE.
   */
  public function isExpired() {
    if (NULL === $this->getExpiresIn()) {
      // If no expiry was indicated, assume it is valid.
      return FALSE;
    }
    return time() + $this->buffer >= $this->getExpiresIn();
  }

  /**
   * Get new AccessToken from Json string.
   *
   * @param string $jsonString
   *   The json string representation of the object.
   *
   * @return AccessToken
   *   The Access token object.
   */
  public static function fromJson($jsonString) {
    $tokenData = json_decode($jsonString, TRUE);
    if (NULL === $tokenData && JSON_ERROR_NONE !== json_last_error()) {
      $errorMsg = function_exists('json_last_error_msg') ? json_last_error_msg() : json_last_error();
      throw new AccessTokenException(sprintf('unable to decode JSON from storage: %s', $errorMsg));
    }

    return new self($tokenData);
  }

  /**
   * Parse object to Json.
   *
   * @throws \RuntimeException
   *   Thrown error is not was encoded.
   *
   * @return string
   *   The json string representation of the object.
   */
  public function toJson() {
    $jsonData = [
      'access_token' => $this->getToken(),
      'token_type' => $this->getTokenType(),
      'expires_in' => $this->getExpiresIn(),
    ];

    if (FALSE === $jsonString = json_encode($jsonData)) {
      throw new \RuntimeException('unable to encode JSON');
    }

    return $jsonString;
  }

  /**
   * Set Access Token.
   *
   * @param string $accessToken
   *   The access token.
   *
   * @throws \Drupal\vscpa_sso\Exception\AccessTokenException
   *   Thrown when invalid token is provided.
   */
  private function setAccessToken($accessToken) {
    self::requireString('access_token', $accessToken);
    if (1 !== preg_match('/^[\x20-\x7E]+$/', $accessToken)) {
      throw new AccessTokenException('invalid "access_token"');
    }
    $this->accessToken = $accessToken;
  }

  /**
   * Set token type.
   *
   * @param string $tokenType
   *   The token type.
   *
   * @throws \Drupal\vscpa_sso\Exception\AccessTokenException
   *   Thrown when unsupported token_type is provided.
   */
  private function setTokenType($tokenType) {
    self::requireString('token_type', $tokenType);
    if ('bearer' !== $tokenType) {
      throw new AccessTokenException('unsupported "token_type"');
    }
    $this->tokenType = $tokenType;
  }

  /**
   * Set expires in date.
   *
   * @param int|null $expiresIn
   *   The expires in date.
   *
   * @throws \Drupal\vscpa_sso\Exception\AccessTokenException
   *   Thrown when invalid expires_in is provided.
   */
  private function setExpiresIn($expiresIn) {
    if (NULL !== $expiresIn) {
      self::requireInt('expires_in', $expiresIn);
      if (0 >= $expiresIn) {
        throw new AccessTokenException('invalid "expires_in"');
      }
      $expiresIn = time() + $expiresIn;
    }
    $this->expiresIn = $expiresIn;
  }

  /**
   * Require string validation.
   *
   * @param string $k
   *   The key.
   * @param string $v
   *   The value.
   *
   * @throws \Drupal\vscpa_sso\Exception\AccessTokenException
   *   Thrown when the value if the given key is not string.
   */
  private static function requireString($k, $v) {
    if (!is_string($v)) {
      throw new AccessTokenException(sprintf('"%s" must be string', $k));
    }
  }

  /**
   * Require string validation.
   *
   * @param string $k
   *   The key.
   * @param string $v
   *   The value.
   *
   * @throws \Drupal\vscpa_sso\Exception\AccessTokenException
   *   Thrown when the value if the given key is not int.
   */
  private static function requireInt($k, $v) {
    if (!is_int($v)) {
      throw new AccessTokenException(sprintf('"%s" must be int', $k));
    }
  }

}
