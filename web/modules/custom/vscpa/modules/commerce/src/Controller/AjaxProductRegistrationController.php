<?php

namespace Drupal\vscpa_commerce\Controller;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\vscpa_commerce\Entity\EventSession;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Class AjaxProductRegistrationController.
 *
 *  Handle add to cart on product registration.
 */
class AjaxProductRegistrationController extends ControllerBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Constructs a new AjaxProductRegistrationController object.
   *
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, CurrentStoreInterface $current_store, AccountInterface $current_user) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_store.current_store'),
      $container->get('current_user')
    );
  }

  /**
   * Do Product registration.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The Json result of the operation.
   */
  public function doRegistration(Request $request) {
    $data_string = $request->getContent();
    $data = json_decode($data_string);
    // Requires parameters.
    $employees = $data->employees ?? NULL;
    $variation_id = $data->variation_id ?? NULL;
    if (empty($employees) || empty($variation_id)) {
      return new JsonResponse([
        'data' => [
          'success' => FALSE,
          'message' => $this->t('Missing information to complete the registration request.'),
        ],
        'method' => 'POST',
      ]);
    }
    // Prepare the cart.
    $store = $this->currentStore->getStore();
    $cart_owner = $this->currentUser;
    if (!$cart = $this->cartProvider->getCart('default', $store, $cart_owner)) {
      $cart = $this->cartProvider->createCart('default', $store, $cart_owner);
    }
    $variation = ProductVariation::load($variation_id);
    $employees_result = [];
    // Handle session registration.
    $em_session = $this->getElectronicMaterialsSession($variation);
    $has_session = ($em_session instanceof EventSession);
    // Add to cart product per each selected employee.
    foreach ($employees as $delta => $employee) {
      $order_item = $this->cartManager->createOrderItem($variation, 1);
      if ($order_item->hasField('field_user')) {
        $order_item->set('field_user', $employee);
      }
      if ($has_session && $order_item->hasField('field_sessions_selected')) {
        $order_item->set('field_sessions_selected', [$em_session]);
      }
      $result = $this->cartManager->addOrderItem($cart, $order_item, FALSE);
      $item['employee_id'] = $employee;
      $item['added_to_cart'] = FALSE;
      $item['order_item_id'] = NULL;
      if ($result && !empty($result->id())) {
        $item['added_to_cart'] = TRUE;
        $item['order_item_id'] = $result->id();
      }
      $employees_result[] = $item;
    }
    // Try to register people.
    return new JsonResponse([
      'success' => TRUE,
      'employees' => $employees_result,
      'variation_id' => $variation_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getElectronicMaterialsSession(ProductVariationInterface $variation) {
    if (!$variation) {
      return NULL;
    }
    $product = $variation->getProduct();
    if (!$product) {
      return NULL;
    }
    if (!$product->hasField('field_event_timeslot_groups')) {
      return NULL;
    }
    $timeslot_groups = $product->get('field_event_timeslot_groups');
    foreach ($timeslot_groups as $group_item) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $group */
      /** @var \Drupal\Core\Entity\ContentEntityInterface $timeslot */
      /** @var \Drupal\Core\Entity\ContentEntityInterface $session_code */
      $group = $group_item->entity ?? NULL;
      if (!$group) {
        continue;
      }
      if (!$group->hasField('field_timeslots')) {
        continue;
      }
      $timeslots = $group->get('field_timeslots');
      foreach ($timeslots as $timeslot_item) {
        $timeslot = $timeslot_item->entity;
        if ($timeslot && $timeslot->hasField('field_sessions')) {
          foreach ($timeslot->get('field_sessions') as $session) {
            $session_entity = $session->entity ?? NULL;
            $session_code = ($session_entity) ? $session_entity->get('field_session_code')
              ->getString() : '';
            if ($session_code == 'EM1') {
              return $session_entity;
            }
          }
        }
      }
    }
    return NULL;
  }

}
