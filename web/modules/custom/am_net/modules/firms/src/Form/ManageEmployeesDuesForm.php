<?php

namespace Drupal\am_net_firms\Form;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\am_net_membership\Event\MembershipApplicationEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\am_net_membership\Event\MembershipEvents;
use Drupal\am_net_firms\EmployeeManagementTool;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormBase;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Implements the Manage Employees Dues Form.
 */
class ManageEmployeesDuesForm extends FormBase {

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
   * The Firm admin.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $firmAdmin;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\am_net_firms\EmployeeManagementTool $employee_management_tool
   *   The Employee Management Tool service.
   * @param \Drupal\taxonomy\TermInterface $firm
   *   The Firm.
   * @param \Drupal\user\UserInterface $firm_admin
   *   The Firm admin.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EmployeeManagementTool $employee_management_tool, TermInterface $firm, UserInterface $firm_admin, EventDispatcherInterface $event_dispatcher) {
    $this->employeeManagementTool = $employee_management_tool;
    $this->firm = $firm;
    $this->firmAdmin = $firm_admin;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $route_match = \Drupal::routeMatch();
    return new static(
      $container->get('am_net_firms.employee_management_tool'),
      $route_match->getParameter('firm'),
      $route_match->getParameter('user'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_firms.employee_management_tool.manage_dues';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#id'] = 'manage-employees-dues-form';
    $form['#attributes'] = ['class' => ['manage-employees-dues-form']];
    // Title.
    $firm_label = $this->employeeManagementTool->getFirmTitle($this->firm);
    $title = $this->t('Pay Dues');
    $description = $firm_label;
    $form['title'] = [
      '#type' => 'item',
      '#markup' => '<div class="page-header"><h3>' . $title . ' <small><i>' . $description . '</i></small></h3></div>',
    ];
    $manage_my_firm_url = Url::fromRoute('am_net_firms.employee_management_tool.manage_employees', ['user' => $this->firmAdmin->id(), 'firm' => $this->firm->id()], [])->toString();
    $alert = '<span class="glyphicon glyphicon-info-sign"></span> ' . t('Have an employee missing, or need to remove someone who no longer works here? You can add or remove employees from the <a href="@manage_my_firm_url">Manage My Firm</a> page.', ['@manage_my_firm_url' => $manage_my_firm_url]);
    $form['alert'] = [
      '#type' => 'item',
      '#markup' => "<div class='alert alert-warning' role='alert'>$alert</div>",
    ];
    // Renewal Information message.
    $content = \Drupal::state()->get(EMTSettingForm::RENEWAL_INFORMATION_MESSAGE);
    if (!empty($content)) {
      $form['renewal_information_message'] = [
        '#type' => 'item',
        '#markup' => $content,
      ];
    }
    // Employees with Dues Balances.
    $employees = $this->employeeManagementTool->getEmployeesWithDuesBalances($this->firm);
    /* @var \Drupal\am_net_membership\MembershipCheckerInterface $membership_checker */
    $membership_checker = $this->employeeManagementTool->getMembershipChecker();
    $employee_selected = \Drupal::request()->query->get('employee');
    // Initialize an empty array.
    $options = [];
    $default_value = [];
    if (!empty($employees)) {
      $pac_default_contribution = 20;
      $educational_default_contribution = 20;
      /* @var \Drupal\user\UserInterface $employee */
      // Next, loop through the $employees array.
      foreach ($employees as $employee) {
        // Format Values.
        $delta = $employee->id();
        $name = $employee->getEmail();
        $firstname = $employee->field_givenname->value ?? NULL;
        $lastname = $employee->field_familyname->value ?? NULL;
        $full_name = $firstname . " " . $lastname;
        $default_value[$delta] = ($delta == $employee_selected) ? 1 : 0;
        // Membership price.
        $membership_price = $membership_checker->getMembershipPrice($employee);
        $price = number_format(floatval($membership_price), 2, '.', ',');
        $dues_balance = $price;
        // Pac Contribution.
        $price = number_format(floatval($pac_default_contribution), 2, '.', ',');
        $pac_contribution = [
          'data' => [
            '#type' => 'number',
            '#title' => '$',
            '#name' => 'pac_contribution[' . $delta . ']',
            '#value' => $price,
            '#min' => 0,
            '#attributes' => [
              'data-id' => $delta,
              'class' => [
                'money-contribution',
                "pac-balance-{$delta}",
              ],
            ],
          ],
        ];
        // Educational Contribution.
        $price = number_format(floatval($educational_default_contribution), 2, '.', ',');
        $educational_foundation_contribution = [
          'data' => [
            '#type' => 'number',
            '#title' => '$',
            '#name' => 'educational_foundation_contribution[' . $delta . ']',
            '#value' => $price,
            '#min' => 0,
            '#attributes' => [
              'data-id' => $delta,
              'class' => [
                'money-contribution',
                "educational-balance-{$delta}",
              ],
            ],
          ],
        ];
        $total = ($dues_balance + $pac_default_contribution + $educational_default_contribution);
        $total = '$' . number_format(floatval($total), 2, '.', ',');
        $total_element = [
          'data' => [
            '#type' => 'markup',
            '#markup' => "<div class='hide dues-balance dues-balance-{$delta}' id='dues-balance-{$delta}'>{$dues_balance}</div><div class='total-summary total-{$delta}' id='total-{$delta}'>{$total}</div>",
          ],
        ];
        // Set Values.
        $options[$delta] = [
          'name' => $name,
          'field_givenname' => $full_name,
          'dues_balance' => '$' . $dues_balance,
          'pac_contribution' => $pac_contribution,
          'educational_foundation_contribution' => $educational_foundation_contribution,
          'total' => $total_element,
        ];
      }
    }
    $header = [
      'name' => t('Name'),
      'field_givenname' => t('Full Name'),
      'dues_balance' => t('Dues Balance'),
      'pac_contribution' => t('VSCPA PAC Contribution'),
      'educational_foundation_contribution' => t('VSCPA Educational Foundation Contribution'),
      'total' => t('Total'),
    ];
    $form['employee_dues_list'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No employees with Dues Balance found!.'),
      '#attributes' => [
        'class' => ['emt-firm-dues'],
      ],
      '#default_value' => $default_value,
    ];
    if (!empty($employees)) {
      // Attach Js behaviors: Manage Employees Dues.
      $form['#attached']['library'][] = 'am_net_firms/manage_employees_dues';
      // Add Form Actions.
      $form['actions']['#type'] = 'actions';
      // Clear Donation button.
      $form['actions']['clear_donations'] = [
        '#markup' => '<a href="#" class="btn btn-default clear-donations" id="clear-donations"> Clear Donations</a>',
      ];
      // Pay dues button.
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('<span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> Pay Dues'),
        '#button_type' => 'primary',
        '#weight' => 10,
        '#attributes' => [
          'class' => ['btn-sm'],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    $employees = $this->getSelectedEmployees($values);
    if (empty($employees)) {
      $form_state->setError($form['employee_dues_list'], t('Please select at least one employee.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    $employees = $this->getSelectedEmployees($values);
    foreach ($employees as $employee_id => $item) {
      $donations = [];
      $pac = new Price($item['pac'], 'USD');
      if (!$pac->isZero()) {
        $donations[] = [
          'amount' => $pac,
          'anonymous' => FALSE,
          'destination' => 'PAC',
          'source' => 'P',
        ];
      }
      $ef = new Price($item['edu'], 'USD');
      if (!$ef->isZero()) {
        $donations[] = [
          'amount' => $ef,
          'anonymous' => FALSE,
          'destination' => 'EF',
          'source' => 'P',
        ];
      }
      $employee = User::load($employee_id);
      $event = new MembershipApplicationEvent($employee, $donations, $this->firmAdmin);
      $this->eventDispatcher->dispatch(MembershipEvents::SUBMIT_APPLICATION, $event);
    }
    // Redirect to cart page to complete the Memberships purchase
    // and the donations.
    $form_state->setRedirectUrl(Url::fromRoute('commerce_cart.page'));
  }

  /**
   * Get Selected Employees from user input.
   *
   * @param array $values
   *   The array of user input values.
   *
   * @return array
   *   The formatted submitted values.
   */
  public function getSelectedEmployees(array $values = []) {
    $employee_dues_list = isset($values['employee_dues_list']) ? $values['employee_dues_list'] : [];
    if (empty($employee_dues_list)) {
      return [];
    }
    $employees = [];
    $key_edu_donation = 'educational_foundation_contribution';
    $key_pac_donation = 'pac_contribution';
    foreach ($employee_dues_list as $key => $employee_id) {
      if ($key == $employee_id) {
        $pac = isset($values[$key_pac_donation][$employee_id]) ? $values[$key_pac_donation][$employee_id] : 0;
        $edu = isset($values[$key_edu_donation][$employee_id]) ? $values[$key_edu_donation][$employee_id] : 0;
        $employees[$employee_id] = [
          'pac' => $pac,
          'edu' => $edu,
        ];
      }
    }
    return $employees;
  }

}
