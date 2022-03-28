<?php

namespace Drupal\vscpa_commerce\PeerReview;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wrapper init methods for Peer Review Rates Service.
 */
trait PeerReviewRatesTrait {

  /**
   * The Peer Review Rates service.
   *
   * @var \Drupal\vscpa_commerce\PeerReview\PeerReviewRatesInterface
   */
  protected $rates = NULL;

  /**
   * Class constructor for Peer Review Config form.
   *
   * @param \Drupal\vscpa_commerce\PeerReview\PeerReviewRatesInterface $rates_service
   *   The Peer Review Rates service.
   */
  public function __construct(PeerReviewRatesInterface $rates_service) {
    $this->rates = $rates_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vscpa_commerce.peer_review_rates')
    );
  }

}
