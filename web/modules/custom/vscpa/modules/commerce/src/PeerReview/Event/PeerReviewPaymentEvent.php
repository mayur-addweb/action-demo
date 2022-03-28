<?php

namespace Drupal\vscpa_commerce\PeerReview\Event;

use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;

/**
 * Defines the Peer Review Payment event.
 *
 * @see \Drupal\vscpa_commerce\PeerReview\Event\PeerReviewPaymentEvents
 */
class PeerReviewPaymentEvent extends Event {

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * A url to redirect to after this event.
   *
   * @var \Drupal\Core\Url|null
   */
  protected $redirectUrl;

  /**
   * The Peer Review Info Service.
   *
   * @var \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   */
  protected $info = NULL;

  /**
   * Constructs a new DonationEvent.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface $info
   *   The Peer Review Info Service.
   */
  public function __construct(AccountInterface $account, PeerReviewInfoInterface $info) {
    $this->account = $account;
    $this->info = $info;
  }

  /**
   * Gets the user account.
   *
   * @return \Drupal\user\UserInterface
   *   The user account.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Gets the amount of the donation.
   *
   * @return \Drupal\commerce_price\Price
   *   The amount of the donation.
   */
  public function getAmount() {
    return $this->info->getAmount();
  }

  /**
   * Gets the Peer Review Payment Info.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   *   The peer review payment info.
   */
  public function getPeerReviewPaymentInfo() {
    return $this->info;
  }

  /**
   * Gets a redirect url, if one is set.
   *
   * @return \Drupal\Core\Url|null
   *   The URL to which the user should be redirected.
   */
  public function getRedirectUrl() {
    return $this->redirectUrl;
  }

  /**
   * Gets the default Peer Review Payment product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The Peer Review Payment product
   */
  public function getDefaultPeerReviewPaymentProduct() {
    try {
      $storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
    }
    catch (InvalidPluginDefinitionException $e) {
      return NULL;
    }
    catch (PluginNotFoundException $e) {
      return NULL;
    }
    $properties = [
      'type' => 'peer_review_administrative_fee',
      'status' => NodeInterface::PUBLISHED,
    ];
    $products = $storage->loadByProperties($properties);
    if (empty($products)) {
      return NULL;
    }
    return current($products);
  }

  /**
   * Sets the redirect url.
   *
   * @param string $url
   *   The URL to which the user should be redirected.
   */
  public function setRedirectUrl($url) {
    $this->redirectUrl = $url;
  }

}
