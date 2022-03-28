<?php

namespace Drupal\vscpa_commerce;

use Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfo;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\am_net_membership\MembershipCheckerInterface;
use Drupal\am_net_cpe\CpeRegistrationManagerInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\am_net_cpe\CpeProductManagerInterface;
use Drupal\vscpa_commerce\Sync\DuesPaymentPlan;
use Drupal\vscpa_commerce\Sync\EventRegistration;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\vscpa_commerce\Sync\ProfileRecurring;
use Drupal\am_net\AmNetRecordExcludedException;
use Drupal\am_net\AmNetRecordNotFoundException;
use Drupal\vscpa_commerce\Sync\DuesMaintenance;
use Drupal\am_net\AssociationManagementClient;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\vscpa_commerce\Sync\ReviewPayment;
use Drupal\vscpa_commerce\Sync\Contribution;
use Drupal\vscpa_commerce\Sync\ProductSale;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\rng\EventManagerInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Calculator;
use Drupal\am_net\AmNetCacheHelper;
use Drupal\taxonomy\TermInterface;
use Drupal\commerce_price\Price;
use Drupal\user\UserInterface;
use Drupal\commerce\Context;

/**
 * AM.net Synchronization Manager.
 */
class AmNetSyncManager implements AmNetSyncManagerInterface {

  use StringTranslationTrait;

  /**
   * The AM.net API HTTP client.
   *
   * @var \Drupal\am_net\AssociationManagementClient
   */
  protected $client;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The 'vscpa_commerce' logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The membership checker.
   *
   * @var \Drupal\am_net_membership\MembershipCheckerInterface
   */
  protected $membershipChecker;

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The order item storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderItemStorage;

  /**
   * The payment storage.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * The price manager.
   *
   * @var \Drupal\vscpa_commerce\PriceManagerInterface
   */
  protected $priceManager;

  /**
   * The AM.net CPE product manager.
   *
   * @var \Drupal\am_net_cpe\CpeProductManagerInterface
   */
  protected $productManager;

  /**
   * The profile storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * The AM.net CPE registration manager.
   *
   * @var \Drupal\am_net_cpe\CpeRegistrationManagerInterface
   */
  protected $registrationManager;

