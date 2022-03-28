<?php

namespace Drupal\vscpa_commerce\Form;

use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\vscpa_commerce\EventRegistrationManagerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\am_net_cpe\Element\AdaComplianceModal;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;

/**
 * A form for selecting session options while adding an item to the cart.
 */
class AddToCartWithSessionSelectionsForm extends FormBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The purchasable entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $entity;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * The store context.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The chain base price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The employee id.
   *
   * @var int
   */
  protected $employeeId = NULL;

  /**
   * The event registration manager.
   *
   * @var \Drupal\vscpa_commerce\EventRegistrationManagerInterface
   */
  protected $eventRegistrationManager;

  /**
   * Constructs a AddToCartAfterAgreementsForm object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
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
   * @param \Drupal\vscpa_commerce\EventRegistrationManagerInterface $event_registration_manager
   *   The event registration manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(Request $request, EntityTypeManagerInterface $entity_type_manager, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, CurrentStoreInterface $current_store, ChainPriceResolverInterface $chain_price_resolver, AccountInterface $current_user, EventRegistrationManagerInterface $event_registration_manager) {
    $this->request = $request;
    $this->entityTypeManager = $entity_type_manager;
    $this->entity = $this->entityTypeManager->getStorage($request->get('type'))->load($request->get('id'));
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->orderTypeResolver = $order_type_resolver;
    $this->currentStore = $current_store;
    $this->chainPriceResolver = $chain_price_resolver;
    $this->currentUser = $current_user;
    $this->eventRegistrationManager = $event_registration_manager;
    $this->employeeId = $request->get('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_type.manager'),
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
  public function getFormId() {
    return 'vscpa_commerce_with_session_selections_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $only_contains_materials = FALSE;
    $order_item = $this->cartManager->createOrderItem($this->entity);
    if ($grouped_options = $this->eventRegistrationManager->getRemainingOptions($this->entity, $order_item, FALSE)) {
      $product = $this->entity->getProduct();
      $product_manager = \Drupal::service('am_net_cpe.product_manager');
      $labels_added = [];
      foreach ($grouped_options as $gid => $timeslot_group) {
        $label = $timeslot_group['label'];
        $label_token = vscpa_commerce_generate_machine_name($label);
        if (isset($labels_added[$label_token])) {
          $label = NULL;
        }
        else {
          $labels_added[$label_token] = TRUE;
        }
        $form['timeslot_groups'][$gid] = [
          '#title' => $label,
          '#type' => 'details',
          '#open' => TRUE,
          '#attributes' => [
            'class' => ['material_preference_timeslot_groups'],
          ],
        ];
        $timeslot_group_type = $timeslot_group['type'] ?? NULL;
        $is_materials = ($timeslot_group_type == 'materials');
        $is_sponsors = ($timeslot_group_type == 'sponsors');
        $attributes = [
          'oninvalid' => "this.setCustomValidity('To register, please select a/the session from this timeslot.')",
        ];
        if ($is_materials) {
          foreach ($timeslot_group['timeslots'] as $tid => $timeslot) {
            $options = [];
            if ((count($timeslot['sessions']) == 1)) {
              // Render as Checkbox.
              $session = current($timeslot['sessions']);
              if ($product_manager->isSessionOpenForRegistration($product, $session['entity'])) {
                $session_default_value = $session['entity']->id();
                $field = [
                  '#title' => $session['entity']->label(),
                  '#type' => 'checkbox',
                  '#required' => FALSE,
                  '#default_value' => $session_default_value,
                  '#return_value' => $session_default_value,
                  '#attributes' => $attributes,
                ];
              }
            }
            else {
              // Render as Radios.
              foreach ($timeslot['sessions'] as $sid => $session) {
                if ($product_manager->isSessionOpenForRegistration($product, $session['entity'])) {
                  $options[$sid] = $session['entity']->label();
                }
              }
              $field = [
                '#type' => 'radios',
                '#options' => $options,
                '#required' => TRUE,
                '#attributes' => $attributes,
              ];
            }
            $form['timeslot_groups'][$gid]['timeslots'][$tid]['session'] = $field;
            if (($field['#type'] == 'radios') && !empty($options)) {
              $form['timeslot_groups'][$gid]['timeslots'][$tid]['session']['#default_value'] = key($options);
            }
          }
          $only_contains_materials = TRUE;
        }
        if ($is_sponsors) {
          $form['timeslot_groups'][$gid]['#attributes']['class'][] = 'sponsors_preference_timeslot_groups';
          foreach ($timeslot_group['timeslots'] as $tid => $timeslot) {
            // Add the subtitle.
            $form['timeslot_groups'][$gid]['timeslots'][$tid] = [
              '#title' => $timeslot['label'],
              '#type' => 'details',
              '#open' => TRUE,
            ];
            // Render the options as Checkbox.
            $options = [];
            foreach ($timeslot['sessions'] as $sid => $session) {
              $options[$sid] = $session['entity']->label();
            }
            $field = [
              '#type' => 'checkboxes',
              '#options' => $options,
              '#required' => FALSE,
            ];
            $form['timeslot_groups'][$gid]['timeslots'][$tid]['session'] = $field;
            if (($field['#type'] == 'radios') && !empty($options)) {
              $form['timeslot_groups'][$gid]['timeslots'][$tid]['session']['#default_value'] = key($options);
            }
          }
        }
        elseif (!empty($timeslot_group['timeslots'])) {
          foreach ($timeslot_group['timeslots'] as $tid => $timeslot) {
            $form['timeslot_groups'][$gid]['timeslots'][$tid] = [
              '#title' => $timeslot['label'],
              '#type' => 'details',
              '#open' => TRUE,
            ];
            if (!empty($timeslot['sessions'])) {
              if (count($timeslot['sessions']) > 1) {
                $options = [];
                foreach ($timeslot['sessions'] as $sid => $session) {
                  if ($product_manager->isSessionOpenForRegistration($product, $session['entity'])) {
                    $options[$sid] = $session['entity']->label();
                  }
                };
                $form['timeslot_groups'][$gid]['timeslots'][$tid]['session'] = [
                  '#type' => 'radios',
                  '#options' => $options,
                  '#required' => TRUE,
                  '#attributes' => $attributes,
                ];
                if (!empty($options)) {
                  $form['timeslot_groups'][$gid]['timeslots'][$tid]['session']['#default_value'] = key($options);
                }
              }
              else {
                $session = current($timeslot['sessions']);
                if ($product_manager->isSessionOpenForRegistration($product, $session['entity'])) {
                  $sid = key($timeslot['sessions']);
                  $session_option = [
                    '#type' => 'checkbox',
                    '#title' => $session['entity']->label(),
                    '#required' => FALSE,
                    '#attributes' => $attributes,
                    '#default_value' => 1,
                  ];
                  if ($session['general']) {
                    $session_option['#default_value'] = 1;
                    $session_option['#disabled'] = FALSE;
                  }
                  $form['timeslot_groups'][$gid]['timeslots'][$tid]['session'][$sid] = $session_option;
                }
              }
            }
            $only_contains_materials = FALSE;
          }
        }
      }
    }
    // Add Section 'Special Assistance Needed'.
    $form['special_needs'] = AdaComplianceModal::getSpecialNeeds($this->currentUser);
    if ($only_contains_materials) {
      if ($route = $this->request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $route->setDefault('_title', 'Material Preference');
      }
      $form['#attributes']['class'][] = 'material-preference-form';
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['accept'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#attributes' => [
        'class' => ['use-ajax'],
      ],
      '#submit' => ['::submitForm'],
      '#attached' => [
        'library' => [
          'core/jquery',
          'core/drupal',
          'core/drupal.dialog.ajax',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Validation. (public function validateForm()).
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $order_item_storage->createFromPurchasableEntity($this->entity);
    // Ensure an not-null unit price.
    $order_item_total = $order_item->getTotalPrice();
    if (is_null($order_item_total)) {
      $zero = new Price('0', 'USD');
      $order_item->setUnitPrice($zero);
    }
    // Set user selection.
    $uid = is_numeric($this->employeeId) ? $this->employeeId : $this->currentUser()->id();
    $order_item->set('field_user', $uid);
    $order_item->save();
    // Set session selections.
    $selections = [];

    if ($timeslot_groups = $form_state->getValue('timeslot_groups')) {
      foreach ($timeslot_groups as $gid => $group) {
        if (!empty($group['timeslots'])) {
          foreach ($group['timeslots'] as $tid => $timeslot) {
            // Radios.
            if (is_array($timeslot['session'])) {
              foreach ($timeslot['session'] as $sid => $selected) {
                if ($selected) {
                  $selections[] = $sid;
                }
              }
            }
            // Checkbox.
            elseif ($timeslot['session']) {
              $selections[] = $timeslot['session'];
            }
          }
        }
      }
    }
    if (!empty($selections)) {
      $this->eventRegistrationManager->selectSessions($selections, $order_item);
    }
    // Add 'Special Needed' to the order item.
    $selected_special_needs = [];
    $items = $form_state->getValue(['special_needs', 'assistance_requested'], []);
    foreach ($items as $tid => $selected) {
      if ($selected) {
        $selected_special_needs[] = $tid;
      }
    }
    $items = $form_state->getValue(['special_needs', 'dietary_restrictions'], []);
    foreach ($items as $tid => $selected) {
      if ($selected) {
        $selected_special_needs[] = $tid;
      }
    }
    if (!empty($selected_special_needs)) {
      $order_item->setData('selected_special_needs', $selected_special_needs);
    }
    /** @var \Drupal\commerce\PurchasableEntityInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();
    $order_type = $this->orderTypeResolver->resolve($order_item);
    $store = $this->selectStore($purchased_entity);
    $cart = $this->cartProvider->getCart($order_type, $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type, $store);
    }
    $this->cartManager->addOrderItem($cart, $order_item, FALSE);
    // Check referer.
    $referer = $this->getRequest()->headers->get('referer');
    if (!empty($referer)) {
      $form_state->setRedirectUrl(Url::fromUri($referer));
    }
    elseif ($purchased_entity instanceof ProductVariationInterface && $product = $purchased_entity->getProduct()) {
      $form_state->setRedirect('entity.commerce_product.canonical', ['commerce_product' => $product->id()]);
    }
  }

  /**
   * Selects the store for the given purchasable entity.
   *
   * If the entity is sold from one store, then that store is selected.
   * If the entity is sold from multiple stores, and the current store is
   * one of them, then that store is selected.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The entity being added to cart.
   *
   * @throws \Exception
   *   When the entity can't be purchased from the current store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The selected store.
   */
  protected function selectStore(PurchasableEntityInterface $entity) {
    $stores = $entity->getStores();
    if (count($stores) === 1) {
      $store = reset($stores);
    }
    else {
      $store = $this->currentStore->getStore();
      if (!in_array($store, $stores)) {
        // Indicates that the site listings are not filtered properly.
        throw new \Exception("The given entity can't be purchased from the current store.");
      }
    }

    return $store;
  }

}
