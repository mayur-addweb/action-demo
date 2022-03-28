<?php

namespace Drupal\am_net_firms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\am_net_firms\EmployeeManagementTool;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Firm's Modal Registration' widget.
 *
 * @FieldWidget(
 *   id = "am_net_firms_modal_registration",
 *   module = "am_net_firms",
 *   label = @Translation("Firm's Modal Registration Widget"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ModalRegistrationWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Check if current user is firm admin.
   *
   * @var bool
   */
  protected $isFirmAdmin = NULL;

  /**
   * The membership checker.
   *
   * @var \Drupal\am_net_firms\EmployeeManagementTool
   */
  protected $employeeManagementTool;

  /**
   * Constructs a new ProductVariationWidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\am_net_firms\EmployeeManagementTool $employee_management_tool
   *   The Employee Management Tool service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EmployeeManagementTool $employee_management_tool, AccountProxyInterface $account) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->employeeManagementTool = $employee_management_tool;
    $this->account = $account;
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
      $configuration['third_party_settings'],
      $container->get('am_net_firms.employee_management_tool'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isFirmAdmin() {
    if (is_null($this->isFirmAdmin)) {
      $this->isFirmAdmin = $this->employeeManagementTool->isFirmAdmin($this->account);
    }
    // Check Role.
    return $this->isFirmAdmin;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Ensure that the user is Authenticated.
    if ($this->account->isAnonymous()) {
      return [];
    }
    // Ensure that the widget only works for Firm's Admins.
    if (!$this->isFirmAdmin()) {
      $element['target_id'] = [
        '#type' => 'hidden',
        '#value' => $this->account->id(),
      ];
      return $element;
    }
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $form_state->get('product');
    if (!$product || (!($product instanceof ProductInterface))) {
      return [];
    }
    $variations = $this->loadEnabledVariations($product);
    if (empty($variations)) {
      return [];
    }
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $entity */
    // Order item Entity.
    $entity = $items->getEntity();
    // Ensure that the widget only works over specific entity bundles.
    $supported_bundles = [
      'event_registration',
      'session_registration',
      'self_study_registration',
    ];
    if (!in_array($entity->bundle(), $supported_bundles)) {
      return [];
    }
    $count_variations = count($variations);
    $selected_variation = NULL;
    $selected_variation_id = NULL;
    if ($count_variations > 0) {
      $selected_variation = current($variations);
      $selected_variation_id = $selected_variation->id();
    }
    $element['registrations'] = [
      '#type' => 'modal_registration',
      '#is_firm_admin' => $this->isFirmAdmin(),
      '#employees' => $this->employeeManagementTool->getAllFirmEmployees($this->account, $selected_variation),
      '#curren_user_id' => $this->account->id(),
      '#required' => TRUE,
      '#sessions' => NULL,
      '#product' => $product,
      '#variations' => $variations,
      '#selected_variation' => $selected_variation,
      '#selected_variation_id' => $selected_variation_id,
      '#show_variation_selector' => ($count_variations > 1) ? 1 : 0,
    ];
    return $element;
  }

  /**
   * Validate the color text field.
   */
  public static function validate(array $element, FormStateInterface $form_state) {
    // @todo. Validate.
  }

  /**
   * Ajax Refresh callback.
   *
   * This callback will occur *after* the form has been rebuilt by buildForm().
   * Since that's the case, the form should contain the right values for
   * instrument_dropdown.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The portion of the render structure that will replace the
   *   employee-selector-widget form element.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Ensure that the widget is used in a order item context.
    if ($field_definition->getTargetEntityTypeId() != 'commerce_order_item') {
      return FALSE;
    }
    // This Field Widget was designed only for the field user.
    if ($field_definition->getName() != 'field_user') {
      return FALSE;
    }
    // In this point the 'Modal registration' Widget is applicable.
    return TRUE;
  }

  /**
   * Gets the enabled variations for the product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   An array of variations.
   */
  protected function loadEnabledVariations(ProductInterface $product) {
    $enabled_variations = [];
    $variations = $product->getVariations();
    foreach ($variations as $key => $variation) {
      if (!$variation->isPublished()) {
        continue;
      }
      $enabled_variations[$key] = $variation;
    }
    return $variations;
  }

}