  /**
   * The registration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $registrationStorage;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a new AmNetSyncManager.
   *
   * @param \Drupal\am_net\AssociationManagementClient $am_net_client
   *   The AM.net API HTTP client.
   * @param \Drupal\am_net_cpe\CpeProductManagerInterface $product_manager
   *   The AM.net CPE product manager.
   * @param \Drupal\am_net_cpe\CpeRegistrationManagerInterface $registration_manager
   *   The AM.net CPE registration manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The 'vscpa_commerce' logger channel.
   * @param \Drupal\am_net_membership\MembershipCheckerInterface $membership_checker
   *   The membership checker.
   * @param \Drupal\vscpa_commerce\PriceManagerInterface $price_manager
   *   The price manager.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(AssociationManagementClient $am_net_client, CpeProductManagerInterface $product_manager, CpeRegistrationManagerInterface $registration_manager, EntityTypeManagerInterface $entity_type_manager, EventManagerInterface $event_manager, LoggerChannelInterface $logger, MembershipCheckerInterface $membership_checker, PriceManagerInterface $price_manager, CurrentStoreInterface $current_store) {
    $this->client = $am_net_client;
    $this->eventManager = $event_manager;
    $this->logger = $logger;
    $this->membershipChecker = $membership_checker;
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
    $this->paymentStorage = $entity_type_manager->getStorage('commerce_payment');
    $this->priceManager = $price_manager;
    $this->productManager = $product_manager;
    $this->profileStorage = $entity_type_manager->getStorage('profile');
    $this->registrationManager = $registration_manager;
    $this->registrationStorage = $entity_type_manager->getStorage('registration');
    $this->currentStore = $current_store;
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * Pushes a Drupal Commerce order entity to relevant record(s) in AM.net.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   */
  public function pushOrder(OrderInterface $order) {
    foreach ($order->getItems() as $order_item) {
      $order_item_bundle = $order_item->bundle();
      switch ($order_item_bundle) {
        case 'event_registration':
          $this->postEventRegistrationOrderItem($order_item);
          break;

        case 'self_study_registration':
          $this->postSelfStudyRegistrationOrderItem($order_item);
          break;

        case 'membership':
          $this->postMembershipOrderItem($order_item);
          break;

        case 'donation':
          $this->postDonationOrderItem($order_item);
          break;

        case 'peer_review_administrative_fee':
          $this->postPeerReviewPaymentOrderItem($order_item);
          break;

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function pullProductSales($am_net_name_id) {
    try {
      $response = $this->client->get("/Person/{$am_net_name_id}/productsales");
      if ($error_message = $response->getErrorMessage()) {
        $this->logger->error($error_message);
      }

      return $response->getResult();
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncAmNetCpeProductSale(array $am_net_order) {
    $am_net_name_id = trim($am_net_order['ID']);
    $am_net_order_number = trim($am_net_order['OrderNumber']);
    $drupal_order_number = "A-{$am_net_order_number}";
    if (!$user = $this->registrationManager->getDrupalUserByAmNetId($am_net_name_id)) {
      return NULL;
    }
    if ($order = $this->orderStorage->loadByProperties([
      'field_amnet_order_number' => $am_net_order_number,
    ])
    ) {
      // Not handling order updates at this time.
      return current($order);
    }
    else {
      $order_items = [];
      foreach ($am_net_order['Items'] as $item) {
        $product_code = trim($item['ProductCode']);
        try {
          // This call to getDrupal... ensures the product exists (is synced).
          // We sync excluded products to maintain order history.
          $product = $this->productManager->getDrupalCpeSelfStudyProduct($product_code, TRUE, TRUE);
          $variation = $product->getDefaultVariation();
          $order_item = $this->orderItemStorage->create([
            'title' => $variation->label(),
            'type' => 'self_study_registration',
            'quantity' => $item['QuantityShipped'],
            'unit_price' => new Price((string) $item['ItemsPrice'], 'USD'),
            'purchased_entity' => $variation,
            'field_user' => $user,
          ]);
          $order_items[] = $order_item;
        }
        catch (AmNetRecordExcludedException $e) {
          break;
        }
        catch (AmNetRecordNotFoundException $e) {
          break;
        }
      }
      if (!empty($order_items)) {
        // Get or create the customer billing profile.
        if (!$profile = $this->profileStorage->loadDefaultByUser($user, 'customer')) {
          $address = $user->field_home_address;
          $profile = $this->profileStorage->create([
            'type' => 'customer',
            'address' => [
              'country_code' => $address->country_code,
              'postal_code' => $address->postal_code,
              'locality' => $address->locality,
              'address_line1' => $address->address_line1,
              'administrative_area' => $address->administrative_area,
              'given_name' => $user->field_givenname->value,
              'family_name' => $user->field_familyname->value,
            ],
            'uid' => $user,
          ]);
          $profile->save();
        }

        // Create a new draft order.
        /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
        $order = $this->orderStorage->create([
          'type' => 'registration',
          'state' => 'draft',
          'order_number' => $drupal_order_number,
          'field_amnet_order_number' => $am_net_order_number,
          'mail' => $user->getEmail(),
          'order_items' => $order_items,
          'uid' => $user,
          'store_id' => $this->currentStore->getStore(),
          'billing_profile' => $profile,
        ]);
        $order->save();

        // Apply the 'place' transition event so other parts of the system
        // can act on the order, e.g. to create registrations.
        $order->getState()->applyTransition(
          $order->getState()->getTransitions()['place']
        );

        // Override the placed and completed dates with the AM.net date(s).
        $order
          ->set('placed', (new DrupalDateTime($am_net_order['PurchaseOrderDate']))->getTimestamp())
          ->set('completed', (new DrupalDateTime($am_net_order['PurchaseOrderDate']))->getTimestamp())
          ->save();

        return $order;
      }
    }
  }

  /**
   * Extract Order Item.
   *
   * @param array $order_item_values
   *   Required field, The order item values.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface|null
   *   The Order Item entity, otherwise NULL.
   */
  public function extractOrderItem(array $order_item_values = []) {
    // The Order Item ID.
    $order_item_id = $order_item_values['order_item_id'] ?? NULL;
    if (empty($order_item_id)) {
      return NULL;
    }
    /* @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->orderItemStorage->load($order_item_id);
    if (!$order_item) {
      return NULL;
    }
    return $order_item;
  }

  /**
   * Sync Event registration: Submit Event Participant into AM.met system.
   *
   * @param array $order_item_values
   *   Required field, The order item values.
   *
   * @return array
   *   The responses from the requested operation, otherwise FALSE.
   */
  public function syncOrderItemEventRegistrationRecord(array $order_item_values = []) {
    $order_item = $this->extractOrderItem($order_item_values);
    if (!$order_item) {
      return [
        'item_processed' => FALSE,
        'messages' => [],
      ];
    }
    // Posts the event registration order item to AM.net.
    return $this->postEventRegistrationOrderItem($order_item);
  }

  /**
   * Sync Self Study registration into AM.met system.
   *
   * @param array $order_item_values
   *   Required field, The order item values.
   *
   * @return array
   *   The responses from the requested operation, otherwise FALSE.
   */
  public function syncOrderItemSelfStudyRegistrationRecord(array $order_item_values = []) {
    $order_item = $this->extractOrderItem($order_item_values);
    if (!$order_item) {
      return [
        'item_processed' => FALSE,
        'messages' => [],
      ];
    }
    // Posts the Self Study registration order item to AM.net.
    return $this->postSelfStudyRegistrationOrderItem($order_item);
  }

  /**
   * Sync Donation into AM.met system.
   *
   * @param array $order_item_values
   *   Required field, The order item values.
   *
   * @return array
   *   The responses from the requested operation, otherwise FALSE.
   */
  public function syncOrderItemDonationRecord(array $order_item_values = []) {
    $order_item = $this->extractOrderItem($order_item_values);
    if (!$order_item) {
      return [
        'item_processed' => FALSE,
        'messages' => [],
      ];
    }
    // Posts the Donation order item to AM.net.
    return $this->postDonationOrderItem($order_item);
  }

  /**
   * Sync Membership Payment: Submit Membership Payment into AM.met system.
   *
   * @param array $order_item_values
   *   Required field, The order item values.
   *
   * @return array
   *   The responses from the requested operation, otherwise FALSE.
   */
  public function syncOrderItemMembershipPaymentRecord(array $order_item_values = []) {
    // Get the flag: 'disable_dues_account_creation'.
    $disable_dues_account_creation = $order_item_values['submit_changes']['disable_dues_account_creation'] ?? FALSE;
    $disable_dues_account_creation = (bool) $disable_dues_account_creation;
    // Get the Order Item Info.
    $order_item = $this->extractOrderItem($order_item_values);
    if (!$order_item) {
      return [
        'item_processed' => FALSE,
        'messages' => [],
      ];
    }
    // Posts the Membership Payment order item to AM.net.
    return $this->postMembershipOrderItem($order_item, $disable_dues_account_creation);
  }

  /**
   * Sync Peer Review Payment: Submit Peer Review Payment into AM.met system.
   *
   * @param array $order_item_values
   *   Required field, The order item values.
   *
   * @return array
   *   The responses from the requested operation, otherwise FALSE.
   */
  public function syncOrderItemPeerReviewPaymentRecord(array $order_item_values = []) {
    $order_item = $this->extractOrderItem($order_item_values);
    if (!$order_item) {
      return [
        'item_processed' => FALSE,
        'messages' => [],
      ];
    }
    // Posts the Peer Review Payment order item to AM.net.
    return $this->postPeerReviewPaymentOrderItem($order_item);
  }

  /**
   * Posts an event registration order item to AM.net.
   *
   * AM.net handles event registrations differently than products.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   An 'event_registration' order item.
   *
   * @return array
   *   The responses from the requested operation, otherwise FALSE.
   */
  public function postEventRegistrationOrderItem(OrderItemInterface $order_item) {
    // Sync Event Registration - Order Item.
    // Get the Order entity.
    $order = $order_item->getOrder();
    // Get the Cart owner.
    $cart_owner = $order->getCustomer();
    /** @var \Drupal\user\UserInterface $registrant */
    $registrant = $order_item->get('field_user')->entity;
    // Get Event Fields.
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $event_product_variation */
    $event_product_variation = $order_item->getPurchasedEntity();
    if (!$event_product_variation) {
      return NULL;
    }
    $event_product = $event_product_variation->getProduct();
    // Add event registration object instance.
    $eventRegistration = new EventRegistration();

    // Set Event registration - ID.
    $am_net_name_id = $registrant->get('field_amnet_id')->getString();
    $am_net_name_id = trim($am_net_name_id);
    $eventRegistration->setId($am_net_name_id);

    // Set Event registration - Tran Date.
    $order_placed_time = new DrupalDateTime();
    $order_placed_time->setTimestamp($order->getPlacedTime());
    $tran_date = $order_placed_time->format('Y-m-d');
    $tran_date = $tran_date . 'T00:00:00';
    $eventRegistration->setTranDate($tran_date);
    // Set Event registration - No Tran Date Edit.
    // 'NoTranDateEdit' Enables a validation rule that requires transaction
    // date to be within 5 days of current date.
    $pass_days = strtotime('-3 day');
    $no_tran_date_edit = !($order->getPlacedTime() > $pass_days);
    $eventRegistration->setNoTranDateEdit($no_tran_date_edit);
    // Get the firm linked to the user: No all the users are linked
    // to firm, ex students.
    $user_linked_firm = $this->getUserLinkedFirm($registrant);
    // Check if cart owner is firm admin.
    $cart_owner_is_firm_admin = $this->isCartOwnerFirmAdmin($cart_owner, $user_linked_firm);
    // Set Event registration - PayBy.
    $pay_by = ($cart_owner_is_firm_admin) ? 'F' : 'P';
    $eventRegistration->setPayBy($pay_by);

    // Set Event registration - Event code.
    $event_code = $event_product->field_amnet_event_id->code ?? NULL;
    $eventRegistration->setCode1($event_code);

    // Set Event registration - Event year.
    $event_year = $event_product->field_amnet_event_id->year ?? NULL;
    $eventRegistration->setYr($event_year);

    // Payment fields.
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface[] $payments */
    $payments = $this->paymentStorage->loadMultipleByOrder($order);
    // We don't need to have a payment for $0 orders!.
    $payment = !empty($payments) ? current($payments) : NULL;
    if ($payment) {
      // Get Remote ID.
      $remote_id = explode('|', $payment->getRemoteId());
      $payment_reference_number = $remote_id[0] ?? '';
      $payment_authorization_code = $remote_id[1] ?? '';
      // Set Event registration - AuthCode.
      $eventRegistration->setAuthCode($payment_authorization_code);
      // Set Event registration - RefNbr.
      $eventRegistration->setRefNbr($payment_reference_number);
    }

    // Get the Payment Method.
    $payment_method = !empty($payment) ? $payment->getPaymentMethod() : NULL;
    if ($payment_method) {
      // Set Event registration - Card Number.
      $card_number = $this->getCardNumber($payment_method);
      $eventRegistration->setCardno($card_number);
      // Set Event registration - Exp.
      $payment_method_card_exp_year = $payment_method->card_exp_year->value ?? NULL;
      $payment_method_card_exp_month = $payment_method->card_exp_month->value ?? NULL;
      if (!empty($payment_method_card_exp_year) && !empty($payment_method_card_exp_month)) {
        $card_exp = new DrupalDateTime();
        $card_exp->setDate($payment_method_card_exp_year, $payment_method_card_exp_month, 1);
      }
      else {
        $expire_time = $payment_method->getExpiresTime();
        $card_exp = DrupalDateTime::createFromTimestamp($expire_time);
      }
      $card_expiration_date = $card_exp->format('m/y');
      $eventRegistration->setExp($card_expiration_date);
    }

    // Get Billing info.
    $billing_profile = $order->getBillingProfile();
    $billing_first_name = $billing_profile->address->given_name ?? '';
    $billing_last_name = $billing_profile->address->family_name ?? '';
    // Set Event registration - Payor.
    $payor = "{$billing_first_name} {$billing_last_name}";
    $eventRegistration->setPayor($payor);

    // Add session selections.
    $order_item_selected_sessions = $order_item->get('field_sessions_selected')->referencedEntities();
    if (!empty($order_item_selected_sessions)) {
      /* @var \Drupal\vscpa_commerce\Entity\EventSession $session */
      foreach ($order_item_selected_sessions as $session) {
        $session_item = [
          'ses' => $session->get('field_session_code')->getString(),
        ];
        $eventRegistration->addSession($session_item);
      }
    }
    // Get session registration order items attached to this event order item.
    $session_registration_items = $this->orderItemStorage->getQuery()->condition('field_order_item', $order_item->id())->execute();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $paid_session_registrations */
    $paid_session_registrations = $this->orderItemStorage->loadMultiple($session_registration_items);
    // Add fees for paid sessions.
    $session_fees_total = 0;
    if (!empty($paid_session_registrations)) {
      foreach ($paid_session_registrations as $session_order_item) {
        $session_fee = $this->getAmNetSessionFee($session_order_item, $registrant);
        $eventRegistration->addFee($session_fee);
        $session_fees_total += $session_fee['amt'];
      }
    }

    // Set Event registration - Add Event Fee.
    $unit_price_fee = 0;
    $unit_price = $order_item->getUnitPrice();
    if ($unit_price) {
      $unit_price_fee = $unit_price->getNumber();
    }
    $event_fee = $this->getAmNetEventFee($order_item, $registrant);
    $event_price_fee = $event_fee['amt'] ?? 0;
    if (isset($event_fee['amt'])) {
      $event_fee['amt'] = $unit_price_fee;
      $eventRegistration->addFee($event_fee);
      // Handle event registration Fee adjustments.
      $order_item_adjusted_total_amt = $event_price_fee;
      $order_item_adjusted_total_amt = is_numeric($order_item_adjusted_total_amt) ? ((string) $order_item_adjusted_total_amt) : NULL;
      $order_item_adjusted_total_price = new Price($order_item_adjusted_total_amt, 'USD');
      $order_item_total_price = $order_item->getTotalPrice();
      if (!empty($order_item_adjusted_total_amt) && !$order_item_total_price->equals($order_item_adjusted_total_price)) {
        // We need to send adjustment related to discounts.
        $adjustments = $order_item->getAdjustments();
        if (!empty($adjustments)) {
          foreach ($adjustments as $delta => $adjustment) {
            $is_commerce_adjustment = $adjustment && ($adjustment instanceof Adjustment);
            if (!$is_commerce_adjustment) {
              continue;
            }
            if ($adjustment->getType() != 'custom') {
              continue;
            }
            $adjustment_label = $adjustment->getLabel();
            $is_aicpa_discount = (strpos($adjustment_label, 'AICPA Discount') !== FALSE);
            $is_seminar_discount = (strpos($adjustment_label, 'Seminar Discount') !== FALSE);
            $is_good_will_discount = (strpos($adjustment_label, '% Off!') !== FALSE);
            $is_custom_discount = $is_aicpa_discount || $is_seminar_discount || $is_good_will_discount;
            if (!$is_custom_discount) {
              continue;
            }
            $adjustment_amount = $adjustment->getAmount();
            if ($is_aicpa_discount) {
              $fee_ty2 = 'AD';
            }
            elseif ($is_seminar_discount) {
              $fee_ty2 = 'SV';
            }
            if ($is_good_will_discount) {
              $fee_ty2 = 'DP';
            }
            $fee_amt = $adjustment_amount->getNumber();
            $fee_fss = 'A';
            $adjustment_fee = [
              'ty2' => $fee_ty2,
              'amt' => $fee_amt,
              'fss' => $fee_fss,
            ];
            $eventRegistration->addFee($adjustment_fee);
          }
        }
      }
    }
    // Combine session fees in Event total when sending to AM.net.
    $ccAmount = $event_price_fee + $session_fees_total;
    $eventRegistration->setCcAmount($ccAmount);
    // Sync Event registration.
    $result = $eventRegistration->sync($this->client, $this->logger);
    // Update Order Item update sync status.
    $sync_status = [
      'order_id' => $order->id() ?? NULL,
      'order_item_id' => $order_item->id(),
      'purchased_entity_id' => $event_product_variation->id() ?? NULL,
      'order_item_type' => $event_product_variation->bundle() ?? NULL,
      'sync_status' => $result['item_processed'],
      'last_synced' => date('Y-m-d H:i:s'),
      'messages' => [],
    ];
    if ($result['item_processed'] != AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED) {
      $sync_status['messages'] = $result['messages'];
      $sync_status['sync_log'] = $result['messages']['error_message'] ?? '';
    }
    $this->orderItemUpdateSyncStatus($sync_status);
    // Update User's ADA needs if needed.
    $this->updateUserAdaNeeds($registrant, $order_item);
    // Return Result.
    return $result;
  }

  /**
   * Update User ADA Needs.
   *
   * @param \Drupal\user\UserInterface $registrant
   *   The user registrant.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   A event registration order item.
   */
  public function updateUserAdaNeeds(UserInterface $registrant = NULL, OrderItemInterface $order_item = NULL) {
    if (!$registrant || !$order_item) {
      return;
    }
    $field_value = $order_item->getData('selected_special_needs');
    if (empty($field_value)) {
      return;
    }
    // Update the values.
    $tid_values = [];
    foreach ($field_value as $key => $value) {
      $tid_values[]['target_id'] = (string) $value;
    }
    $registrant->set("field_special_needs", $tid_values);
    try {
      $registrant->save();
    }
    catch (EntityStorageException $e) {
      return;
    }
  }

  /**
   * Posts a CPE self-study "registration" order item to AM.net.
   *
   * AM.net does not have 'registrations' for Self-study CPE (products).
   * But Drupal uses registrations to track access to these product types.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   A 'self_study_registration' order item.
   *
   * @return array
   *   The responses from the requested operation, otherwise FALSE.
   */
  protected function postSelfStudyRegistrationOrderItem(OrderItemInterface $order_item) {
    // Get the Order entity.
    $order = $order_item->getOrder();
    // Get the Cart owner.
    $cart_owner = $order->getCustomer();
    /** @var \Drupal\user\UserInterface $registrant */
    $registrant = $order_item->get('field_user')->entity;

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
    $product_variation = $order_item->getPurchasedEntity();
    $product = $product_variation->getProduct();
    $product_code = $product->get('field_course_prodcode')->getString();

    // Add Product Sale object instance.
    $productSale = new ProductSale();
    $productSale->setCode($product_code);

    // Set Product Sale - ID.
    $am_net_name_id = $registrant->get('field_amnet_id')->getString();
    $am_net_name_id = trim($am_net_name_id);
    $productSale->setId($am_net_name_id);

    // Set Product Sale - Tran Date.
    $order_tran_date = $order->getPlacedTime();
    if (empty($order_tran_date)) {
      $order_tran_date = $order->getCreatedTime();
    }
    $order_placed_time = new DrupalDateTime();
    $order_placed_time->setTimestamp($order_tran_date);
    $tran_date = $order_placed_time->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $productSale->setTranDate($tran_date);

    // Get the firm linked to the user: No all the users are linked
    // to firm, ex students.
    $user_linked_firm = $this->getUserLinkedFirm($registrant);
    // Check if cart owner is firm admin.
    $cart_owner_is_firm_admin = $this->isCartOwnerFirmAdmin($cart_owner, $user_linked_firm);
    // Set Product Sale - PayBy.
    $pay_by = ($cart_owner_is_firm_admin) ? 'F' : 'P';
    $productSale->setPayBy($pay_by);

    // Payment fields.
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface[] $payments */
    $payments = $this->paymentStorage->loadMultipleByOrder($order);
    // We don't need to have a payment for $0 orders!.
    $payment = !empty($payments) ? current($payments) : NULL;
    if ($payment) {
      // Get Remote ID.
      $remote_id = explode('|', $payment->getRemoteId());
      $payment_reference_number = $remote_id[0] ?? '';
      $payment_authorization_code = $remote_id[1] ?? '';
      // Set Product Sale - AuthCode.
      $productSale->setValue('AuthCode', $payment_authorization_code);
      // Set Product Sale - RefNbr.
      $productSale->setValue('RefNbr', $payment_reference_number);
    }

    // Get the Payment Method.
    $payment_method = !empty($payment) ? $payment->getPaymentMethod() : NULL;
    if ($payment_method) {
      // Set Product Sale - Card Number.
      $card_number = $this->getCardNumber($payment_method);
      $productSale->setValue('Cardno', $card_number);
      // Set Product Sale - Exp.
      $payment_method_card_exp_year = $payment_method->card_exp_year->value ?? NULL;
      $payment_method_card_exp_month = $payment_method->card_exp_month->value ?? NULL;
      if (!empty($payment_method_card_exp_year) && !empty($payment_method_card_exp_month)) {
        $card_exp = new DrupalDateTime();
        $card_exp->setDate($payment_method_card_exp_year, $payment_method_card_exp_month, 1);
      }
      else {
        $expire_time = $payment_method->getExpiresTime();
        $card_exp = DrupalDateTime::createFromTimestamp($expire_time);
      }
      $card_expiration_date = $card_exp->format('m/y');
      $productSale->setValue('Exp', $card_expiration_date);
    }

    // Get Billing info.
    $billing_profile = $order->getBillingProfile();
    $billing_first_name = $billing_profile->address->given_name ?? '';
    $billing_last_name = $billing_profile->address->family_name ?? '';
    // Set Product Sale - Payor.
    $payor = "{$billing_first_name} {$billing_last_name}";
    $productSale->setPayor($payor);

    // Set Product Sale - Order total.
    $ccAmountNumber = 0;
    $ccAmount = $order_item->getAdjustedUnitPrice();
    if (!empty($ccAmount)) {
      $ccAmountNumber = $ccAmount->getNumber();
      $ccAmountNumber = number_format($ccAmountNumber, 2, '.', '');
    }
    $productSale->setCcAmount($ccAmountNumber);
    // Add Item.
    $item = [
      'ProductCode' => $product_code,
      'Price' => $ccAmountNumber,
      'Cost' => $ccAmountNumber,
      'ShippedQuantity' => (int) $order_item->getQuantity(),
    ];
    $productSale->addItem($item);
    // Sync Event registration.
    $result = $productSale->sync($this->client, $this->logger);
    // Update Order Item update sync status.
    $sync_status = [
      'order_id' => $order->id() ?? NULL,
      'order_item_id' => $order_item->id(),
      'purchased_entity_id' => $product_variation->id() ?? NULL,
      'order_item_type' => $product_variation->bundle() ?? NULL,
      'sync_status' => $result['item_processed'],
      'last_synced' => date('Y-m-d H:i:s'),
      'messages' => [],
    ];
    if ($result['item_processed'] != AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED) {
      $sync_status['messages'] = $result['messages'];
      $sync_status['sync_log'] = $result['messages']['error_message'] ?? '';
    }
    $this->orderItemUpdateSyncStatus($sync_status);
    // Return Result.
    return $result;
  }

  /**
   * Prepares a Peer Review Payment order to push to AM.net.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Peer Review Payment order item.
   */
  public function postPeerReviewPaymentOrderItem(OrderItemInterface $order_item) {
    // Only act on Peer Review Payment.
    if ($order_item->bundle() !== 'peer_review_administrative_fee') {
      return NULL;
    }
    /* @var \Drupal\Core\TypedData\ListInterface $field_values */
    $field_values = $order_item->get('field_peer_review_transaction');
    try {
      /* @var \Drupal\vscpa_commerce\Plugin\Field\FieldType\PeerReviewTransaction $field_item */
      $field_item = $field_values->first();
    }
    catch (MissingDataException $e) {
      return NULL;
    }
    /* @var \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface $peer_review_info */
    $peer_review_info = $field_item->toPeerReviewInfo();
    if (!$peer_review_info) {
      return NULL;
    }
    $billing_items = $peer_review_info->getTransactionsForSync();
    if (empty($billing_items)) {
      return NULL;
    }
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
    $product_variation = $order_item->getPurchasedEntity();
    $purchased_entity_id = ($product_variation) ? $product_variation->id() : NULL;
    $order_item_type = ($product_variation) ? $product_variation->bundle() : NULL;
    // Get the Order entity.
    $order = $order_item->getOrder();
    $email = $order->getEmail();
    $send_payment_receipt = TRUE;
    $card_info = $this->getCardInfo($order);
    $payments = [];
    /* @var \Drupal\vscpa_commerce\PeerReview\PeerReviewTransactionInterface $billing */
    foreach ($billing_items as $delta => $billing) {
      $reviewPayment = new ReviewPayment();
      // Set Peer Review Payment - Firm ID.
      $firm = $peer_review_info->getFirmId();
      $reviewPayment->setFirmId($firm);
      // Set Peer Review Payment - Year.
      $year = $billing->getYear();
      $reviewPayment->setYear($year);
      // Set Peer Review Payment - PayBy.
      $pay_by = 'P';
      $reviewPayment->setPayBy($pay_by);
      // Set Peer Review Payment - Payor.
      $payor = $card_info['payor'];
      $reviewPayment->setPayor($payor);
      // Set Peer Review Payment - TranDate.
      $tran_date = $card_info['tran_date'];
      $reviewPayment->setTranDate($tran_date);
      // Set Peer Review Payment - Note.
      $note = $billing->getNote();
      $reviewPayment->setNote($note);
      // Set Peer Review Payment - AC(Account code).
      $ac = $billing->getAccountCode();
      $reviewPayment->setAc($ac);
      // Set the Peer Review Payment - Billing Class Code.
      $billing_class_code = $peer_review_info->getNewBillingCode();
      $reviewPayment->setBillingClassCode($billing_class_code);
      if ($billing->syncAsAdjustment()) {
        // Set Peer Review Payment - Adjustment.
        $amount = $billing->getAmount();
        $reviewPayment->setAdjustment($amount);
      }
      else {
        // Set Peer Review Payment - Amount.
        $amount = $billing->getAmount();
        $reviewPayment->setCcAmount($amount);
        // Set Peer Review Payment - Card No.
        $card_no = $card_info['card_number'];
        $reviewPayment->setCardno($card_no);
        // Set Peer Review Payment - Exp.
        $card_exp = $card_info['card_expiration_date'];
        $reviewPayment->setExp($card_exp);
        // Set Peer Review Payment - RefNbr.
        $payment_reference_number = $card_info['payment_reference_number'];
        $reviewPayment->setRefNbr($payment_reference_number);
        // Set Peer Review Payment - SendPaymentReceipt.
        $reviewPayment->setSendPaymentReceipt($send_payment_receipt);
        // Set Peer Review Payment - PaymentReceiptDeliveryEmail.
        $reviewPayment->setPaymentReceiptDeliveryEmail($email);
      }
      // Add to the list of payments.
      $payments[] = $reviewPayment;
    }
    // Sync info with AM.net.
    /* @var \Drupal\vscpa_commerce\sync\ReviewPayment $payment */
    foreach ($payments as $payment) {
      // Sync peer review payment.
      $result = $payment->sync($this->client, $this->logger);
    }
    // Update Order Item update sync status.
    $sync_status = [
      'order_id' => $order->id() ?? NULL,
      'order_item_id' => $order_item->id(),
      'purchased_entity_id' => $purchased_entity_id,
      'order_item_type' => $order_item_type,
      'sync_status' => $result['item_processed'],
      'last_synced' => date('Y-m-d H:i:s'),
      'messages' => [],
    ];
    if ($result['item_processed'] != AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED) {
      $sync_status['messages'] = $result['messages'];
      $request = $result['messages']['request'] ?? '';
      $sync_status['sync_log'] = $result['messages']['error_message'] ?? $request;
    }
    $this->orderItemUpdateSyncStatus($sync_status);
    // Return Result.
    return $result;
  }

  /**
   * Get 'Dues Payment Plan' Info.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The membership order item.
   *
   * @return \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfoInterface|false
   *   The Dues Plan info, FALSE otherwise.
   */
  public function getPaymentPlan(OrderItemInterface $order_item) {
    // Only act on membership (ignore membership donations).
    // Donations will be merged in with these membership order items.
    if ($order_item->bundle() !== 'membership') {
      return FALSE;
    }
    $info = $order_item->get('field_payment_plan_info');
    if ($info->isEmpty()) {
      return FALSE;
    }
    /** @var \Drupal\am_net\Plugin\Field\FieldType\AmNetData $field */
    try {
      $field = $info->first();
    }
    catch (MissingDataException $e) {
      return FALSE;
    }
    $amnet_data = $field->toAmNetData();
    $data = $amnet_data->getData();
    if (empty($data)) {
      return FALSE;
    }
    return DuesPaymentPlanInfo::create($data);
  }

  /**
   * Check if the given order item is enroll in the 'Dues Payment Plan'.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The membership order item.
   *
   * @return bool
   *   TRUE if Payment Plan should be applied, FALSE otherwise.
   */
  public function membershipEnrollInToPaymentPlan(OrderItemInterface $order_item) {
    $plan = $this->getPaymentPlan($order_item);
    if (!$plan) {
      return FALSE;
    }
    return $plan->isPlanActive();
  }

  /**
   * Do POST membership order request from Drupal AM.net.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The membership order item.
   *
   * @return array|false
   *   The array result on success, Otherwise FALSE.
   */
  public function doPostMembershipOrderItem(OrderItemInterface $order_item) {
    $order_item_id = $order_item->id();
    // Get the Order entity.
    $order = $order_item->getOrder();
    // Get the Cart owner.
    $cart_owner = $order->getCustomer();
    /** @var \Drupal\user\UserInterface $user */
    $user = $order_item->get('field_user')->entity;
    // Check if user is a terminated member.
    $is_terminated_member = $this->membershipChecker->isTerminatedMember($user);
    $is_terminated_member_on_re_apply = $this->membershipChecker->isTerminatedMemberOnReApply($user);
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
    $product_variation = $order_item->getPurchasedEntity();
    $order_item_total_price = $order_item->getTotalPrice();
    $order_item_adjusted_total_price = $order_item->getAdjustedTotalPrice();
    // Add DuesMaintenance object instance.
    $duesMaintenance = new DuesMaintenance();
    // Get the firm linked to the user: No all the users are linked
    // to firm, ex students.
    $user_linked_firm = $this->getUserLinkedFirm($user);
    $firm_id = $this->getFirmAmNetId($user_linked_firm);
    // Check if cart owner is firm admin.
    $cart_owner_is_firm_admin = $this->isCartOwnerFirmAdmin($cart_owner, $user_linked_firm);
    if ($cart_owner_is_firm_admin && !empty($firm_id)) {
      $duesMaintenance->setFirm($firm_id);
    }
    // Payment fields.
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface[] $payments */
    $payments = $this->paymentStorage->loadMultipleByOrder($order);
    // We don't need to have a payment for $0 orders!.
    $payment = !empty($payments) ? current($payments) : NULL;
    $payment_reference_number = '';
    $ccAmount = NULL;
    if ($payment) {
      // Set Dues Maintenance PayBy.
      $ccAmount = $order_item_adjusted_total_price;
      // Get Remote ID.
      $remote_id = explode('|', $payment->getRemoteId());
      $payment_reference_number = $remote_id[0] ?? '';
      $payment_authorization_code = $remote_id[1] ?? '';
      // Set Dues Maintenance - AuthCode.
      $duesMaintenance->setAuthCode($payment_authorization_code);
      // Set Dues Maintenance - RefNbr.
      $duesMaintenance->setRefNbr($payment_reference_number);
    }
    // Get the Payment Method.
    $payment_method = !empty($payment) ? $payment->getPaymentMethod() : NULL;
    $card_exp = NULL;
    $card_number = '';
    if ($payment_method) {
      // Set Dues Maintenance - DuesPayment.
      $card_number = $this->getCardNumber($payment_method);
      $duesMaintenance->setCardno($card_number);
      // Set Dues Maintenance - Exp.
      $payment_method_card_exp_year = $payment_method->card_exp_year->value ?? NULL;
      $payment_method_card_exp_month = $payment_method->card_exp_month->value ?? NULL;
      if (!empty($payment_method_card_exp_year) && !empty($payment_method_card_exp_month)) {
        $card_exp = new DrupalDateTime();
        $card_exp->setDate($payment_method_card_exp_year, $payment_method_card_exp_month, 1);
      }
      else {
        $expire_time = $payment_method->getExpiresTime();
        $card_exp = DrupalDateTime::createFromTimestamp($expire_time);
      }
      $card_expiration_date = $card_exp->format('m/y');
      $duesMaintenance->setExp($card_expiration_date);
    }
    // Get Billing info.
    $billing_profile = $order->getBillingProfile();
    $billing_first_name = $billing_profile->address->given_name ?? '';
    $billing_last_name = $billing_profile->address->family_name ?? '';
    // Set Dues Maintenance - Payor.
    $payor = "{$billing_first_name} {$billing_last_name}";
    $duesMaintenance->setPayor($payor);

    // 4. Membership details.
    // Membership status info.
    $membership_status_info = $this->membershipChecker->getMembershipStatusInfo($user);
    // Determine if the user is in a Membership Application or
    // in a Membership Renewal.
    $is_membership_application = ($membership_status_info['is_membership_application'] == TRUE);
    // Get the Membership price.
    $dues_amount = $membership_status_info['dues_amount'];

    // Set Dues Maintenance - DuesPayment.
    $duesMaintenance->setDuesPayment($dues_amount);

    // Set Dues Maintenance - Dues Billing.
    $dues_billing = ($is_membership_application || $is_terminated_member_on_re_apply) ? $dues_amount : '';
    $duesMaintenance->setDuesBilling($dues_billing);

    // Check if is necessary include Any Dues Adjustment.
    $is_dues_adjusted = FALSE;
    if (!$order_item_total_price->equals($order_item_adjusted_total_price)) {
      // There are differences in the price, the total price was adjusted.
      $dues_adjustment_price = $order_item_total_price->subtract($order_item_adjusted_total_price);
      $dues_adjustment_price = $dues_adjustment_price->getNumber();
      $dues_adjustment = '-' . $dues_adjustment_price;
      $duesMaintenance->setDuesAdjustment($dues_adjustment);
      $duesMaintenance->setDuesPayment($order_item_adjusted_total_price->getNumber());
      // Check if the rate was reduced until Zero.
      $is_dues_adjusted = TRUE;
    }

    // Set Dues Maintenance - PostZeroDues.
    $post_zero_dues = ($order_item_adjusted_total_price->isZero() && !$is_dues_adjusted) ? TRUE : '';
    $duesMaintenance->setPostZeroDues($post_zero_dues);

    // 5. AM.net record.
    // Set Dues Maintenance - ID.
    $am_net_name_id = $user->get('field_amnet_id')->getString();
    $am_net_name_id = trim($am_net_name_id);
    $duesMaintenance->setId($am_net_name_id);
    // Clear Cache.
    AmNetCacheHelper::clearNameCache($am_net_name_id);

    // Set the adjusted billing class code - AC.
    $billing_class_code = $user->get('field_amnet_billing_class')->getString();
    if (empty($billing_class_code)) {
      $billing_class_code = $this->getBillingClassCode($user);
    }
    $duesMaintenance->setAc($billing_class_code);

    // Set the original Billing Class code - OC.
    $duesMaintenance->setOc($billing_class_code);

    // Set Dues Maintenance - PayBy.
    $pay_by = ($cart_owner_is_firm_admin) ? 'F' : 'P';
    $duesMaintenance->setPayBy($pay_by);

    // Set Dues Maintenance - Tran Date.
    $order_placed_time = new DrupalDateTime();
    $order_placed_time->setTimestamp($order->getPlacedTime());
    $tran_date = $order_placed_time->format('Y-m-d');
    $tran_date = $tran_date . 'T00:00:00';
    $duesMaintenance->setTranDate($tran_date);

    if ($is_membership_application) {
      // Set Dues Maintenance - setSendMembershipApplicationConfirmation.
      $duesMaintenance->setSendMembershipApplicationConfirmation(TRUE);
      // Calcule Dues Maintenance - Paid Through.
      $dues_paid_through = $this->membershipChecker->getMembershipLicenseExpirationDate('Y-m-d\TH:i:s');
    }
    else {
      // Set Dues Maintenance - SendMembershipRenewalEmail.
      $duesMaintenance->setSendMembershipRenewalEmail(TRUE);
      // Calculate Dues Maintenance - Paid Through.
      $dues_paid_through = $this->membershipChecker->getEndDateOfCurrentFiscalYear('Y-m-d\TH:i:s');
    }

    // Set Dues Maintenance - Paid Through.
    $duesMaintenance->setDuesPdThru($dues_paid_through);
    // Set the current Fiscal Year.
    $year = $this->membershipChecker->getCurrentFiscalYear();
    $duesMaintenance->setYear($year);

    // Order Recurring.
    $order_is_recurring = $order->hasField('field_am_net_recurring') ? $order->get('field_am_net_recurring')->value : FALSE;
    // 2. Donations.
    // Dues Check-off.
    $marketing_source_code = 'DC';
    $duesMaintenance->setMarketingSourceCode($marketing_source_code);
    // Get membership donation order items related to this membership.
    $membership_donations = $this->getMembershipDonationItems($order_item_id);
    if (!empty($membership_donations)) {
      // Membership donations.
      foreach ($membership_donations as $donation) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $donation_variation */
        $donation_variation = $donation->getPurchasedEntity();
        $donation_product = $donation_variation->getProduct();
        $donation_source = $donation->get('field_donation_source')->getString();
        $donation_destination = $donation_product->get('field_donation_destination')
          ->getString();
        $donation_fund = NULL;
        if ($donation_destination === 'PAC') {
          $donation_code = $donation_source === 'P' ? 'P1' : 'P2';
        }
        else {
          $donation_code = $donation_source === 'P' ? 'EF' : 'E2';
          $donation_fund = $order_is_recurring ? 'AF' : 'DC';
        }
        $anonymous = $donation->get('field_donation_anonymous')
          ->getString() ? 'Y' : 'N';
        /* @var \Drupal\commerce_price\Price $donation_price */
        $donation_price = $donation->getAdjustedUnitPrice();
        if (empty($ccAmount)) {
          $ccAmount = $donation_price;
        }
        else {
          $ccAmount = $ccAmount->add($donation_price);
        }
        $amt = $donation_price->getNumber();
        // Set Dues Maintenance - addContribution.
        $duesMaintenance->addContribution($donation_code, $anonymous, $amt, $donation_fund);
      }
    }
    // Crete the recurring profile.
    if ($order_is_recurring && $payment_method) {
      $profileRecurring = new ProfileRecurring();
      // Set Profile Recurring - ProfileName.
      $next_fiscal_year = $this->membershipChecker->getMembershipLicenseExpirationDate('Y');
      $profile_name = 'Dues Maintenance Starting in FY ' . $next_fiscal_year;
      $profileRecurring->setProfileName($profile_name);
      // Set Profile Recurring - ProfileStart.
      $profile_start_date = $this->membershipChecker->getMembershipLicenseExpirationDate('Y-05-01\T00:00:00');
      $profileRecurring->setProfileStart($profile_start_date);
      // Set Profile Recurring - ProfileEnd.
      // Set to Jan 1st 1800. This is our "NULL" date.
      $profile_end = '1800-01-01T00:00:00';
      $profileRecurring->setProfileEnd($profile_end);
      // Set Profile Recurring - RecurringPeriodCode.
      $profileRecurring->setRecurringPeriodCode('YEAR');
      // Set Profile Recurring - ReferenceTransationNumber.
      $profileRecurring->setReferenceTransationNumber($payment_reference_number);
      // Set Profile Recurring - ReferenceTransactionAdded.
      $reference_transaction_added = $order_placed_time->format('Y-m-d\TH:i:s');
      $profileRecurring->setReferenceTransactionAdded($reference_transaction_added);
      // Set Profile Recurring - CardExpires.
      $card_expires = $card_exp->format('Y-m-d\TH:i:s');
      $profileRecurring->setCardExpires($card_expires);
      // Set Profile Recurring - CardNumber.
      $profileRecurring->setCardNumber($card_number);
      // Set Profile Recurring - Payor.
      $profileRecurring->setPayor($payor);
      // Add Dues distribution.
      $profileRecurring->addDistribution($dues_amount, TRUE, FALSE);
      // Add Donation Distributions.
      $donation_distributions = $duesMaintenance->distributeContributions();
      if (!empty($donation_distributions)) {
        $profileRecurring->addDistributions($donation_distributions);
      }
      // Sync Profile.
      $recurring_profile_Id = $profileRecurring->sync($am_net_name_id, $this->client, $this->logger);
      if (!empty($recurring_profile_Id)) {
        // Set Dues Maintenance - RecurringProfileId.
        $duesMaintenance->setRecurringProfileId($recurring_profile_Id);
      }
    }
    // Set ccAmount.
    if (!empty($ccAmount)) {
      $ccAmountNumber = $ccAmount->getNumber();
      $duesMaintenance->setCcAmount($ccAmountNumber);
      $dues_payment = $duesMaintenance->getDuesPayment();
      $is_dues_payment_empty = (is_null($dues_payment) || (strlen($dues_payment) == 0));
      if ($is_dues_payment_empty) {
        // Set Dues Maintenance - DuesPayment.
        $duesMaintenance->setDuesPayment($ccAmountNumber);
      }
      else {
        if ($dues_payment > $ccAmountNumber) {
          // Avoid Syncing issue: SyncErrorCode: 85 | The total amount due
          // (dues payment + contribution totals) does not equal payments
          // applied (names MOA + firms MOA + credit card amount).
          $duesMaintenance->setDuesPayment($ccAmountNumber);
        }
      }
    }
    // Check Reinstate.
    if ($is_terminated_member) {
      $duesMaintenance->setReinstate('Y');
    }

    // Sync the Dues Maintenance.
    $result = $duesMaintenance->sync($this->client, $this->logger);
    // Update Order Item update sync status.
    $sync_status = [
      'order_id' => $order->id() ?? NULL,
      'order_item_id' => $order_item_id,
      'purchased_entity_id' => $product_variation->id() ?? NULL,
      'order_item_type' => $product_variation->bundle() ?? NULL,
      'sync_status' => $result['item_processed'],
      'last_synced' => date('Y-m-d H:i:s'),
      'messages' => [],
    ];
    if ($result['item_processed'] != AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED) {
      $sync_status['messages'] = $result['messages'];
      $sync_status['sync_log'] = $result['messages']['error_message'] ?? '';
    }
    $this->orderItemUpdateSyncStatus($sync_status);
    // Return Result.
    return $result;
  }

  /**
   * Do POST membership order request from Drupal AM.net with dues payment plan.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The membership order item.
   * @param bool $disable_dues_account_creation
   *   Disable dues account creation.
   *
   * @return array|false
   *   The array result on success, Otherwise FALSE.
   */
  public function doPostMembershipOrderItemWithPaymentPlan(OrderItemInterface $order_item, $disable_dues_account_creation = FALSE) {
    // Get the plan.
    $plan = $this->getPaymentPlan($order_item);
    // Check if is necessary include Any Dues Adjustment.
    $membership_total_billing = $plan->getDuesPayment();
    $order_item_total_price = new Price($membership_total_billing, 'USD');
    $membership_adjusted_total_billing = $plan->getAdjustedDuesPayment($order_item);
    $order_item_adjusted_total_price = new Price($membership_adjusted_total_billing, 'USD');
    $is_dues_adjusted = FALSE;
    $dues_adjustment = NULL;
    if (!$order_item_total_price->equals($order_item_adjusted_total_price)) {
      // There are differences in the price, the total price was adjusted.
      $dues_adjustment_price = $order_item_total_price->subtract($order_item_adjusted_total_price);
      $dues_adjustment_price = $dues_adjustment_price->getNumber();
      $dues_adjustment = '-' . $dues_adjustment_price;
      $is_dues_adjusted = TRUE;
    }
    // Get the order item ID.
    $order_item_id = $order_item->id();
    // Get the Order entity.
    $order = $order_item->getOrder();
    // Get the Payment Method.
    $payments = $this->paymentStorage->loadMultipleByOrder($order);
    $payment = !empty($payments) ? current($payments) : NULL;
    $payment_authorization_code = NULL;
    $payment_reference_number = NULL;
    $payment_method = NULL;
    if ($payment) {
      $payment_method = $payment->getPaymentMethod();
      // Get Remote ID.
      $remote_id = explode('|', $payment->getRemoteId());
      $payment_reference_number = $remote_id[0] ?? '';
      $payment_authorization_code = $remote_id[1] ?? '';
    }
    $card_expires_month = NULL;
    $card_expires_year = NULL;
    $card_number = NULL;
    if ($payment_method) {
      // Gets card number.
      $card_number = $this->getCardNumber($payment_method);
      // Gets card exp info.
      $payment_method_card_exp_year = $payment_method->card_exp_year->value ?? NULL;
      $payment_method_card_exp_month = $payment_method->card_exp_month->value ?? NULL;
      if (!empty($payment_method_card_exp_year) && !empty($payment_method_card_exp_month)) {
        $card_exp = new DrupalDateTime();
        $card_exp->setDate($payment_method_card_exp_year, $payment_method_card_exp_month, 1);
      }
      else {
        $expire_time = $payment_method->getExpiresTime();
        $card_exp = DrupalDateTime::createFromTimestamp($expire_time);
      }
      $card_expires_month = $card_exp->format('m');
      $card_expires_year = $card_exp->format('y');
    }
    // Get Billing info.
    $billing_profile = $order->getBillingProfile();
    $billing_first_name = $billing_profile->address->given_name ?? '';
    $billing_last_name = $billing_profile->address->family_name ?? '';
    // Check if the order is recurring.
    $order_is_recurring = $order->hasField('field_am_net_recurring') ? $order->get('field_am_net_recurring')->value : FALSE;
    $order_is_recurring = (bool) $order_is_recurring;
    // Add DuesMaintenance object instance.
    $duesPaymentPlan = new DuesPaymentPlan();
    /** @var \Drupal\user\UserInterface $user */
    $user = $order_item->get('field_user')->entity;
    // 1. Set the Dues Payment Plan - ID.
    $am_net_name_id = $user->get('field_amnet_id')->getString();
    $am_net_name_id = trim($am_net_name_id);
    $duesPaymentPlan->setId($am_net_name_id);
    // Clear Cache.
    AmNetCacheHelper::clearNameCache($am_net_name_id);
    // 2. Set the Dues Payment Plan - TranDate.
    $order_placed_time = new DrupalDateTime();
    $order_placed_time->setTimestamp($order->getPlacedTime());
    $tran_date = $order_placed_time->format('Y-m-d');
    $tran_date = $tran_date . 'T00:00:00';
    $duesPaymentPlan->setTranDate($tran_date);
    // 3. Set the Dues Payment Plan - DuesPayment.
    if ($is_dues_adjusted) {
      $duesPaymentPlan->setDuesPayment($membership_adjusted_total_billing);
    }
    else {
      $duesPaymentPlan->setDuesPayment($membership_total_billing);
    }
    // 4. Set the Dues Payment Plan - Cardno.
    $duesPaymentPlan->setCardno($card_number);
    // 5. Set the Dues Payment Plan - CardExpiresMonth.
    $duesPaymentPlan->setCardExpiresMonth($card_expires_month);
    // 6. Set the Dues Payment Plan - CardExpiresYear.
    $duesPaymentPlan->setCardExpiresYear($card_expires_year);
    // 7. Set the Dues Payment Plan - MS Dues Check-off.
    $marketing_source_code = 'DC';
    $duesPaymentPlan->setMarketingSourceCode($marketing_source_code);
    // 8. Set the Dues Payment Plan - Year.
    // @todo include it into the 'Dues Payment Plan' class.
    $year = $this->membershipChecker->getCurrentFiscalYear();
    $duesPaymentPlan->setYear($year);
    // 9. Set the Dues Payment Plan - Payor.
    $payor = "{$billing_first_name} {$billing_last_name}";
    $duesPaymentPlan->setPayor($payor);
    // 10. Set the Dues Payment Plan - PayBy.
    $pay_by = 'P';
    $duesPaymentPlan->setPayBy($pay_by);
    // 11. Set the Dues Payment Plan - CCAmount.
    if ($is_dues_adjusted) {
      $duesPaymentPlan->setCcAmount($plan->getAdjustedCcAmount($order_item));
    }
    else {
      $duesPaymentPlan->setCcAmount($plan->getCcAmount());
    }
    // 12. Set the Dues Payment Plan - AuthCode.
    $duesPaymentPlan->setAuthCode($payment_authorization_code);
    // 13. Set the Dues Payment Plan - RefNbr.
    $duesPaymentPlan->setRefNbr($payment_reference_number);
    // 14. Set the Dues Payment Plan - StoredCardToken.
    $duesPaymentPlan->setStoredCardToken($payment_reference_number);
    // 15. Set the Dues Payment Plan - SetupPaymentPlan.
    $duesPaymentPlan->setUpPaymentPlan(TRUE);
    // 16. Set the Dues Payment Plan - AutoRenew.
    // Note: If the customer wants the payment plan to autorenew/rollover
    // at the end of the year into a new payment plan, set "AutoRenew"
    // to "true".
    $duesPaymentPlan->setAutoRenew($order_is_recurring);
    // 17. Set the Dues Payment Plan - Contributions.
    $membership_donations = $this->getMembershipDonationItems($order_item_id);
    if (!empty($membership_donations)) {
      // Membership donations.
      foreach ($membership_donations as $donation) {
        if ($donation->bundle() != 'membership_donation') {
          continue;
        }
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $donation_variation */
        $donation_variation = $donation->getPurchasedEntity();
        $donation_product = $donation_variation->getProduct();
        $donation_destination_field = $donation_product->get('field_donation_destination');
        $donation_destination = $donation_destination_field->getString();
        $donation_fund = NULL;
        if ($donation_destination === 'PAC') {
          $donation_code = 'PP';
        }
        else {
          $donation_code = 'EP';
          $donation_fund = $order_is_recurring ? 'AF' : 'DC';
        }
        $anonymous = $donation->get('field_donation_anonymous')->getString() ? 'Y' : 'N';
        /* @var \Drupal\commerce_price\Price $donation_price */
        $donation_price = $plan->getContributionAmount(strtolower($donation_destination));
        if (!$donation_price || $donation_price->isZero()) {
          continue;
        }
        $amt = $donation_price->getNumber();
        // Set Dues Payment Plan - Add contribution.
        $duesPaymentPlan->addContribution($donation_code, $anonymous, $amt, $donation_fund);
      }
    }
    // 19. Set send payment plan confirmation email.
    $duesPaymentPlan->setSendPaymentPlanConfirmationEmail(TRUE);
    // Add Dues Maintenance object instance.
    $has_dues_accounts = TRUE;
    $doDuesMaintenanceSync = FALSE;
    $duesMaintenance = new DuesMaintenance();
    $duesMaintenance->setId($am_net_name_id);
    $duesMaintenance->setTranDate($tran_date);
    $duesMaintenance->setYear($year);
    $duesMaintenance->setEmail($order->getEmail());
    // Add Dues Billing account if is a Membership Application.
    if ($plan->isMembershipApplication()) {
      // Create the dues account.
      $duesMaintenance->setDuesBilling($membership_total_billing);
      // Set the adjusted billing class code - AC.
      $billing_class_code = $user->get('field_amnet_billing_class')->getString();
      if (empty($billing_class_code)) {
        $billing_class_code = $this->getBillingClassCode($user);
      }
      // Set the adjusted billing class code - AC.
      $duesMaintenance->setAc($billing_class_code);
      // Set the original Billing Class code - OC.
      $duesMaintenance->setOc($billing_class_code);
      $doDuesMaintenanceSync = TRUE;
    }
    // Send the Dues Adjustment if Applies.
    if ($is_dues_adjusted) {
      $duesMaintenance->setDuesAdjustment($dues_adjustment);
      $doDuesMaintenanceSync = TRUE;
    }
    if (!$disable_dues_account_creation && $doDuesMaintenanceSync) {
      // Sync the Dues Maintenance..
      $result = $duesMaintenance->sync($this->client, $this->logger);
      $has_dues_accounts = $result['item_processed'] ?? FALSE;
    }
    if (!$has_dues_accounts) {
      // Stop here.
      return $result;
    }
    // Sync the Dues Payment Plan.
    $result = $duesPaymentPlan->sync($this->client, $this->logger);
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
    $product_variation = $order_item->getPurchasedEntity();
    $product_variation_id = NULL;
    $product_variation_bundle = NULL;
    if ($product_variation) {
      $product_variation_id = $product_variation->id();
      $product_variation_bundle = $product_variation->bundle();
    }
    // Update Order Item update sync status.
    $sync_status = [
      'order_id' => $order->id() ?? NULL,
      'order_item_id' => $order_item_id,
      'purchased_entity_id' => $product_variation_id,
      'order_item_type' => $product_variation_bundle,
      'sync_status' => $result['item_processed'],
      'last_synced' => date('Y-m-d H:i:s'),
      'messages' => [],
    ];
    $sync_status['messages'] = $result['messages'] ?? [];
    $sync_status['sync_log'] = $result['messages']['error_message'] ?? '';
    $this->orderItemUpdateSyncStatus($sync_status);
    // Return Result.
    return $result;
  }

  /**
   * Prepares a membership order to push to AM.net.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The membership order item.
   * @param bool $disable_dues_account_creation
   *   Disable dues account creation.
   */
  public function postMembershipOrderItem(OrderItemInterface $order_item, $disable_dues_account_creation = FALSE) {
    // Only act on membership (ignore membership donations).
    // Donations will be merged in with these membership order items.
    if ($order_item->bundle() !== 'membership') {
      return NULL;
    }
    // Sync Membership with AM.net.
    if ($this->membershipEnrollInToPaymentPlan($order_item)) {
      $result = $this->doPostMembershipOrderItemWithPaymentPlan($order_item, $disable_dues_account_creation);
    }
    else {
      $result = $this->doPostMembershipOrderItem($order_item);
    }
    // Return Result.
    return $result;
  }

  /**
   * Pushes a donation order to AM.net.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The membership order item.
   */
  protected function postDonationOrder(OrderInterface $order) {
    // Get the Order entity.
    $items = $order->getItems();
    // Get the Cart owner.
    $cart_owner = $order->getCustomer();
    /** @var \Drupal\user\UserInterface $user */
    $user = $cart_owner;
    $am_net_name_id = $user->get('field_amnet_id')->getString();
    $am_net_name_id = trim($am_net_name_id);
    // Get the firm linked to the user: No all the users are linked
    // to firm, ex students.
    $user_linked_firm = $this->getUserLinkedFirm($user);
    $firm_id = $this->getFirmAmNetId($user_linked_firm);
    // Add Contribution object instance.
    $donation = new Contribution();
    // Set Contribution - Tran Date.
    $order_placed_time = new DrupalDateTime();
    $order_placed_time->setTimestamp($order->getPlacedTime());
    $tran_date = $order_placed_time->format('Y-m-d');
    $tran_date = $tran_date . 'T00:00:00';
    $donation->setTranDate($tran_date);
    // Set CCAmount.
    $ccAmount = NULL;
    $ccAmount = $order->getTotalPrice();
    if (!empty($ccAmount)) {
      $ccAmountNumber = $ccAmount->getNumber();
      $ccAmountNumber = number_format($ccAmountNumber, 2, '.', '');
      $donation->setCcAmount($ccAmountNumber);
    }
    // Get the Payment Method.
    $payments = $this->paymentStorage->loadMultipleByOrder($order);
    $payment = !empty($payments) ? current($payments) : NULL;
    $payment_method = !empty($payment) ? $payment->getPaymentMethod() : NULL;
    if ($payment_method) {
      // Set Contribution - DuesPayment.
      $card_number = $this->getCardNumber($payment_method);
      $donation->setCardno($card_number);
      // Set Contribution - Exp.
      $payment_method_card_exp_year = $payment_method->card_exp_year->value ?? NULL;
      $payment_method_card_exp_month = $payment_method->card_exp_month->value ?? NULL;
      if (!empty($payment_method_card_exp_year) && !empty($payment_method_card_exp_month)) {
        $card_exp = new DrupalDateTime();
        $card_exp->setDate($payment_method_card_exp_year, $payment_method_card_exp_month, 1);
      }
      else {
        $expire_time = $payment_method->getExpiresTime();
        $card_exp = DrupalDateTime::createFromTimestamp($expire_time);
      }
      $card_expiration_date = $card_exp->format('m/y');
      $donation->setExp($card_expiration_date);
    }
    // Get Billing info.
    $billing_profile = $order->getBillingProfile();
    $billing_first_name = $billing_profile->address->given_name ?? '';
    $billing_last_name = $billing_profile->address->family_name ?? '';
    // Set Contribution - Payor.
    $payor = "{$billing_first_name} {$billing_last_name}";
    $donation->setPayor($payor);
    if ($payment) {
      // Get Remote ID.
      $remote_id = explode('|', $payment->getRemoteId());
      $payment_reference_number = $remote_id[0] ?? '';
      $payment_authorization_code = $remote_id[1] ?? '';
      // Set Contribution - AuthCode.
      $donation->setAuthCode($payment_authorization_code);
      // Set Contribution - RefNbr.
      $donation->setRefNbr($payment_reference_number);
    }
    $pay_by = '';
    $donation_destination = '';
    foreach ($items as $order_item) {
      $donation_source = $order_item->get('field_donation_source')->getString();
      $donation_destination = $order_item->get('field_donation_destination')->getString();
      $donation_fund = $order_item->get('field_fund')->getString();
      if ($donation_destination === 'PAC') {
        $donation_code = $donation_source === 'P' ? 'P1' : 'P2';
      }
      else {
        $donation_code = $donation_source === 'P' ? 'EF' : 'E2';
      }
      $anonymous = $order_item->get('field_donation_anonymous')->getString() ? 'Y' : 'N';
      /* @var \Drupal\commerce_price\Price $donation_price */
      $donation_price = $order_item->getAdjustedUnitPrice();
      $amt = $donation_price->getNumber();
      $amt = number_format($amt, 2, '.', '');
      // Set Donation Contribution - addContribution.
      $donation->addContribution($donation_code, $anonymous, $amt, $donation_fund);
      // Set Contribution - PayBy.
      $pay_by = $donation_source;
    }
    // Set Contribution - PayBy.
    $donation->setPayBy($pay_by);
    // Set Contribution - Firm.
    $firm = $pay_by === 'F' ? $firm_id : '';
    $donation->setFirm($firm);
    if (empty($firm)) {
      // Set Contribution - ID.
      $donation->setId($am_net_name_id);
    }

    // Set Send Contribution Receipt Email.
    if ($donation_destination === 'EF') {
      // Set Contribution - setSendEfContributionReceipt.
      $donation->setSendEfContributionReceipt(TRUE);
    }
    elseif ($donation_destination === 'PAC') {
      // Set Contribution - setSendEfContributionReceipt.
      $donation->setSendPacContributionReceipt(TRUE);
    }
    // Sync the Contribution.
    $donation->sync($this->client, $this->logger);
  }

  /**
   * Pushes a donation order to AM.net.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The Donation order item.
   */
  protected function postDonationOrderItem(OrderItemInterface $order_item) {
    // Get the Order entity.
    $order = $order_item->getOrder();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
    $product_variation = $order_item->getPurchasedEntity();
    // Get the Cart owner.
    $cart_owner = $order->getCustomer();
    /** @var \Drupal\user\UserInterface $user */
    $user = $cart_owner;
    $am_net_name_id = $user->get('field_amnet_id')->getString();
    $am_net_name_id = trim($am_net_name_id);
    // Get the firm linked to the user: No all the users are linked
    // to firm, ex students.
    $user_linked_firm = $this->getUserLinkedFirm($user);
    $firm_id = $this->getFirmAmNetId($user_linked_firm);
    // Add Contribution object instance.
    $donation = new Contribution();
    // Set Contribution - Tran Date.
    $order_placed_time = new DrupalDateTime();
    $order_placed_time->setTimestamp($order->getPlacedTime());
    $tran_date = $order_placed_time->format('Y-m-d');
    $tran_date = $tran_date . 'T00:00:00';
    $donation->setTranDate($tran_date);
    // Set CCAmount.
    $ccAmount = NULL;
    /* @var \Drupal\commerce_price\Price $ccAmount */
    $ccAmount = $order_item->getTotalPrice();
    $donation_amount = NULL;
    if (!empty($ccAmount)) {
      $ccAmountNumber = $ccAmount->getNumber();
      $donation_amount = number_format($ccAmountNumber, 2, '.', '');
      $donation->setCcAmount($donation_amount);
    }
    // Get the Payment Method.
    $payments = $this->paymentStorage->loadMultipleByOrder($order);
    $payment = !empty($payments) ? current($payments) : NULL;
    $payment_method = !empty($payment) ? $payment->getPaymentMethod() : NULL;
    $card_exp = NULL;
    $card_number = '';
    if ($payment_method) {
      // Set Contribution - DuesPayment.
      $card_number = $this->getCardNumber($payment_method);
      $donation->setCardno($card_number);
      // Set Contribution - Exp.
      $payment_method_card_exp_year = $payment_method->card_exp_year->value ?? NULL;
      $payment_method_card_exp_month = $payment_method->card_exp_month->value ?? NULL;
      if (!empty($payment_method_card_exp_year) && !empty($payment_method_card_exp_month)) {
        $card_exp = new DrupalDateTime();
        $card_exp->setDate($payment_method_card_exp_year, $payment_method_card_exp_month, 1);
      }
      else {
        $expire_time = $payment_method->getExpiresTime();
        $card_exp = DrupalDateTime::createFromTimestamp($expire_time);
      }
      $card_expiration_date = $card_exp->format('m/y');
      $donation->setExp($card_expiration_date);
    }
    // Get Billing info.
    $billing_profile = $order->getBillingProfile();
    $billing_first_name = $billing_profile->address->given_name ?? '';
    $billing_last_name = $billing_profile->address->family_name ?? '';
    // Set Contribution - Payor.
    $payor = "{$billing_first_name} {$billing_last_name}";
    $donation->setPayor($payor);
    $payment_reference_number = '';
    if ($payment) {
      // Get Remote ID.
      $remote_id = explode('|', $payment->getRemoteId());
      $payment_reference_number = $remote_id[0] ?? '';
      $payment_authorization_code = $remote_id[1] ?? '';
      // Set Contribution - AuthCode.
      $donation->setAuthCode($payment_authorization_code);
      // Set Contribution - RefNbr.
      $donation->setRefNbr($payment_reference_number);
    }
    $donation_source = $order_item->get('field_donation_source')->getString();
    $donation_destination = $order_item->get('field_donation_destination')->getString();
    $donation_fund = $order_item->get('field_fund')->getString();
    if ($donation_destination === 'PAC') {
      $donation_code = $donation_source === 'P' ? 'P1' : 'P2';
    }
    else {
      $donation_code = $donation_source === 'P' ? 'EF' : 'E2';
    }
    $anonymous = $order_item->get('field_donation_anonymous')->getString() ? 'Y' : 'N';
    // Set Donation Contribution - addContribution.
    $donation->addContribution($donation_code, $anonymous, $donation_amount, $donation_fund);
    // Set Contribution - PayBy.
    $pay_by = $donation_source;
    // Set Contribution - PayBy.
    $donation->setPayBy($pay_by);
    // Set Contribution - Firm.
    $firm = $pay_by === 'F' ? $firm_id : '';
    $donation->setFirm($firm);
    if (empty($firm)) {
      // Set Contribution - ID.
      $donation->setId($am_net_name_id);
    }
    $label = '';
    // Set Send Contribution Receipt Email.
    if ($donation_destination === 'EF') {
      // Set Contribution - setSendEfContributionReceipt.
      $donation->setSendEfContributionReceipt(TRUE);
      $label = 'EF Contribution';
    }
    elseif ($donation_destination === 'PAC') {
      // Set Contribution - setSendEfContributionReceipt.
      $donation->setSendPacContributionReceipt(TRUE);
      $label = 'PAC Contribution';
    }
    // Check if the Donation is recurring.
    $field_am_net_recurring = $order_item->get('field_am_net_recurring')->getString();
    $is_recurring = empty($field_am_net_recurring) ? FALSE : boolval($field_am_net_recurring);
    if ($is_recurring && $payment_method) {
      $profileRecurring = new ProfileRecurring();
      $field_am_net_recurring = $order_item->get('field_am_net_recurring_interval')->getString();
      // The Default interval is Monthly.
      $interval = empty($field_am_net_recurring) ? 'MNTH' : $field_am_net_recurring;
      // Set Profile Recurring - RecurringPeriodCode.
      $profileRecurring->setRecurringPeriodCode($interval);
      $reference_transaction_added = $order_placed_time->format('Y-m-d\TH:i:s');
      // Set Profile Recurring - ProfileEnd.
      // Set to Jan 1st 1800. This is our "NULL" date.
      $profile_end = '1800-01-01T00:00:00';
      $profileRecurring->setProfileEnd($profile_end);
      // Set Profile Recurring - ProfileStart.
      $profile_start_date = $this->getProfileStartDateBasedOnPeriodCodeAndOrderPlacedDate($interval, $reference_transaction_added, 'Y-m-d\T00:00:00');
      $profileRecurring->setProfileStart($profile_start_date);
      // Set Profile Recurring - ProfileName.
      $profile_name = $label . ' - Recurring ' . $this->getRecurringPeriodLabelByCode($interval);
      $profileRecurring->setProfileName($profile_name);
      // Set Profile Recurring - ReferenceTransationNumber.
      $profileRecurring->setReferenceTransationNumber($payment_reference_number);
      // Set Profile Recurring - ReferenceTransactionAdded.
      $profileRecurring->setReferenceTransactionAdded($reference_transaction_added);
      // Set Profile Recurring - CardExpires.
      $card_expires = $card_exp->format('Y-m-d\TH:i:s');
      $profileRecurring->setCardExpires($card_expires);
      // Set Profile Recurring - CardNumber.
      $profileRecurring->setCardNumber($card_number);
      // Set Profile Recurring - Payor.
      $profileRecurring->setPayor($payor);
      // Add Contribution.
      $contribution = [
        'Amount' => $donation_amount,
        'IsDues' => FALSE,
        'IsContribution' => TRUE,
        'ContributionCode' => $donation_code,
        'DepositTo' => '',
        'AppliedTo' => $donation_code,
      ];
      $profileRecurring->addDistributions([$contribution]);
      // Sync Profile.
      $recurring_profile_Id = $profileRecurring->sync($am_net_name_id, $this->client, $this->logger);
      if (!empty($recurring_profile_Id)) {
        // Set Dues Maintenance - RecurringProfileId.
        $donation->setRecurringProfileId($recurring_profile_Id);
      }
    }
    // Sync the Contribution.
    $result = $donation->sync($this->client, $this->logger);
    // Update Order Item update sync status.
    $sync_status = [
      'order_id' => $order->id() ?? NULL,
      'order_item_id' => $order_item->id(),
      'purchased_entity_id' => $product_variation->id() ?? NULL,
      'order_item_type' => $product_variation->bundle() ?? NULL,
      'sync_status' => $result['item_processed'],
      'last_synced' => date('Y-m-d H:i:s'),
      'messages' => [],
    ];
    if ($result['item_processed'] != AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED) {
      $sync_status['messages'] = $result['messages'];
      $sync_status['sync_log'] = $result['messages']['error_message'] ?? '';
    }
    $this->orderItemUpdateSyncStatus($sync_status);
    // Return Result.
    return $result;
  }

  /**
   * Get Recurring Period Label By Code.
   *
   * @param string $interval
   *   The Period Code.
   *
   * @return string
   *   The Recurring Period Label.
   */
  public function getRecurringPeriodLabelByCode($interval = NULL) {
    if (empty($interval)) {
      return NULL;
    }
    $label = NULL;
    switch ($interval) {
      case "MNTH":
        $label = "Monthly";
        break;

      case "3MNT":
        $label = "Quarterly";
        break;

      case "YEAR":
        $label = "Annually";
        break;
    }
    return $label;
  }

  /**
   * Get profile start date based on period code and order placed date.
   *
   * @param string $interval
   *   The Period Code.
   * @param string $placed_date
   *   The Order Placed Date.
   * @param string $format
   *   The output date format.
   *
   * @return string|null
   *   The Profile Start date on the given format.
   */
  public function getProfileStartDateBasedOnPeriodCodeAndOrderPlacedDate($interval = NULL, $placed_date = NULL, $format = 'Y-m-d\TH:i:s') {
    if (empty($interval) || empty($placed_date)) {
      return NULL;
    }
    $profile_start_date = NULL;
    $base_time = strtotime($placed_date);
    switch ($interval) {
      case "MNTH":
        $profile_start_date = date($format, strtotime("+1 month", $base_time));
        break;

      case "3MNT":
        $profile_start_date = date($format, strtotime("+3 month", $base_time));
        break;

      case "YEAR":
        $profile_start_date = date($format, strtotime("+12 month", $base_time));
        break;
    }
    return $profile_start_date;
  }

  /**
   * Order Item update sync status.
   *
   * @param array $order_item_values
   *   Required field, The order item values.
   */
  public function orderItemUpdateSyncStatus(array $order_item_values = []) {
    if (empty($order_item_values)) {
      return;
    }
    $order_id = $order_item_values['order_id'] ?? NULL;
    $order_item_id = $order_item_values['order_item_id'] ?? NULL;
    if (empty($order_id) || empty($order_item_id)) {
      return;
    }
    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->orderStorage->load($order_id);
    if (!$order) {
      return;
    }
    $field_name = 'field_am_net_sync';
    if (!$order->hasField($field_name)) {
      return;
    }
    // Update Order Field: Am.net Sync.
    $order_items = [];
    $field_values = $order->get($field_name)->getValue();
    if (!empty($field_values) && is_array($field_values)) {
      $order_items = current($field_values);
    }
    $items = $order_items['items'] ?? [];
    $key = "item_{$order_id}_{$order_item_id}";
    $items[$key] = $order_item_values;
    $order_items['items'] = $items;
    // Save Changes.
    $order->set($field_name, $order_items);
    $order->save();
  }

  /**
   * Gets an AM.net Event Fee for the given event order item and registrant.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\user\UserInterface $registrant
   *   The registrant.
   *
   * @return array
   *   An array of the properties required to identify an AM.net Fee:
   *     - ty2: The fee code.
   *     - amt: The fee amount.
   *     - fss: 'A' for All, 'M' for Members, 'N' for Non-Members.
   */
  public function getAmNetEventFee(OrderItemInterface $order_item, UserInterface $registrant) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $order_item->getPurchasedEntity();
    // Check one day variation.
    $one_day_variation = FALSE;
    $one_day_registration = $variation->get('field_one_day_registration')->getString();
    if (!empty($one_day_registration)) {
      /** @var \DateTime $start_date */
      $start_date = $variation->get('field_applies_to_date_range')->start_date;
      /** @var \DateTime $end_date */
      $end_date = $variation->get('field_applies_to_date_range')->end_date;
      $hours_diff = $start_date->diff($end_date)->format('%h');
      $one_day_variation = abs($hours_diff) < 24;
    }
    $time = $order_item->getOrder()->getPlacedTime();
    $store = $order_item->getOrder()->getStore();
    $context = new Context($registrant, $store, $time);
    $quantity = $order_item->getQuantity();
    // Get Order Item Adjusted Total Price applying any coupon.
    $unit_price = $this->getOrderItemAdjustedTotalPrice($order_item);
    $pricing_options = $this->priceManager->getEventPricingOptions($variation, $quantity, $context);
    $early_bird = $pricing_options['current_option']['type'] === 'earlybird';
    $member = $registrant->hasRole('member');
    $regular_price = $pricing_options['current_option']['price'] ?? NULL;
    $member_price = $pricing_options['current_option']['member_price'] ?? NULL;

    // Support FREE events case 1.
    // Free events does not have any fees configured on AM.net.
    if (empty($unit_price)) {
      return [];
    }

    // Support FREE events case 2.
    // Ensure that the fee is Great than $0.0.
    // Calculator returns 1 if the first one is greater.
    $is_greater_than_zero = (Calculator::compare($unit_price, '0') == 1);
    if (!$is_greater_than_zero) {
      return [];
    }
    // Default to Standard Fee.
    $standard_fee_apply_to_member_type = $variation->get('field_override_s_fee_apply_to')->getString();
    $early_fee_apply_to_member_type = $variation->get('field_override_e_fee_apply_to')->getString();
    $fss = NULL;
    if ($one_day_variation) {
      $ty2 = 'OD';
    }
    elseif ($early_bird) {
      $ty2 = 'ER';
      if (!empty($early_fee_apply_to_member_type)) {
        $fss = $early_fee_apply_to_member_type;
      }
    }
    else {
      $ty2 = 'SF';
      if (!empty($standard_fee_apply_to_member_type)) {
        $fss = $standard_fee_apply_to_member_type;
      }
    }
    // Set FSS.
    if (empty($fss)) {
      if ($one_day_variation && !is_null($regular_price) && !is_null($member_price)) {
        $one_day_variation = ($regular_price == $member_price);
      }
      if ($one_day_variation) {
        $fss = 'A';
      }
      elseif ($member) {
        $fss = 'M';
      }
      else {
        $fss = 'N';
      }
    }
    // Return Fee values.
    return [
      'ty2' => $ty2,
      'amt' => $unit_price,
      'fss' => $fss,
    ];

  }

  /**
   * Checks if order item has custom adjustments.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return bool
   *   TRUE if order item has custom adjustments, otherwise FALSE.
   */
  public function orderItemHasCustomAdjustments(OrderItemInterface $order_item = NULL) {
    if (!$order_item) {
      return FALSE;
    }
    $adjustments = $order_item->getAdjustments();
    if (empty($adjustments)) {
      return FALSE;
    }
    foreach ($adjustments as $delta => $adjustment) {
      $is_commerce_adjustment = $adjustment && ($adjustment instanceof Adjustment);
      if (!$is_commerce_adjustment) {
        continue;
      }
      if ($adjustment->getType() == 'custom') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets an AM.net Session Fee for the given event order item and registrant.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return string|null
   *   An array of the properties required to identify an AM.net Fee.
   */
  public function getOrderItemAdjustedTotalPrice(OrderItemInterface $order_item = NULL) {
    if (!$order_item) {
      return NULL;
    }
    $order = $order_item->getOrder();
    $unit_price = $order_item->getAdjustedTotalPrice();
    // Note 'order item percentage off' already are included
    // on getAdjustedTotalPrice.
    $price_number = $unit_price->getNumber();
    // Check if the order has coupons applied.
    $coupons = $order->get('coupons')->referencedEntities();
    if (empty($coupons)) {
      // No coupons, return base price.
      return $price_number;
    }
    /* @var \Drupal\commerce_promotion\Entity\Coupon $coupon */
    // Coupons apply discount.
    // Multiple coupon applications is not allowed on VSCPA business logic..
    $coupon = current($coupons);
    // Get the promotion.
    $promotion = ($coupon) ? $coupon->getPromotion() : NULL;
    // Get the offer.
    $offer = ($promotion) ? $promotion->getOffer() : NULL;
    if (!$offer) {
      // No offer, return base price.
      return $price_number;
    }
    /* @var \Drupal\commerce_price\RounderInterface $rounder */
    $rounder = \Drupal::service('commerce_price.rounder');
    // Check the type of offer.
    $percentage_off = [
      'order_percentage_off',
    ];
    if (!in_array($offer->getPluginId(), $percentage_off)) {
      return $price_number;
    }
    /* @var \Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\OrderItemPercentageOff $offer */
    $percentage = $offer->getPercentage();
    $adjustment_amount = $unit_price->multiply($percentage);
    $adjustment_amount = $rounder->round($adjustment_amount);
    $amount = $unit_price->subtract($adjustment_amount);
    $price_number = $amount->getNumber();
    // Return price with discount applied.
    return $price_number;
  }

  /**
   * Gets an AM.net Session Fee for the given event order item and registrant.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\user\UserInterface $registrant
   *   The registrant.
   *
   * @return array
   *   An array of the properties required to identify an AM.net Fee:
   *     - ty2: The fee code.
   *     - amt: The fee amount.
   *     - fss: 'A' for All, 'M' for Members, 'N' for Non-Members.
   */
  public function getAmNetSessionFee(OrderItemInterface $order_item, UserInterface $registrant) {
    return [
      'ty2' => 'WR',
      'amt' => $order_item->getAdjustedUnitPrice()->getNumber(),
      'fss' => 'A',
    ];
  }

  /**
   * Gets membership donation order items related to a given membership.
   *
   * @param string $order_item_id
   *   The membership order item ID.
   *
   * @return array
   *   An array of the donation orders items related to the given membership.
   */
  public function getMembershipDonationItems($order_item_id = '') {
    if (empty($order_item_id)) {
      return [];
    }
    $membership_donations = [];
    // Get membership donation order items related to the given membership.
    $membership_donation_items = $this->orderItemStorage->getQuery()
      ->condition('field_order_item', $order_item_id)
      ->execute();

    if (!empty($membership_donation_items)) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface[] $membership_donations */
      $membership_donations = $this->orderItemStorage->loadMultiple($membership_donation_items);
    }
    return $membership_donations;
  }

  /**
   * Gets User linked firm.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The firm entity linked to the user.
   */
  public function getUserLinkedFirm(UserInterface $user = NULL) {
    if (!$user) {
      return NULL;
    }
    $user_field_field_firm = $user->get('field_firm');
    if (isset($user_field_field_firm->entity)) {
      return $user_field_field_firm->entity;
    }
    return NULL;
  }

  /**
   * Get the AM.net ID related to a firm ID.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The user entity.
   *
   * @return string|null
   *   The firm AM.net ID otherwise NULL.
   */
  public function getFirmAmNetId(TermInterface $firm = NULL) {
    if (!$firm) {
      return NULL;
    }
    if (!$firm->hasField('field_amnet_id')) {
      return NULL;
    }
    $firm_field_amnet_id = $firm->get('field_amnet_id')->getString();
    $firm_field_amnet_id = trim($firm_field_amnet_id);
    $firm_am_net_id = !empty($firm_field_amnet_id) ? $firm_field_amnet_id : NULL;
    return $firm_am_net_id;
  }

  /**
   * Check if the Cart owner is the firm admin for a given Firm.
   *
   * @param \Drupal\user\UserInterface|null $cart_owner
   *   The user entity.
   * @param \Drupal\taxonomy\TermInterface $firm
   *   The referenced firm.
   *
   * @return bool
   *   TRUE if the Cart owner is The firm, otherwise FALSE.
   */
  public function isCartOwnerFirmAdmin(UserInterface $cart_owner = NULL, TermInterface $firm = NULL) {
    if (!$cart_owner) {
      return FALSE;
    }
    // Get Cart owner roles.
    $cart_owner_roles = $cart_owner->getRoles();
    if (empty($cart_owner_roles)) {
      return FALSE;
    }
    // Check if the cart owner has the role: Firm Admin.
    $is_firm_admin = in_array('firm_administrator', $cart_owner_roles);
    if (!$is_firm_admin) {
      return FALSE;
    }
    if (!$firm) {
      return TRUE;
    }
    // Compare the Cart owner linked firm  ID with the reference firm ID.
    $field_cart_owner_firm = $cart_owner->get('field_firm');
    /* @var \Drupal\taxonomy\TermInterface $cart_owner_firm */
    $cart_owner_firm = $field_cart_owner_firm->entity ?? NULL;
    if (!$cart_owner_firm) {
      return FALSE;
    }
    // Check if the firms are the same.
    if ($firm->id() == $cart_owner_firm->id()) {
      return TRUE;
    }
    // Is possible that the Cart owner firm is a parent firm of
    // the referenced firm.
    $parentFirms = $this->firmGetParentFirms($firm);

    foreach ($parentFirms as $parentFirm) {
      if ($parentFirm->id() === $cart_owner_firm->id()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Determine's the firm's pareent firm.
   *
   * @param \Drupal\taxonomy\TermInterface $firm
   *   Firm to look for parents for.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Array of parent firms.
   */
  protected function firmGetParentFirms(TermInterface $firm) {
    return $this->termStorage->loadParents($firm->id());
  }

  /**
   * Get credit card number by payment Method.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   Firm to look for parents for.
   *
   * @return string
   *   The credit card number.
   */
  protected function getCardNumber(PaymentMethodInterface $payment_method = NULL) {
    if (!$payment_method) {
      return '';
    }
    $card_number = $payment_method->amnet_number->value ?? '';
    if (empty($card_number) && $payment_method->hasField('card_number')) {
      $card_number = $payment_method->get('card_number')->getString();
    }
    $card_type = $payment_method->get('card_type')->getString();
    $group = ['amex', 'jcb', 'dinersclub'];
    if ((strlen($card_number) == 4) && !empty($card_type)) {
      // Apply mask to CC number.
      if ($card_type == "visa") {
        $card_number = "4***********" . $card_number;
      }
      elseif ($card_type == "mastercard") {
        $card_number = "5***********" . $card_number;
      }
      elseif (in_array($card_type, $group)) {
        $card_number = "3**********" . $card_number;
      }
      elseif ($card_type == "discover") {
        $card_number = "6***********" . $card_number;
      }
    }
    return $card_number;
  }

  /**
   * Get credit card info associated with a given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The target order.
   *
   * @return array
   *   The credit card info.
   */
  protected function getCardInfo(OrderInterface $order = NULL) {
    $info = [
      'card_number' => NULL,
      'card_expiration_date' => NULL,
      'payment_authorization_code' => NULL,
      'payment_reference_number' => NULL,
      'payor' => NULL,
      'tran_date' => NULL,
    ];
    if (!$order) {
      return $info;
    }
    // Set tran date.
    $order_placed_time = new DrupalDateTime();
    $order_placed_time->setTimestamp($order->getPlacedTime());
    $tran_date = $order_placed_time->format('Y-m-d');
    $tran_date = $tran_date . 'T00:00:00';
    $info['tran_date'] = $tran_date;
    // Get the Payment Method.
    $payments = $this->paymentStorage->loadMultipleByOrder($order);
    $payment = !empty($payments) ? current($payments) : NULL;
    /* @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = !empty($payment) ? $payment->getPaymentMethod() : NULL;
    if ($payment_method) {
      // Set Card number.
      $card_number = $this->getCardNumber($payment_method);
      $info['card_number'] = $card_number;
      // Set Card Exp.
      $payment_method_card_exp_year = $payment_method->card_exp_year->value ?? NULL;
      $payment_method_card_exp_month = $payment_method->card_exp_month->value ?? NULL;
      if (!empty($payment_method_card_exp_year) && !empty($payment_method_card_exp_month)) {
        $card_exp = new DrupalDateTime();
        $card_exp->setDate($payment_method_card_exp_year, $payment_method_card_exp_month, 1);
      }
      else {
        $expire_time = $payment_method->getExpiresTime();
        $card_exp = DrupalDateTime::createFromTimestamp($expire_time);
      }
      $card_expiration_date = $card_exp->format('m/y');
      $info['card_expiration_date'] = $card_expiration_date;
    }
    // Get Billing info.
    $billing_profile = $order->getBillingProfile();
    $billing_first_name = $billing_profile->address->given_name ?? '';
    $billing_last_name = $billing_profile->address->family_name ?? '';
    $payor = "{$billing_first_name} {$billing_last_name}";
    $info['payor'] = $payor;
    if ($payment) {
      // Get Remote ID.
      $remote_id = explode('|', $payment->getRemoteId());
      $payment_reference_number = $remote_id[0] ?? '';
      $payment_authorization_code = $remote_id[1] ?? '';
      // Set AuthCode.
      $info['payment_authorization_code'] = $payment_authorization_code;
      // Set RefNbr.
      $info['payment_reference_number'] = $payment_reference_number;
    }
    return $info;
  }

  /**
   * Mark order as completed for Sync.
   *
   * @param string $order_id
   *   The Order ID.
   */
  public function setOrderSyncAsCompleted($order_id = NULL) {
    if (empty($order_id)) {
      return;
    }
    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->orderStorage->load($order_id);
    if (!$order) {
      return;
    }
    $order_items = $order->getItems();
    if (empty($order_items)) {
      $dummy_id = '00000000';
      $sync_status = [
        'order_id' => $order_id,
        'order_item_id' => $dummy_id,
        'purchased_entity_id' => $dummy_id,
        'sync_status' => AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED,
        'sync_log' => 'sync done',
        'last_synced' => date('Y-m-d H:i:s'),
        'messages' => [],
      ];
      $this->orderItemUpdateSyncStatus($sync_status);
      return;
    }
    foreach ($order_items as $order_item) {
      // Update Order Item update sync status.
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $event_product_variation */
      $product_variation = $order_item->getPurchasedEntity();
      $purchased_entity_id = $product_variation ? $product_variation->id() : NULL;
      $order_item_type = $product_variation ? $product_variation->bundle() : NULL;
      $sync_status = [
        'order_id' => $order->id() ?? NULL,
        'order_item_id' => $order_item->id(),
        'purchased_entity_id' => $purchased_entity_id,
        'order_item_type' => $order_item_type,
        'sync_status' => AmNetOrderInterface::ORDER_ITEM_SYNCHRONIZED,
        'sync_log' => 'sync done',
        'last_synced' => date('Y-m-d H:i:s'),
        'messages' => [],
      ];
      $this->orderItemUpdateSyncStatus($sync_status);
    }
  }

  /**
   * Mark order as no synced.
   *
   * @param string $order_id
   *   The Order ID.
   */
  public function setOrderAsNoSynced($order_id = NULL) {
    if (empty($order_id)) {
      return;
    }
    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->orderStorage->load($order_id);
    if (!$order) {
      return;
    }
    $order_items = $order->getItems();
    if (empty($order_items)) {
      return;
    }
    foreach ($order_items as $order_item) {
      // Update Order Item update sync status.
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $event_product_variation */
      $product_variation = $order_item->getPurchasedEntity();
      $sync_status = [
        'order_id' => $order->id() ?? NULL,
        'order_item_id' => $order_item->id(),
        'purchased_entity_id' => $product_variation->id() ?? NULL,
        'order_item_type' => $product_variation->bundle() ?? NULL,
        'sync_status' => AmNetOrderInterface::ORDER_ITEM_NOT_SYNCHRONIZED,
        'sync_log' => 'sync done',
        'last_synced' => date('Y-m-d H:i:s'),
        'messages' => [],
      ];
      $this->orderItemUpdateSyncStatus($sync_status);
    }
  }

  /**
   * Get Billing Class Code.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user interface.
   *
   * @return string|null
   *   The given Billing Class code for the given user, Otherwise NULL.
   */
  protected function getBillingClassCode(UserInterface $user = NULL) {
    if (!$user) {
      return NULL;
    }
    $billingClassServiceManager = \Drupal::service('am_net_membership.billing_class_checker_manager');
    /** @var \Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface $billingClassChecker */
    $billingClassChecker = $billingClassServiceManager->getChecker();
    $billingClassCode = $billingClassChecker->getCode($user);
    return $billingClassCode;
  }

}
