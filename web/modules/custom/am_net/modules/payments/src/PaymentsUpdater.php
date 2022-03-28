<?php

namespace Drupal\am_net_payments;

use Drupal\am_net\AssociationManagementClient;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * The AM.net payments updater service.
 *
 * @package Drupal\am_net_payments
 */
class PaymentsUpdater implements PaymentsUpdaterInterface {

  /**
   * The AM.net API HTTP client.
   *
   * @var null|\UnleashedTech\AMNet\Api\Client
   */
  protected $httpClient;

  /**
   * The payment gateway storage.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayStorageInterface
   */
  protected $paymentGatewayStorage;

  /**
   * The payment method storage.
   *
   * @var \Drupal\commerce_payment\PaymentMethodStorageInterface
   */
  protected $paymentMethodStorage;

  /**
   * The AM.net payments helper.
   *
   * @var \Drupal\am_net_payments\PaymentsHelperInterface
   */
  protected $paymentsHelper;

  /**
   * The payment storage.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * The profile storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * Constructs a new PaymentsUpdater.
   *
   * @param \Drupal\am_net\AssociationManagementClient $client
   *   The AM.net API HTTP client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\am_net_payments\PaymentsHelperInterface $payments_helper
   *   The payments helper.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(AssociationManagementClient $client, EntityTypeManagerInterface $entity_type_manager, PaymentsHelperInterface $payments_helper) {
    $this->httpClient = $client->getClient();
    $this->paymentGatewayStorage = $entity_type_manager->getStorage('commerce_payment_gateway');
    $this->paymentMethodStorage = $entity_type_manager->getStorage('commerce_payment_method');
    $this->paymentStorage = $entity_type_manager->getStorage('commerce_payment');
    $this->paymentsHelper = $payments_helper;
    $this->profileStorage = $entity_type_manager->getStorage('profile');
  }

  /**
   * {@inheritdoc}
   */
  public function update(UserInterface $user, $payment_gateway_id) {
    $payment_gateway = $this->paymentGatewayStorage->load($payment_gateway_id);
    if (!$payment_gateway || !$user->field_amnet_id->value) {
      return;
    }
    $endpoint = "Person/{$user->field_amnet_id->value}/recurring";
    if ($result = $this->httpClient->get($endpoint)->getResult()) {
      foreach ($result as $profile) {
        $this->updatePaymentMethodFromProfile($user, $payment_gateway, $profile);
      }
    }
  }

  /**
   * Creates or updates a local payment method for the given user and profile.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway
   *   The payment gateway.
   * @param array $profile
   *   The AM.net recurring payment profile.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updatePaymentMethodFromProfile(UserInterface $user, PaymentGatewayInterface $gateway, array $profile) {
    if (!isset($profile['CardNumber']) || empty($profile['CardNumber'])) {
      return;
    }
    if (!isset($profile['CardExpires']) || empty($profile['CardExpires'])) {
      return;
    }
    if (!isset($profile['ReferenceTransationNumber']) || empty($profile['ReferenceTransationNumber'])) {
      return;
    }
    // Format fields.
    $card_type = $this->paymentsHelper->getCommerceCardType($profile['CardNumber']);
    $card_number = substr($profile['CardNumber'], -4);
    $expires = (new \DateTime($profile['CardExpires']))->getTimestamp();
    $uid = $user->id();
    // Check if the Payment Method already exist localy.
    $result = $this->paymentMethodStorage->getQuery()
      ->condition('card_type', $card_type)
      ->condition('card_number', $card_number)
      ->condition('expires', $expires)
      ->condition('uid', $uid)
      ->execute();
    $isPaymentMethodSynced = !empty($result);
    if ($isPaymentMethodSynced) {
      return;
    }
    // (Integration: AMS - Sync user profiles)
    // @todo: https://unleashed.teamwork.com/#tasks/6514642
    // Make sure customer profile exists first.
    // We should seek to keep minimal billing profiles in Drupal.
    if (!$billing_profile = $this->profileStorage->loadDefaultByUser($user, 'customer')) {
      return;
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->paymentMethodStorage->create([
      'payment_gateway' => $gateway->id(),
      'payment_gateway_mode' => $gateway->getPlugin()->getMode(),
      'card_number' => substr($profile['CardNumber'], -4),
      'card_type' => $this->paymentsHelper->getCommerceCardType($profile['CardNumber']),
      'expires' => (new \DateTime($profile['CardExpires']))->getTimestamp(),
      'remote_id' => $profile['ReferenceTransationNumber'],
      'reusable' => TRUE,
      'type' => 'credit_card',
      'uid' => $user->id(),
    ]);
    $payment_method->setBillingProfile($billing_profile);
    $payment_method->save();
  }

}
