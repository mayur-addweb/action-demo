<?php

namespace Drupal\am_net_firms\MembershipQualification;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormBase;
use Drupal\am_net_firms\EMTInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define Base Step Class.
 *
 * @package Drupal\am_net_firms\MembershipQualification\Step
 */
abstract class StepsFormBase extends FormBase {

  /**
   * The Current Step Id.
   *
   * @var string
   */
  protected $stepId = NULL;

  /**
   * Employee management tool instance.
   *
   * @var \Drupal\am_net_firms\EMTInterface|null
   */
  protected $employeeManagementTool = NULL;

  /**
   * Defines an account which represents the employee.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected $employee = NULL;

  /**
   * Defines an account which represents the Firm Admin.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected $firmAdmin = NULL;

  /**
   * Defines an taxonomy which represents the Firm.
   *
   * @var \Drupal\taxonomy\TermInterface|null
   */
  protected $firm = NULL;

  /**
   * Values of element.
   *
   * @var array
   */
  protected $values = [];

  /**
   * Constructs a Membership Qualification Form object.
   *
   * @param \Drupal\am_net_firms\EMTInterface $employee_management_tool
   *   The Employee management tool instance.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The Current Route Match instance.
   */
  public function __construct(EMTInterface $employee_management_tool = NULL, CurrentRouteMatch $route_match = NULL) {
    $this->employee = $route_match->getParameter('employee');
    $this->firmAdmin = $route_match->getParameter('user');
    $this->firm = $route_match->getParameter('firm');
    $this->employeeManagementTool = $employee_management_tool;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_firms.employee_management_tool'),
      $container->get('current_route_match')
    );
  }

  /**
   * Get Field Value.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return mixed
   *   The value of the field.
   */
  public function getFieldValue($field_name = '') {
    if (empty($field_name)) {
      return NULL;
    }
    $values = $this->getValues();
    $value = isset($values[$field_name]) ? $values[$field_name] : NULL;
    if (is_null($value)) {
      // Try to search the value on the employee info.
      $value = $this->getUserFieldValue($field_name);
    }
    return $value;
  }

  /**
   * Get user's field Label.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The field title.
   */
  public function getUserFieldValue($field_name = '') {
    if (!$this->employee->hasField($field_name)) {
      return NULL;
    }
    return $this->employeeManagementTool->getMembershipChecker()->getFieldValue($this->employee, $field_name);
  }

  /**
   * Get user field Description.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The field Description.
   */
  public function getFieldDescription($field_name = '') {
    if (!$this->employee->hasField($field_name)) {
      return '';
    }
    $field_definition = $this->employee->get($field_name)->getFieldDefinition();
    return $field_definition->getDescription();
  }

  /**
   * Get user field Label.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The field title.
   */
  public function getFieldLabel($field_name = '') {
    if (!$this->employee->hasField($field_name)) {
      return '';
    }
    $field_definition = $this->employee->get($field_name)->getFieldDefinition();
    return $field_definition->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $values = []) {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsValidators() {
    return [];
  }

  /**
   * Add Text Field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $class
   *   The field class.
   *
   * @return array
   *   A text-type-field Render array.
   */
  public function addTextField($field_name = '', $class = '') {
    return [
      '#type' => 'textfield',
      '#title' => $this->getFieldLabel($field_name),
      '#default_value' => $this->getFieldValue($field_name),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->getFieldDescription($field_name),
      '#prefix' => "<div class='$class'>",
      '#suffix' => "</div>",
    ];
  }

  /**
   * Add Date Field.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   A Date-type-field Render array.
   */
  public function addDateField($field_name = '') {
    $default_value = $this->getFieldValue($field_name);
    if (!empty($default_value)) {
      // Ensure date format.
      $date = date_create($default_value);
      $default_value = date_format($date, 'Y-m-d');
    }
    if (empty($default_value) || (strpos($default_value, '1800') !== FALSE)) {
      $default_value = NULL;
    }
    $element = [
      '#type' => 'date',
      '#title' => $this->getFieldLabel($field_name),
      '#default_value' => $default_value,
      '#description' => $this->getFieldDescription($field_name),
    ];
    return $element;
  }

  /**
   * Add Radios Field.
   *
   * @param string $field_name
   *   The field name.
   * @param array $options
   *   The array of options.
   * @param string $class
   *   The field class.
   *
   * @return array
   *   A Radios-type-field Render array.
   */
  public function addRadiosField($field_name = '', array $options = [], $class = '') {
    $class = empty($class) ? 'radios-field' : $class;
    return [
      '#type' => 'radios',
      '#title' => $this->getFieldLabel($field_name),
      '#default_value' => $this->getFieldValue($field_name),
      '#description' => $this->getFieldDescription($field_name),
      '#options' => $options,
      '#attributes' => [
        'class' => [$class],
      ],
    ];
  }

  /**
   * Add Select Field.
   *
   * @param string $field_name
   *   The field name.
   * @param array $options
   *   The array of options.
   * @param string $class
   *   The field class.
   *
   * @return array
   *   A Select-type-field Render array.
   */
  public function addSelectField($field_name = '', array $options = [], $class = '') {
    $class = empty($class) ? 'select-field' : $class;
    return [
      '#type' => 'select',
      '#title' => $this->getFieldLabel($field_name),
      '#default_value' => $this->getFieldValue($field_name),
      '#description' => $this->getFieldDescription($field_name),
      '#options' => $options,
      '#attributes' => [
        'class' => [$class],
      ],
      '#prefix' => "<div class='$class'>",
      '#suffix' => "</div>",
    ];
  }

  /**
   * Add Checkboxes Field.
   *
   * @param string $field_name
   *   The field name.
   * @param bool $entity_reference
   *   The entity reference flag.
   * @param string $vid
   *   The referenced VID.
   * @param string $class
   *   The field class.
   *
   * @return array
   *   A Checkboxes-type-field Render array.
   */
  public function addCheckboxesField($field_name = '', $entity_reference = TRUE, $vid = 'gender', $class = '') {
    if ($entity_reference) {
      $options = $this->getEntityReferenceOptionsAllowedValues($vid);
    }
    else {
      $options = $this->getFieldOptionsAllowedValues($field_name);
    }
    $class = empty($class) ? 'checkboxes-field' : $class;
    return [
      '#type' => 'checkboxes',
      '#title' => $this->getFieldLabel($field_name),
      '#default_value' => $this->getFieldValue($field_name),
      '#description' => $this->getFieldDescription($field_name),
      '#options' => $options,
      '#attributes' => [
        'class' => [$class],
      ],
    ];
  }

  /**
   * Get Entity Reference options allowed values.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $type
   *   The referenced type.
   * @param array $conditions
   *   The conditions array.
   *
   * @return array
   *   array The array of allowed values.
   */
  public function getEntityReferenceOptionsAllowedValues($entity_type = '', $type = '', array $conditions = []) {
    if (empty($type)) {
      return [];
    }
    $options = [];
    $entities = [];
    if ($entity_type == 'taxonomy_term') {
      $entities = \Drupal::entityTypeManager()->getStorage($entity_type)->loadTree($type, $parent = 0, $max_depth = NULL, $load_entities = TRUE);
    }
    elseif ($entity_type == 'node') {
      $query = \Drupal::entityQuery('node')->condition('status', 1)->condition('type', $type);
      if (!empty($conditions)) {
        foreach ($conditions as $field_name => $field_value) {
          $query->condition($field_name, $field_value);
        }
      }
      $ids = $query->execute();
      $entities = Node::loadMultiple($ids);
    }
    foreach ($entities as $entity) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $options[$entity->id()] = $entity->label();
    }
    return $options;
  }

  /**
   * View result to options allowed values.
   *
   * @param array $rows
   *   An array containing an object for each view item.
   *
   * @return array
   *   array The array of allowed values.
   */
  public function viewResultToOptions(array $rows = []) {
    if (empty($rows)) {
      return [];
    }
    $options = [
      '_none' => '- None -',
    ];
    foreach ($rows as $row) {
      $options[$row->nid] = $row->node_field_data_title;
    }
    return $options;
  }

  /**
   * Get user field options allowed values.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   array The array of allowed values.
   */
  public function getFieldOptionsAllowedValues($field_name = '') {
    if (!$this->employee->hasField($field_name)) {
      return [];
    }
    $field_definition = $this->employee->get($field_name)->getFieldDefinition();
    $field_storage_definition = $field_definition->getFieldStorageDefinition();
    return options_allowed_values($field_storage_definition);
  }

}
