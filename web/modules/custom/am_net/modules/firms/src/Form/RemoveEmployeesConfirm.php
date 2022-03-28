<?php

namespace Drupal\am_net_firms\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\am_net_firms\EmployeeManagementTool;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for remove a given Employee of a Firm.
 */
class RemoveEmployeesConfirm extends ConfirmFormBase {

  /**
   * The Employee Management Tool service.
   *
   * @var \Drupal\am_net_firms\EmployeeManagementTool
   */
  protected $employeeManagementTool;

  /**
   * The Firm.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $firm;

  /**
   * The Employee.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $employee;

  /**
   * The current destination.
   *
   * @var string
   */
  protected $destination;

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\am_net_firms\EmployeeManagementTool $employee_management_tool
   *   The Employee Management Tool service.
   */
  public function __construct(EmployeeManagementTool $employee_management_tool) {
    $this->employeeManagementTool = $employee_management_tool;
    $route_match = \Drupal::routeMatch();
    $this->employee = $route_match->getParameter('employee');
    $this->firm = $route_match->getParameter('firm');
    $this->destination = \Drupal::destination()->get();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_firms.employee_management_tool')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_firms.employee_management_tool.manage_employees.remove_employee_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the following employee(s) from the firm/company?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('am_net_firms.employee_management_tool.manage_employees', ['user' => $this->employee->id(), 'firm' => $this->firm->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove Employee');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->firm || !$this->employee) {
      return $this->redirect($this->destination);
    }
    $current_user_id = $this->currentUser()->id();
    // Prevent current user of change their linked firm.
    if ($this->employee->id() == $current_user_id) {
      // Output a notice that the current user can not change their linked firm.
      $message = $this->t('You(%name) cannot change your linked Firm from here.', ['%name' => $this->employee->label()]);
      drupal_set_message($message, 'warning');
      return $this->redirect($this->destination);
    }
    // Header.
    $title = $this->t('Manage Employees/Pay Dues');
    $title .= ' <small>' . $this->t('Confirm Removal of Employee') . '</small>';
    $description = '<br><p>' . $this->t('Are you sure you want to remove the following employee(s) from the firm/company?') . '<p>';
    $form['header'] = [
      '#type' => 'item',
      '#markup' => '<div class="page-header"><h1 class="accent-left purple">' . $title . '</h1></div>' . $description,
    ];
    $root = NULL;
    $names = [];
    $form['accounts'] = ['#tree' => TRUE];
    $uid = $this->employee->id();
    $names[$uid] = [
      '#markup' => $this->employeeManagementTool->getUserSummary($this->employee),
      '#wrapper_attributes' => [
        'class' => [
          'list-group-item',
        ],
      ],
    ];
    $form['accounts'][$uid] = [
      '#type' => 'hidden',
      '#value' => $uid,
    ];
    $form['account']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
      '#list_type' => 'ul',
      '#attributes' => [
        'class' => [
          'list-group',
        ],
      ],
    ];
    $form['operation'] = ['#type' => 'hidden', '#value' => 'cancel'];
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();
    if ($this->employee->id() != $current_user_id) {
      // UnLink a given user to a given Firm.
      $this->employeeManagementTool->unLinkUserToFirm($this->firm, $this->employee);
      // Show confirm message.
      $message = $this->t('The employee (@employee) has been unlinked from the firm/company.', ['@employee' => $this->employee->getEmail()]);
      drupal_set_message($message);
      $form_state->setRedirect('am_net_firms.employee_management_tool.manage_employees', ['user' => $current_user_id, 'firm' => $this->firm->id()]);
    }
  }

}
