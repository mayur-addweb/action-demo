<?php

namespace Drupal\vscpa_commerce\Form;

use Drupal\commerce_cart\Form\AddToCartForm as BaseAddToCartForm;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vscpa_commerce\EventRegistrationManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a customized order item add to cart form.
 */
class AddToCartForm extends BaseAddToCartForm {

  /**
   * The event registration manager.
   *
   * @var \Drupal\vscpa_commerce\EventRegistrationManagerInterface
   */
  protected $eventRegistrationManager;

  /**
   * Constructs a new AddToCartForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain base price resolver.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\vscpa_commerce\EventRegistrationManagerInterface $registration_manager
   *   The registration agreements manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, CurrentStoreInterface $current_store, ChainPriceResolverInterface $chain_price_resolver, AccountInterface $current_user, EventRegistrationManagerInterface $registration_manager) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time, $cart_manager, $cart_provider, $order_type_resolver, $current_store, $chain_price_resolver, $current_user);
    $this->eventRegistrationManager = $registration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.current_store'),
      $container->get('commerce_price.chain_price_resolver'),
      $container->get('current_user'),
      $container->get('vscpa_commerce.event_registration_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isFirmAdmin(AccountInterface $account = NULL) {
    if (!$account) {
      return FALSE;
    }
    // Check Role.
    return in_array('firm_administrator', $account->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Add 'Special Needed' to the order item.
    $values = $form_state->getUserInput();
    $selected_special_needs = [];
    $items = $values['special_needs']['assistance_requested'] ?? [];
    foreach ($items as $tid => $selected) {
      if ($selected) {
        $selected_special_needs[] = $tid;
      }
    }
    $items = $values['special_needs']['dietary_restrictions'] ?? [];
    foreach ($items as $tid => $selected) {
      if ($selected) {
        $selected_special_needs[] = $tid;
      }
    }
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->entity ?? NULL;
    if (!empty($selected_special_needs) && $order_item) {
      $this->entity->unsetData('selected_special_needs');
      $this->entity->setData('selected_special_needs', $selected_special_needs);
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Set user selection.
    $field_user = $form_state->getValue(['field_user', 0, 'target_id']);
    $uid = is_numeric($field_user) ? $field_user : \Drupal::currentUser()->id();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->entity;
    // Get the selected variation, if it changes via ajax.
    // @todo: Figure out the underlying issue ProductLazyBuilders::addToCart
    $variation_input = ['purchased_entity', 0, 'variation'];
    if ($selected_variation = $form_state->getValue($variation_input)) {
      $purchased_entity = ProductVariation::load($selected_variation);
    }
    else {
      /** @var \Drupal\commerce\PurchasableEntityInterface $purchased_entity */
      $purchased_entity = $order_item->getPurchasedEntity();
    }
    $is_anonymous = $this->currentUser()->isAnonymous();
    $is_firm_admin = $this->isFirmAdmin($this->currentUser());
    $is_event_product = $this->eventRegistrationManager->isEventRegistration($order_item);
    $is_event_or_product_registration = $this->eventRegistrationManager->isEventOrProductRegistration($order_item);
    $is_event_registration_open = ($is_event_product) ? $this->eventRegistrationManager->isEventRegistrationOpen($purchased_entity) : FALSE;
    $external_registration_url = ($is_event_registration_open) ? $this->eventRegistrationManager->getExternalRegistrationUrl($purchased_entity) : FALSE;
    $is_external_registration = !empty($external_registration_url);
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $purchased_entity->getProduct();
    $is_event_bundle = FALSE;
    if ($product && $product->hasField('field_search_index_is_bundle')) {
      $is_event_bundle = ($product->get('field_search_index_is_bundle')->getString() == '1');
    }
    if ($is_anonymous) {
      $submit_text = $is_event_or_product_registration ? $this->t('Please Login to Register') : $this->t('Add to cart');
    }
    else {
      $submit_text = $is_event_or_product_registration ? $this->t('Register') : $this->t('Add to cart');
    }
    if ($is_firm_admin) {
      // Firm admin Should use the employee selector For element.
      $actions['submit'] = [];
    }
    elseif ($is_anonymous) {
      // User is anonymous.
      $destination = $current_uri = \Drupal::request()->getRequestUri();
      $link_options = [
        'query' => ['destination' => $destination],
        'absolute' => TRUE,
      ];
      $actions['submit'] = [
        '#type' => 'link',
        '#title' => $submit_text,
        '#url' => Url::fromRoute('simplesamlphp_auth.saml_login', [], $link_options),
        '#attributes' => [
          'class' => ['btn', 'btn-success'],
          'role' => 'button',
        ],
      ];
    }
    elseif ($is_event_product && !$is_event_registration_open) {
      // Is a event not available for registration.
      $actions['submit'] = [];
    }
    else {
      if ($is_external_registration) {
        $actions['submit'] = [
          '#type' => 'link',
          '#title' => $submit_text,
          '#url' => Url::fromUri($external_registration_url),
          '#attributes' => [
            'class' => ['btn', 'btn-success'],
            'role' => 'button',
          ],
        ];
      }
      elseif (($purchased_entity->bundle() === 'event_registration') && $selection_options = $this->eventRegistrationManager->getRemainingOptions($purchased_entity, $order_item)) {
        // User is authenticated.
        // Check if the user is already registered.
        if ($is_event_bundle) {
          $is_user_registered = vscpa_commerce_bundle_product_was_purchased($purchased_entity, $uid);
          $register_message = t("<i class='text-center'><u>We notice you're already registered for an event offered by this bundle, so unfortunately cannot process your registration for the entire bundle at this time. To modify your registration, call the VSCPA Learning Team at (800) 733-8272.</u></i>");
          if (!$is_user_registered) {
            // Check if the user has any of the child event in the cart.
            $is_user_registered = vscpa_commerce_are_child_products_in_cart($purchased_entity, $uid);
            $register_message = t("<i class='text-center'><u>This package contains a single event already in your cart. If you wish to register for the entire package, please remove the single event from your cart. Questions? Call the VSCPA Learning Team at (800) 733-8272.</u></i>");
          }
        }
        else {
          $is_user_registered = vscpa_commerce_product_was_purchased($purchased_entity, $uid);
          $register_message = t('<i class="text-center"><u>You are already registered! Questions? Call (800) 733-8272.</u></i>');
          if (!$is_user_registered) {
            // Check if the user has any of the parent event in the cart.
            $is_user_registered = vscpa_commerce_is_parent_bundle_product_in_cart($purchased_entity, $uid);
            $register_message = t("<i class='text-center'><u>This event is part of a package already in your cart. If you wish to register for this event ONLY, please remove the package from your cart. Questions? Call the VSCPA Learning Team at (800) 733-8272.</u></i>");
          }
        }
        if ($is_user_registered && !$is_firm_admin) {
          $actions['submit'] = [
            '#type' => 'markup',
            '#markup' => $register_message,
          ];
        }
        else {
          // Submit to custom cart form when there are remaining
          // session options.
          $actions['submit'] = [
            '#type' => 'link',
            // @codingStandardsIgnoreLine
            '#title' => $submit_text,
            '#url' => Url::fromRoute('vscpa_commerce.add_to_cart_with_session_selections', [
              'type' => $purchased_entity->getEntityTypeId(),
              'id' => $purchased_entity->id(),
              'user' => $uid,
            ]),
            '#attributes' => [
              'class' => ['use-ajax', 'btn', 'btn-success'],
              'role' => 'button',
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 700,
              ]),
            ],
            '#attached' => [
              'library' => [
                'core/jquery',
                'core/drupal',
                'core/drupal.dialog.ajax',
                'vscpa_commerce/add_to_cart',
              ],
            ],
          ];
        }
      }
      else {
        if ($is_event_bundle) {
          // Check if the user has any of the child event in the cart.
          $is_user_registered = vscpa_commerce_are_child_products_in_cart($purchased_entity, $uid);
          $register_message = t("<i class='text-center'><u>This package contains a single event already in your cart. If you wish to register for the entire package, please remove the single event from your cart. Questions? Call the VSCPA Learning Team at (800) 733-8272.</u></i>");
        }
        else {
          $is_user_registered = FALSE;
        }
        if (!$is_user_registered) {
          $is_user_registered = vscpa_commerce_product_was_purchased($purchased_entity, $uid);
          $register_message = t('<i class="text-center"><small><u>You are already registered! Questions? Call (800) 733-8272.</u></small></i>');
        }
        if ($is_user_registered && !$is_firm_admin) {
          $actions['submit'] = [
            '#type' => 'markup',
            '#markup' => $register_message,
          ];
        }
        else {
          if ($is_event_product && !$is_anonymous) {
            // Add Modal: 'Special Assistance Needed'.
            $actions['ada'] = [
              '#type' => 'ada_compliance_modal',
            ];
          }
          // Standard submit handler.
          $actions['submit'] = [
            '#type' => 'submit',
            // @codingStandardsIgnoreLine
            '#value' => $submit_text,
            '#submit' => ['::submitForm'],
          ];
        }
      }
    }
    return $actions;
  }

}
