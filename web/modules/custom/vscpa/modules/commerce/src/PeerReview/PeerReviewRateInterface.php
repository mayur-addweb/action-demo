<?php

namespace Drupal\vscpa_commerce\PeerReview;

/**
 * Defines a common interface for handle individual peer review rate.
 */
interface PeerReviewRateInterface {

  /**
   * Gets the billing class code.
   *
   * @return int
   *   The billing class code.
   */
  public function getBillingClassCode();

  /**
   * Gets the Annual Administrative Fee.
   *
   * @return string
   *   The Annual Administrative Fee.
   */
  public function getFee();

  /**
   * Gets the Label.
   *
   * @return string
   *   The Label.
   */
  public function getLabel();

  /**
   * Gets the Formatted Label.
   *
   * @return string
   *   The formatted label.
   */
  public function getFormattedLabel();

  /**
   * Sets the billing class code.
   *
   * @param string $billing_class_code
   *   The billing class code.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface
   *   The current instance.
   */
  public function setBillingClassCode($billing_class_code = NULL);

  /**
   * Sets the Annual Administrative Fee.
   *
   * @param string $fee
   *   The Annual Administrative Fee.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface
   *   The current instance.
   */
  public function setFee($fee = NULL);

  /**
   * Sets the Label.
   *
   * @param string $label
   *   The Annual Administrative Label.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface
   *   The current instance.
   */
  public function setLabel($label = NULL);

  /**
   * Constructs a new entity object, without permanently saving it.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewRateInterface
   *   The entity object.
   *
   * @throws \InvalidArgumentException
   *   If missing required properties.
   */
  public static function create(array $values = []);

  /**
   * Gets an array of all property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public function toArray();

}
