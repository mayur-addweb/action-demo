<?php

namespace Drupal\am_net_firms\Plugin\Field\FieldWidget;

use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Field\WidgetBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\am_net_firms\EmployeeManagementTool;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'Employee Selector' widget.
 *
 * @FieldWidget(
 *   id = "am_net_firms_employee_selector",
 *   module = "am_net_firms",
 *   label = @Translation("Firm's Employee Selector widget"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EmployeeSelectorWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Ensure that the user is Authenticated.
    if ($this->account->isAnonymous()) {
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
    // Ensure that the widget only works for Firm's Admins.
    if (!$this->employeeManagementTool->isFirmAdmin($this->account)) {
      $element['target_id'] = [
        '#type' => 'hidden',
        '#value' => $this->account->id(),
      ];
      return $element;
    }
    // Build the employee selector form.
    $wrapper_id = Html::getUniqueId('commerce-product-add-to-cart-form');
    $form += [
      '#wrapper_id' => $wrapper_id,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    // Build a parents array for this element's values in the form.
    $parents = array_merge($element['#field_parents'], [$items->getName(), $delta]);
    // Assign a unique identifier to each Form element widget.
    // Since $parents can get quite long, sha1() ensures that every id has
    // a consistent and relatively short length while maintaining uniqueness.
    // Get the default value.
    $wrapper_id = sha1(implode('-', $parents));
    $prefix = 'employee-selector-widget-';
    $wrapper = $prefix . $wrapper_id;
    // Container.
    $key = 'employee_selector_widget';
    $element[$key] = [
      '#type' => 'item',
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper . '" class="employee-selector-widget">',
      '#suffix' => '</div>',
      '#employee_selector_id' => $wrapper_id,
      '#employee_selector_root' => TRUE,
      '#markup' => '<div class="header"><span class="icon-user"></span>' . t("Register Other People in Your Firm") . '</div>',
      '#attributes' => [
        'class' => [$key],
      ],
      '#element_validate' => [
        [static::class, 'validate'],
      ],
    ];
    // Resolve the selected firm Id.
    $selected_firm_id = '_none_';
    $selected_employee_id = '_none_';
    $user_input = (array) NestedArray::getValue($form_state->getUserInput(), $parents);
    if (!empty($user_input)) {
      $selected_firm_id = isset($user_input[$key]['firm']) ? $user_input[$key]['firm'] : $selected_firm_id;
      $selected_employee_id = isset($user_input[$key]['employee']) ? $user_input[$key]['employee'] : $selected_firm_id;
    }
    // Get firm.
    $firm_admin = User::load($this->account->id());
    $firm_list = $this->employeeManagementTool->getFirmsList($firm_admin, $with_users_linked = TRUE);
    $ajax_callback = [get_class($this), 'ajaxRefresh'];
    // Add firm selector.
    if (!empty($firm_list)) {
      $element[$key]['firm'] = [
        '#type' => 'select',
        '#title' => t('Select Firm'),
        '#options' => $firm_list,
        '#required' => TRUE,
        '#empty_option' => '- Select a Firm -',
        '#ajax' => [
          'callback' => $ajax_callback,
          'wrapper' => $form['#wrapper_id'],
        ],
        '#attributes' => [
          'oninvalid' => "this.setCustomValidity('Please select a Firm')",
          'oninput' => "this.setCustomValidity('')",
        ],
      ];
    }
    // Add employee selector.
    if (!empty($selected_firm_id) && is_numeric($selected_firm_id)) {
      $selected_firm = Term::load($selected_firm_id);
      if ($selected_firm) {
        /** @var \Drupal\commerce\PurchasableEntityInterface $purchased_entity */
        $purchased_entity = $entity->getPurchasedEntity();
        // Show the Employee Selector.
        $firm_list = $this->employeeManagementTool->getFirmEmployeesOptions($selected_firm, $purchased_entity);
        if ($firm_list) {
          $element[$key]['employee'] = [
            '#type' => 'select',
            '#title' => t('Select Employee'),
            '#options' => $firm_list,
            '#required' => TRUE,
            '#empty_option' => '- Select a Person -',
            '#attributes' => [
              'oninvalid' => "this.setCustomValidity('Please select an employee from your firm.')",
              'oninput' => "this.setCustomValidity('')",
            ],
          ];
        }
        else {
          $element[$key]['no_value_message'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => t('This firm has no Linked employees yet!.'),
            '#attributes' => [
              'class' => ['no-value-message'],
            ],
          ];
        }
      }
    }
    return $element;
  }

  /**
   * Validate the color text field.
   */
  public static function validate(array $element, FormStateInterface $form_state) {
    $employee_input = ['field_user', 0, 'employee_selector_widget', 'employee'];
    // Set field user dynamically.
    $employee_id = $form_state->getValue($employee_input);
    $employee_was_selected = !empty($employee_id) && is_numeric($employee_id);
    if (!$employee_was_selected) {
      $form_state->setErrorByName('field_user[0][employee_selector_widget][employee]', 'Please select an employee from your firm.');
    }
    $form_state->setValue(['field_user', 0, 'target_id'], $employee_id);
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
   * Selects a Firm from user input.
   *
   * If there's no user input (form viewed for the first time), the default
   * firm is returned.
   *
   * @param \Drupal\taxonomy\TermInterface[] $firms
   *   An array of Firms.
   * @param array $user_input
   *   The user input.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The selected firm.
   */
  protected function selectFirmFromUserInput(array $firms, array $user_input) {
    $current_firm = NULL;
    if (!empty($user_input['firm']) && $firms[$user_input['firm']]) {
      $current_firm = $firms[$user_input['firm']];
    }
    return $current_firm;
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
    // In this point the user of the Widget is applicable.
    return TRUE;
  }

}
