<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldFormatter;

use Drupal\am_net_cpe\EventHelper;
use Drupal\commerce\Context;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\vscpa_commerce\PriceManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceDefaultFormatter;

/**
 * Plugin implementation of the 'vscpa_commerce_current_price' formatter.
 *
 * @FieldFormatter(
 *   id = "vscpa_commerce_current_price",
 *   label = @Translation("[VSCPA Commerce] Current price"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 */
class CurrentPriceFormatter extends PriceDefaultFormatter implements ContainerFactoryPluginInterface {

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
   * The price manager.
   *
   * @var \Drupal\vscpa_commerce\PriceManagerInterface
   */
  protected $priceManager;

  /**
   * Constructs a new PriceCalculatedFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\vscpa_commerce\PriceManagerInterface $price_manager
   *   The price manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CurrencyFormatterInterface $currency_formatter, CurrentStoreInterface $current_store, AccountInterface $current_user, PriceManagerInterface $price_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $currency_formatter);

    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->priceManager = $price_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('commerce_price.currency_formatter'),
      $container->get('commerce_store.current_store'),
      $container->get('current_user'),
      $container->get('vscpa_commerce.price_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $context = new Context($this->currentUser, $this->currentStore->getStore());
    $quantity = 1;
    $elements = [];
    /** @var \Drupal\commerce_price\Plugin\Field\FieldType\PriceItem $item */
    foreach ($items as $delta => $item) {
      /** @var \Drupal\commerce\PurchasableEntityInterface $purchasable_entity */
      $purchasable_entity = $item->getEntity();
      switch ($purchasable_entity->bundle()) {
        case 'event_registration':
          $elements[$delta] = $this->prepareEventPrice($purchasable_entity, $quantity, $context);
          break;

        case 'session_registration':
          $elements[$delta] = $this->prepareSessionPrice($purchasable_entity, $quantity, $context);
          break;

        case 'self_study_registration':
          $elements[$delta] = $this->prepareSelfStudyPrice($purchasable_entity, $quantity, $context);
          break;
      }

    }

    return $elements;
  }

  /**
   * Prepares a render array for a current event price.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return array
   *   A render array for the current event price.
   */
  protected function prepareEventPrice(PurchasableEntityInterface $purchasable_entity, $quantity, Context $context) {
    if (!$pricing_options = $this->priceManager->getEventPricingOptions($purchasable_entity, $quantity, $context)) {
      return [
        '#markup' => $this->t('No event pricing available.'),
      ];
    }

    // Format prices.
    /** @var \Drupal\commerce_price\Price $price */
    if (isset($pricing_options['current_option']['price'])) {
      $price = $pricing_options['current_option']['price'];
      $currency = $price->getCurrencyCode();
      $member_price = isset($pricing_options['current_option']['member_price']) ? $pricing_options['current_option']['member_price'] : $price;
      $pricing_options['current_option']['price'] = $this->currencyFormatter->format($price->getNumber(), $currency);
      $pricing_options['current_option']['member_price'] = $this->currencyFormatter->format($member_price->getNumber(), $currency);
    }
    if (isset($pricing_options['next_option']['price'])) {
      $price = $pricing_options['next_option']['price'];
      $currency = $price->getCurrencyCode();
      $member_price = isset($pricing_options['next_option']['member_price']) ? $pricing_options['next_option']['member_price'] : $price;
      $pricing_options['next_option']['price'] = $this->currencyFormatter->format($price->getNumber(), $currency);
      $pricing_options['next_option']['member_price'] = $this->currencyFormatter->format($member_price->getNumber(), $currency);
    }
    $badge_class = $this->priceManager->getEventProductBadgeEventClass($purchasable_entity);
    if (EventHelper::isEventGroupByPurchasableEntity($purchasable_entity)) {
      $event_registration_status = 'event-open';
      $discount_off_label = NULL;
    }
    else {
      $event_registration_status = $this->priceManager->isEventRegistrationOpen($purchasable_entity) ? 'event-open' : 'event-close';
      $discount_off_label = $this->priceManager->getEventProductDiscountOffLabel($purchasable_entity);
    }
    return [
      '#theme' => 'vscpa_commerce_current_price_event',
      '#current_option' => $pricing_options['current_option'],
      '#next_option' => isset($pricing_options['next_option']) ? $pricing_options['next_option'] : NULL,
      '#badge_class' => $badge_class,
      '#discount_off_label' => $discount_off_label,
      '#status' => $event_registration_status,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Prepares a render array for a current self-study course price.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return array
   *   A render array for the current self-study course price.
   */
  protected function prepareSelfStudyPrice(PurchasableEntityInterface $purchasable_entity, $quantity, Context $context) {
    if (!$pricing_options = $this->priceManager->getSelfStudyPricingOptions($purchasable_entity, $quantity, $context)) {
      return [
        '#markup' => $this->t('No self-study pricing available.'),
      ];
    }

    // Format prices.
    if (isset($pricing_options['price'])) {
      /** @var \Drupal\commerce_price\Price $price */
      $price = $pricing_options['price'];
      $currency = $price->getCurrencyCode();
      $member_price = isset($pricing_options['member_price']) ? $pricing_options['member_price'] : $price;
      $pricing_options['price'] = $this->currencyFormatter->format($price->getNumber(), $currency);
      $pricing_options['member_price'] = $this->currencyFormatter->format($member_price->getNumber(), $currency);
    }

    return [
      '#theme' => 'vscpa_commerce_current_price_self_study',
      '#pricing_options' => $pricing_options,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Prepares a render array for a current session price.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return array
   *   A render array for the current event price.
   */
  protected function prepareSessionPrice(PurchasableEntityInterface $purchasable_entity, $quantity, Context $context) {
    // @todo checks if this function is actually needed.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only allow this formatter to be used on the base Price field.
    return $field_definition->getTargetEntityTypeId() === 'commerce_product_variation' && $field_definition->getName() === 'price';
  }

}
