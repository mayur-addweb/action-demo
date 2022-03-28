<?php

namespace Drupal\am_net_donations;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The donation manager.
 */
class DonationManager {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productStorage;

  /**
   * The am_net_membership configuration settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Constructs a new DonationManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->productStorage = $entity_type_manager->getStorage('commerce_product');
    $this->settings = $config_factory->get('am_net_donations.settings');
  }

  /**
   * Gets the default membership donation product for the given type.
   *
   * @param string $type
   *   'EF' (for Education Fund), or 'PAC' (for Political Action Committee).
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The default product for the given type
   */
  public function getDefaultMembershipDonationProduct($type = 'EF') {
    $suffix = strtolower($type);
    if ($default_membership_donation_product_uuid = $this->settings->get('default_membership_donation_product_' . $suffix)) {
      $product = $this->productStorage->loadByProperties([
        'uuid' => $default_membership_donation_product_uuid,
      ]);
      return current($product);
    }
  }

  /**
   * Gets the default donation product for the given type.
   *
   * @param string $type
   *   'EF' (for Education Fund), or 'PAC' (for Political Action Committee).
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The default product for the given type
   */
  public function getDefaultDonationProduct($type = 'EF') {
    $suffix = strtolower($type);
    if ($default_donation_product_uuid = $this->settings->get('default_donation_product_' . $suffix)) {
      $product = $this->productStorage->loadByProperties([
        'uuid' => $default_donation_product_uuid,
      ]);
      return current($product);
    }
  }

}
