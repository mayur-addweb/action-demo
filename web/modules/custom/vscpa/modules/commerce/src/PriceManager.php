<?php

namespace Drupal\vscpa_commerce;

use Drupal\commerce\Context;
use Drupal\commerce_price\Price;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * The price manager.
 *
 * @package Drupal\vscpa_commerce
 */
class PriceManager implements PriceManagerInterface {

  use EventRegistrationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEventPricingOptions(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Only provide pricing for known entities.
    if (!$entity instanceof ProductVariationInterface || $entity->bundle() !== 'event_registration') {
      return NULL;
    }
    // Assume always EST.
    $timezone = new \DateTimeZone('America/New_York');
    $datetime = DrupalDateTime::createFromTimestamp($context->getTime(), $timezone);

    // Set registration cutoff to day of event, if not specified otherwise.
    $registration_end_date = NULL;
    if ($entity->getProduct()->hasField('field_event') && $event = $entity->getProduct()->get('field_event')->entity) {
      if ($event->hasField('field_event_expiry') && $registration_cutoff_date = $event->field_event_expiry->date) {
        $registration_end_date = $registration_cutoff_date;
      }
      elseif ($event->hasField('field_dates_times') && $first_date = $event->field_dates_times->first()) {
        $registration_end_date = $first_date;
      }
    }
    // Date is stored as UTC. We need to make sure it displays as EST.
    if ($registration_end_date instanceof DrupalDateTime) {
      $registration_end_date->setTimezone($timezone);
    }

    // Get member price, or use default price.
    if ($entity->hasField('field_price_member') && !$entity->get('field_price_member')->isEmpty()) {
      $member_price = $entity->get('field_price_member')->first()->toPrice();
    }
    else {
      $member_price = $entity->getPrice();
    }
    $user_status = $context->getCustomer()->getRoles();
    $is_member = in_array('member', $user_status) || in_array('firm_administrator', $user_status);
    $price = isset($entity->price) ? $entity->price->first() : NULL;
    $price = (!is_null($price) && !$price->isEmpty()) ? $price->toPrice() : new Price('0.0', 'USD');
    $member_price = $member_price ?? new Price('0.0', 'USD');
    $pricing_options = [
      'current_option' => [
        'type' => 'regular',
        'price' => $price,
        'member_price' => $member_price,
        'end_date' => $registration_end_date,
        'is_member' => $is_member,
      ],
      'next_option' => [],
    ];

    if ($entity->hasField('field_early_bird_expiry') && !$entity->get('field_early_bird_expiry')->isEmpty() && ($entity->hasField('field_price_early') || $entity->hasField('field_price_member_early'))) {
      $early_bird_expiry = $entity->field_early_bird_expiry->value;
      if (!$early_bird_expiry instanceof DrupalDateTime) {
        $early_bird_expiry = new DrupalDateTime($early_bird_expiry);
      }
      $early_bird_expiry->setTimezone($timezone);
      $early_bird_expiry_date = $early_bird_expiry->format('Y-m-d') . '23:59:59';
      $early_bird_expiry_date_time = strtotime($early_bird_expiry_date);
      if ($datetime->getTimestamp() < $early_bird_expiry_date_time) {

        $pricing_options['next_option'] = $pricing_options['current_option'];
        $pricing_options['current_option']['type'] = 'earlybird';
        // Get the Current Option - Price.
        $current_option_price = $entity->getPrice();
        if ($entity->hasField('field_price_early')) {
          $field_price_early = $entity->get('field_price_early');
          if (!is_null($field_price_early) && !$field_price_early->isEmpty()) {
            $current_option_price = $field_price_early->first()->toPrice();
          }
        }
        $pricing_options['current_option']['price'] = $current_option_price;
        // Get the Current Option - Member price.
        $current_option_member_price = $current_option_price;
        if ($entity->hasField('field_price_member_early')) {
          $field_price_member_early = $entity->get('field_price_member_early');
          if (!is_null($field_price_member_early) && !$field_price_member_early->isEmpty()) {
            $current_option_member_price = $field_price_member_early->first()->toPrice();
          }
        }
        $pricing_options['current_option']['member_price'] = $current_option_member_price;
        $pricing_options['current_option']['end_date'] = $early_bird_expiry;
      }
    }
    // Check if only a single price should be printed.
    // 1. Early Price.
    $price = $pricing_options['current_option']['price'] ?? NULL;
    $member_price = $pricing_options['current_option']['member_price'] ?? NULL;
    $show_single_price = (!empty($price) && !empty($member_price) && ($price == $member_price));
    $pricing_options['current_option']['show_single_price'] = $show_single_price;
    // 2. Regular Price.
    $price = $pricing_options['next_option']['price'] ?? NULL;
    $member_price = $pricing_options['next_option']['member_price'] ?? NULL;
    $show_single_price = (!empty($price) && !empty($member_price) && ($price == $member_price));
    $pricing_options['next_option']['show_single_price'] = $show_single_price;
    return $pricing_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionPricingOptions(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // TODO: Implement getEventSessionPricingOptions() method.
    // If nonmember fee is not set, use member fee.
  }

  /**
   * {@inheritdoc}
   */
  public function getSelfStudyPricingOptions(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Only provide pricing for known entities.
    if (!$entity instanceof ProductVariationInterface || $entity->bundle() !== 'self_study_registration') {
      return NULL;
    }

    // Get member price, or use default price.
    if ($entity->hasField('field_price_member') && !$entity->get('field_price_member')->isEmpty()) {
      $member_price = $entity->get('field_price_member')->first()->toPrice();
    }
    else {
      $member_price = $entity->getPrice();
    }

    $pricing_options = [
      'price' => $entity->price->first()->toPrice(),
      'member_price' => $member_price,
    ];

    return $pricing_options;
  }

}
