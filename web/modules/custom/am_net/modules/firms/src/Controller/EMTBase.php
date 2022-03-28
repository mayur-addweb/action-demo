<?php

namespace Drupal\am_net_firms\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\am_net_membership\MembershipCheckerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\am_net_firms\EmployeeManagementTool;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Url;

/**
 * Employee Management Tool Base page controller.
 */
class EMTBase extends ControllerBase {

  /**
   * The membership checker service.
   *
   * @var \Drupal\am_net_membership\MembershipCheckerInterface
   */
  protected $membershipChecker;

  /**
   * The employee management tool service.
   *
   * @var \Drupal\am_net_firms\EmployeeManagementTool
   */
  protected $employeeManagementTool;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new Employee Management Tool Base object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type definition.
   * @param \Drupal\am_net_firms\EmployeeManagementTool $employee_management_tool
   *   The employee management tool service.
   * @param \Drupal\am_net_membership\MembershipCheckerInterface $membership_checker
   *   The membership checker service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EmployeeManagementTool $employee_management_tool, MembershipCheckerInterface $membership_checker) {
    $this->entityTypeManager = $entity_type_manager;
    $this->employeeManagementTool = $employee_management_tool;
    $this->membershipChecker = $membership_checker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('am_net_firms.employee_management_tool'),
      $container->get('am_net_membership.checker')
    );
  }

  /**
   * Checks access for employee management tool.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    return AccessResult::allowedIf($this->employeeManagementTool->firmAdministratorAccessCheck($account));
  }

  /**
   * Get Firm Title.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The user entity.
   *
   * @return string|null
   *   The Firm name, Otherwise null.
   */
  public function getFirmTitle(TermInterface $firm = NULL) {
    if (!$firm) {
      return NULL;
    }
    $firm_name = $firm->label();
    $address = $firm->get('field_address')->getValue();
    if (!empty($address)) {
      $address = current($address);
    }
    $locality = $address['locality'] ?? NULL;
    if (!empty($locality)) {
      $firm_name = $firm_name . ' — ' . $locality;
    }
    return $firm_name;
  }

  /**
   * Get Firm Description.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The user entity.
   *
   * @return string|null
   *   The Firm Description, Otherwise null.
   */
  public function getFirmDescription(TermInterface $firm = NULL) {
    if (!$firm) {
      return NULL;
    }
    $entity_type = 'taxonomy_term';
    $view_mode = 'firm_summary';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $pre_render = $view_builder->view($firm, $view_mode);
    return render($pre_render);
  }

  /**
   * Get Firm Action links.
   *
   * @param string $uid
   *   The user uid.
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The user entity.
   *
   * @return string|null
   *   The Firm Action links, Otherwise null.
   */
  public function getFirmActions($uid, TermInterface $firm = NULL) {
    if (!$firm) {
      return NULL;
    }
    $operations = [];
    // Manage Employee.
    $manage_employees_url = Url::fromRoute('am_net_firms.employee_management_tool.manage_employees', [
      'user' => $uid,
      'firm' => $firm->id(),
    ])->toString();
    $label = t('<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span> Manage Employees/Pay Dues');
    $operations[] = "<a href='$manage_employees_url' class='dropdown-item'>$label</a>";
    // Edit Firm Info.
    $edit_firm_info_url = Url::fromRoute('am_net_firms.employee_management_tool.edit_firm_info', [
      'user' => $uid,
      'firm' => $firm->id(),
    ])->toString();
    $label = t('<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> Modify Your Company Info');
    $operations[] = "<a href='$edit_firm_info_url' class='dropdown-item'>$label</a>";
    // Set the actions.
    $actions = implode('', $operations);
    $operations = "<div class='panel-actions operations dropdown pull-right'><a class='btn btn-sm btn-secondary dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' id='actions-uid-$uid'><span class='glyphicon glyphicon-cog' aria-hidden='true'></span>  Actions <span class='glyphicon glyphicon-menu-down' aria-hidden='true'> </a> <div class='dropdown-menu' aria-labelledby='actions-uid-$uid'><h6 class='dropdown-header'></h6>$actions</div></div>";
    return $operations;
  }

  /**
   * Build Firm Panel.
   *
   * @param string $firm_title
   *   The firm title.
   * @param string $firm_description
   *   The firm description.
   * @param string $firm_actions
   *   The firm actions.
   *
   * @return string
   *   The Firm Panel.
   */
  public function buildFirmPanel($firm_title, $firm_description, $firm_actions) {
    return "<div class='panel panel-primary no-padding firm-panel'><div class='panel-heading'><h3 class='panel-title'>{$firm_title}</h3> {$firm_actions}</div><div class='panel-body padding-10 panel-firm-body'>{$firm_description}</div></div>";
  }

  /**
   * Get Edit firm form.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   *
   * @return array
   *   The form render array.
   */
  public function getEditFirmForm(TermInterface $firm = NULL) {
    if (!$firm) {
      return [];
    }
    return \Drupal::formBuilder()
      ->getForm('Drupal\am_net_firms\Form\EditFirmInformationForm');
  }

  /**
   * Get Employee Summary Box - Action Buttons.
   *
   * @param string|null $member_status
   *   The firm_admin entity.
   * @param array $params
   *   The array of params.
   *
   * @return string|null
   *   The markup Action Buttons.
   */
  public function getEmployeeSummaryBoxActionButtons($member_status = NULL, array $params = []) {
    $action_buttons = NULL;
    if ($member_status == 'need_to_renew') {
      $label = 'Renew Now!';
      $route = 'am_net_firms.employee_management_tool.manage_dues';
      $url = Url::fromRoute($route, $params)->toString();
    }
    elseif ($member_status == 'non_members') {
      $label = 'Click here to Join!';
      $route = 'am_net_firms.employee_management_tool.manage_employees.membership_qualification';
      $url = Url::fromRoute($route, $params)->toString();
    }
    if (!empty($label)) {
      $action_buttons = "<div class='action-buttons'><a href='$url' class='btn-cta'> $label</a></div>";;
    }
    // Return Markup Html.
    return $action_buttons;
  }

  /**
   * Get Employee Summary Box - View Operations.
   *
   * @param string|null $member_status
   *   The user's member status.
   * @param array $params
   *   The array of params.
   *
   * @return string
   *   The markup Operations.
   */
  public function getEmployeeSummaryBoxViewOperations($member_status = NULL, array $params = []) {
    $uid = $params['employee'] ?? NULL;
    $firm_id = $params['firm'] ?? NULL;
    if (empty($uid) || empty($firm_id)) {
      return NULL;
    }
    $operations = [];
    // Add View/edit employee.
    $label = t('<span class="glyphicon glyphicon-user" aria-hidden="true"></span> View/edit employee');
    $url = Url::fromRoute('entity.user.canonical', ['user' => $uid])
      ->toString();
    $operations[] = "<a href='$url' class='dropdown-item'>$label</a>";
    // Member actions.
    if ($member_status == 'non_members') {
      $url = Url::fromRoute('am_net_firms.employee_management_tool.manage_employees.membership_qualification', $params)
        ->toString();
      $label = t('<span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> Join Now');
      $operations[] = "<a href='$url' class='dropdown-item'>$label</a>";
    }
    elseif ($member_status == 'need_to_renew') {
      // Pay Membership dues.
      $url = Url::fromRoute('am_net_firms.employee_management_tool.manage_dues', $params)
        ->toString();
      $label = "<span class='glyphicon glyphicon-shopping-cart' aria-hidden='true'></span> " . t('Pay Membership Dues');
      $operations[] = "<a href='$url' class='dropdown-item'>$label</a>";
    }
    // Remove employee operation.
    // Prevent current user of change their linked firm.
    if ($uid != $firm_id) {
      $label = "<span class='glyphicon glyphicon-trash' aria-hidden='true'></span> " . t('Remove employee');
      $url = Url::fromRoute('am_net_firms.employee_management_tool.manage_employees.remove_employee_confirm', $params)
        ->toString();
      $operations[] = "<a href='$url' class='dropdown-item'>$label</a>";
    }
    // Set the actions.
    $actions = implode('', $operations);
    // Return markup string.
    return "<div class='operations dropdown pull-right'><a class='btn btn-sm btn-secondary dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' id='actions-uid-$uid'><span class='glyphicon glyphicon-cog' aria-hidden='true'></span>  Actions <span class='glyphicon glyphicon-menu-down' aria-hidden='true'> </a> <div class='dropdown-menu' aria-labelledby='actions-uid-$uid'><h6 class='dropdown-header'></h6>$actions</div></div>";
  }

  /**
   * Get Employee View.
   *
   * @param \StdClass|null $employee
   *   The employee entity.
   * @param array $params
   *   The array of params.
   *
   * @return array
   *   The Employee View render array.
   */
  public function getEmployeeSummaryBox($employee = NULL, array $params = NULL) {
    if (!$employee) {
      return [];
    }
    // Get the firm ID.
    $firm_id = $params['firm'] ?? NULL;
    // Get the firm admin UID.
    $firm_admin_uid = $params['user'] ?? NULL;
    // Get the member status.
    $member_status = $params['member_status'] ?? NULL;
    // Get the UID.
    $uid = $employee->uid ?? NULL;
    // Get First name.
    $first_name = $employee->first_name ?? NULL;
    // Get Last name.
    $last_name = $employee->last_name ?? NULL;
    // Get the certificate number.
    $certificate_number = $employee->certificate_number ?? NULL;
    // Get Email.
    $email = $employee->mail ?? NULL;
    // Name suffix.
    $suffix_name = ($uid == $firm_admin_uid) ? t("<i class='badge badge-Info font-weight-400'>It's you!</i>") : '';
    // Set the name.
    $name = implode(' ', [$first_name, $last_name, $suffix_name]);
    // Action Buttons.
    $route_params = [
      'user' => $firm_admin_uid,
      'employee' => $uid,
      'firm' => $firm_id,
    ];
    // Action Buttons.
    $action_buttons = $this->getEmployeeSummaryBoxActionButtons($member_status, $route_params);
    // View Operations.
    $operations = $this->getEmployeeSummaryBoxViewOperations($member_status, $route_params);
    // Set the markup.
    $markup = "<div class='employee-row'>$operations<strong class='name'>$name $action_buttons</strong><br><small class='email'>$email</small><small class='hide'>$certificate_number</small></div>";
    // Return Render array.
    return [
      '#markup' => $markup,
      '#wrapper_attributes' => [
        'class' => [
          'list-group-item',
        ],
      ],
    ];
  }

  /**
   * Add employee listing details.
   *
   * @param array $listing
   *   The list of employees.
   * @param array $params
   *   The array of params.
   * @param array $labels
   *   The list texts used in the details element.
   *
   * @return array
   *   The details render array.
   */
  public function addEmployeeListingDetails(array $listing = [], array $params = [], array $labels = []) {
    $title = $labels['title'] ?? NULL;
    $target = $labels['target'] ?? NULL;
    $member_status = $params['member_status'] ?? NULL;
    $search_class = "employee-search employee-search-{$member_status}";
    $no_found_result = [];
    $search = [];
    $items = [];
    foreach ($listing as $uid => $employee) {
      $items[] = $this->getEmployeeSummaryBox($employee, $params);
    }
    if (empty($items)) {
      $no_found_result = [
        '#markup' => $this->t('<h5>There are no employees in this category!.</h5>'),
        '#allowed_tags' => ['h5'],
        '#wrapper_attributes' => [
          'class' => [
            'no-employees-found',
          ],
        ],
      ];
    }
    else {
      $search = [
        '#markup' => "<div class='{$search_class}'><label>Employee Search:<input type='search' placeholder='Name, Certification #, or Email Address' class='form-control input-small input-inline employee-search-input'><div class='description help-block'>Name, Certification #, or Email Address</div></label></div>",
        '#allowed_tags' => ['div', 'class', 'input', 'type', 'label'],
      ];
    }
    $details_title = $this->t('@title <p class="help-block">Click anywhere in this box to expand and manage employees.</p>', [
      '@title' => $title,
      '@target' => $target,
    ]);
    return [
      '#type' => 'details',
      '#title' => $details_title,
      '#prefix' => '<div class="employee-listing-element">',
      '#suffix' => '</div>',
      'search' => $search,
      'no_found_result' => $no_found_result,
      'employee_list' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#wrapper_attributes' => [
          'class' => [
            'item-list',
          ],
        ],
        '#attributes' => [
          'class' => [
            'list-group',
            'firm-employee-list',
          ],
        ],
        '#items' => $items,
      ],
    ];
  }

  /**
   * Get Firm's Employee list View - Grouped By Membership Status.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   *
   * @return array
   *   The Employee list View render array.
   */
  public function getFirmEmployeeListViewGroupedByMembershipStatus(TermInterface $firm = NULL) {
    if (!$firm) {
      return [];
    }
    // Firm Admin Access Check.
    $firm_admin = \Drupal::currentUser();
    if (!($this->employeeManagementTool->firmAdministratorAccessCheck($firm_admin))) {
      return [];
    }
    $params = [
      'user' => $firm_admin->id(),
      'firm' => $firm->id(),
    ];
    // Build the render array.
    $element = [];
    // Handle Members.
    $filter = ['M'];
    $employees = $this->employeeManagementTool->doQuerySearchEmployeesByFirm($firm->id(), $filter);
    $labels = [
      'title' => $this->t('Members in Good Standing'),
      'target' => $this->t('Members in good Standing'),
    ];
    $params['member_status'] = 'members';
    $element['members'] = $this->addEmployeeListingDetails($employees, $params, $labels);
    // Handle people that need to renew.
    $filter = ['L', 'T'];
    $employees = $this->employeeManagementTool->doQuerySearchEmployeesByFirm($firm->id(), $filter);
    $labels = [
      'title' => $this->t('Need to Renew'),
      'target' => $this->t('need to renew'),
    ];
    $params['member_status'] = 'need_to_renew';
    $element['need_to_renew'] = $this->addEmployeeListingDetails($employees, $params, $labels);
    // Handle Nonmembers.
    $filter = ['N'];
    $employees = $this->employeeManagementTool->doQuerySearchEmployeesByFirm($firm->id(), $filter);
    $labels = [
      'title' => $this->t('Nonmembers'),
      'target' => $this->t('Non-members'),
    ];
    $params['member_status'] = 'non_members';
    $element['non_members'] = $this->addEmployeeListingDetails($employees, $params, $labels);
    // Attach Library.
    $element['#attached']['library'][] = 'am_net_firms/emt_filters';
    // Return Render array.
    return $element;
  }

  /**
   * Get Firm's Employee list View.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   *
   * @return array
   *   The Employee list View render array.
   */
  public function getFirmEmployeeListView(TermInterface $firm = NULL) {
    if (!$firm) {
      return [];
    }
    // Firm Admin Access Check.
    $firm_admin = \Drupal::currentUser();
    if (!($this->employeeManagementTool->firmAdministratorAccessCheck($firm_admin))) {
      return [];
    }
    // Filter.
    $filter = \Drupal::request()->query->get('filter');
    if (empty($filter)) {
      $filter = 'all';
    }
    // Get Firm's Employee list.
    $employees = $this->employeeManagementTool->getFirmEmployeeList($firm, $limit = 5, $filter);
    if (empty($employees)) {
      return [];
    }
    // Get the employees array.
    $items = [];
    foreach ($employees as $uid => $employee) {
      $items[] = $this->getEmployeeView($employee, $firm_admin, $firm);
    }
    // Build the render array.
    $element = [];
    $element['#attached']['library'][] = 'am_net_firms/emt_filters';
    $filter_options = [
      'all' => $this->t('All'),
      'members' => $this->t('Members'),
      'nonmembers' => $this->t('Nonmembers'),
    ];
    $element['filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter By'),
      '#options' => $filter_options,
      '#default_value' => $filter,
      '#value' => $filter,
      '#attributes' => [
        'class' => [
          'filter-listing',
        ],
        'id' => 'filter-employee-list',
        'name' => 'filter-employee-list',
      ],
      '#prefix' => '<div class="filter-listing-element">',
      '#suffix' => '</div>',
    ];

    $element['employee_list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => [
        'class' => [
          'item-list',
        ],
      ],
      '#attributes' => [
        'class' => [
          'list-group',
        ],
      ],
      '#items' => $items,
    ];
    // Add Pager.
    $element['employee_list_pager'] = [
      '#type' => 'pager',
    ];
    // Return Render array.
    return $element;
  }

  /**
   * Get Employee View.
   *
   * @param \Drupal\user\UserInterface|null $employee
   *   The employee entity.
   * @param \Drupal\Core\Session\AccountProxyInterface|null $firm_admin
   *   The firm_admin entity.
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   *
   * @return array
   *   The Employee View render array.
   */
  public function getEmployeeView(UserInterface $employee = NULL, AccountProxyInterface $firm_admin = NULL, TermInterface $firm = NULL) {
    if (!$employee) {
      return [];
    }
    // Get First name.
    $first_name = $employee->get('field_givenname')->getString();
    // Get Last name.
    $last_name = $employee->get('field_familyname')->getString();
    // Name suffix.
    $suffix_name = ($employee->id() == $firm_admin->id()) ? t("<i class='badge badge-Info font-weight-400'>It's you!</i>") : '';
    // Set the name.
    $name = implode(' ', [$first_name, $last_name, $suffix_name]);
    // Set the Email.
    $email = $employee->getEmail();
    // Get the member status.
    $member_status_options = $employee->get('field_member_status')
      ->getSetting('allowed_values');
    $member_status_value = $employee->get('field_member_status')->getString();
    $badge_type = ($member_status_value == 'M') ? 'badge-success' : 'badge-warning';
    $member_status = isset($member_status_options[$member_status_value]) ? $member_status_options[$member_status_value] : '';
    // Membership status info.
    $membership_status_info = $this->employeeManagementTool->getMembershipStatusInfo($employee);
    // Membership status message.
    $membership_status_message = $this->getMembershipStatusAlert($membership_status_info, $employee);
    // Action Buttons.
    $action_buttons = $this->getMembershipActionButtons($employee, $firm_admin, $firm, $membership_status_info);
    // Get Employee View Operations.
    $operations = $this->getEmployeeViewOperations($employee, $firm_admin, $firm, $membership_status_info);
    // Set the markup.
    $markup = "<div class='employee-row'>$operations<strong class='name'>$name $action_buttons<br><small class='email'>$email</small></strong><br>$membership_status_message</div>";
    // Add modal dialog.
    $suffix = '';
    if ($membership_status_info['is_membership_application']) {
      $suffix = $this->getMembershipQualificationModal($employee);
    }
    // Return Render array.
    return [
      '#markup' => $markup . $suffix,
      '#wrapper_attributes' => [
        'class' => [
          'list-group-item',
        ],
      ],
    ];
  }

  /**
   * Get Employee View Operations.
   *
   * @param \Drupal\user\UserInterface|null $employee
   *   The employee entity.
   * @param \Drupal\Core\Session\AccountProxyInterface|null $firm_admin
   *   The firm_admin entity.
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   * @param array $membership_status_info
   *   The array with the membership status info.
   *
   * @return string
   *   The markup Operations.
   */
  public function getEmployeeViewOperations(UserInterface $employee = NULL, AccountProxyInterface $firm_admin = NULL, TermInterface $firm = NULL, array $membership_status_info = []) {
    if (!$employee || !$firm || !$firm_admin) {
      return '';
    }
    $params = [
      'user' => $firm_admin->id(),
      'employee' => $employee->id(),
      'firm' => $firm->id(),
    ];
    $uid = $employee->id();
    $operations = [];
    // Add View/edit employee.
    $label = t('<span class="glyphicon glyphicon-user" aria-hidden="true"></span> View/edit employee');
    $url = Url::fromRoute('entity.user.canonical', ['user' => $employee->id()])
      ->toString();
    $operations[] = "<a href='$url' class='dropdown-item'>$label</a>";
    // Member actions.
    if ($membership_status_info['is_membership_application']) {
      $url = Url::fromRoute('am_net_firms.employee_management_tool.manage_employees.membership_qualification', $params)
        ->toString();
      $label = t('<span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> Join Now');
      $operations[] = "<a href='$url' class='dropdown-item'>$label</a>";
      if ($membership_status_info['user_has_dues_defined']) {
        $url = Url::fromRoute('am_net_firms.employee_management_tool.manage_dues', $params)
          ->toString();
        // Pay Membership dues.
        $label = t('<span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> Pay Membership Dues');
        $operations[] = "<a href='$url' class='dropdown-item'>$label</a>";
      }
    }
    elseif ($membership_status_info['is_membership_renewal']) {
      // Pay Membership dues.
      $url = Url::fromRoute('am_net_firms.employee_management_tool.manage_dues', $params)
        ->toString();
      $label = "<span class='glyphicon glyphicon-shopping-cart' aria-hidden='true'></span> " . t('Pay Membership Dues');
      $operations[] = "<a href='$url' class='dropdown-item'>$label</a>";
    }
    // Remove employee operation.
    // Prevent current user of change their linked firm.
    if ($firm_admin->id() != $employee->id()) {
      $label = "<span class='glyphicon glyphicon-trash' aria-hidden='true'></span> " . t('Remove employee');
      $url = Url::fromRoute('am_net_firms.employee_management_tool.manage_employees.remove_employee_confirm', $params)
        ->toString();
      $operations[] = "<a href='$url' class='dropdown-item'>$label</a>";
    }
    // Set the actions.
    $actions = implode('', $operations);
    // Return markup string.
    return "<div class='operations dropdown pull-right'><a class='btn btn-sm btn-secondary dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' id='actions-uid-$uid'><span class='glyphicon glyphicon-cog' aria-hidden='true'></span>  Actions <span class='glyphicon glyphicon-menu-down' aria-hidden='true'> </a> <div class='dropdown-menu' aria-labelledby='actions-uid-$uid'><h6 class='dropdown-header'></h6>$actions</div></div>";
  }

  /**
   * Get Employee Action Buttons.
   *
   * @param \Drupal\user\UserInterface|null $employee
   *   The employee entity.
   * @param \Drupal\Core\Session\AccountProxyInterface|null $firm_admin
   *   The firm_admin entity.
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   * @param array $membership_status_info
   *   The array with the membership status info.
   *
   * @return string|null
   *   The markup Action Buttons.
   */
  public function getMembershipActionButtons(UserInterface $employee = NULL, AccountProxyInterface $firm_admin = NULL, TermInterface $firm = NULL, array $membership_status_info = []) {
    if (!$employee || !$firm || !$firm_admin) {
      return '';
    }
    $params = [
      'user' => $firm_admin->id(),
      'employee' => $employee->id(),
      'firm' => $firm->id(),
    ];
    $label = '';
    $route = NULL;
    if ($membership_status_info['is_membership_application']) {
      // Show user-friendly message to complete the employee’s membership
      // qualification form.
      $label = 'Click here to Join!';
      $route = 'am_net_firms.employee_management_tool.manage_employees.membership_qualification';
      $url = Url::fromRoute($route, $params)->toString();
    }
    elseif ($membership_status_info['is_membership_renewal']) {
      $label = 'Renew Now!';
      $route = 'am_net_firms.employee_management_tool.manage_dues';
      $url = Url::fromRoute($route, $params)->toString();
    }
    if (empty($label)) {
      return NULL;
    }
    // Return Markup Html.
    return "<div class='action-buttons'><a href='$url' class='btn-cta'> $label</a></div>";
  }

  /**
   * Get Membership Status Alert.
   *
   * @param array $membership_status_info
   *   The array with the membership status info.
   * @param \Drupal\user\UserInterface|null $employee
   *   The user entity.
   *
   * @return string
   *   The Alert Markup Html.
   */
  public function getMembershipStatusAlert(array $membership_status_info = [], UserInterface $employee = NULL) {
    if (empty($membership_status_info) || !$employee) {
      return '';
    }
    $messages = [];
    $label = '';
    if ($membership_status_info['is_membership_application']) {
      return '';
    }
    elseif ($membership_status_info['is_membership_renewal']) {
      return '';
    }
    else {
      // User is in good standing with an active license, no further
      // action is required.
      // Show user-friendly message.
      $alert_type = 'alert-success';
      $status_message = $membership_status_info['membership_status_message'];
      $status_message_arguments = $status_message ? $status_message->getArguments() : NULL;
      $date = $status_message_arguments['@date'] ?? NULL;
      if (!empty($date)) {
        $status_message = t('Your membership is good through @date.', ['@date' => $date]);
        $messages[] = $status_message;
      }
    }
    if (empty($messages)) {
      return '';
    }
    $message = implode(' ', $messages);
    if (!empty($label)) {
      $label = "<strong>$label</strong> ";
      $message = $label . $message;
    }
    // Return Markup Html.
    return "<div class='alert $alert_type' role='alert'>$message</div>";
  }

  /**
   * Get Membership Qualification Modal for a given employee.
   *
   * @param \Drupal\user\UserInterface|null $employee
   *   The user entity.
   *
   * @return string
   *   The Modal Markup Html.
   */
  public function getMembershipQualificationModal(UserInterface $employee = NULL) {
    if (!$employee) {
      return '';
    }
    $uid = $employee->id();
    // Return Markup Html.
    $id = "membership-qualification-modal-$uid";
    return "<div class='modal fade' id='$id' tabindex='-1' role='dialog' aria-labelledby='$id' aria-hidden='true'> <div class='modal-dialog' role='document'> <div class='modal-content'> <div class='modal-header'> <h5 class='modal-title' id='$id-label'>Modal title</h5> <button type='button' class='close' data-dismiss='modal' aria-label='Close'> <span aria-hidden='true'>×</span> </button> </div> <div class='modal-body'> ... </div> <div class='modal-footer'> <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button> <button type='button' class='btn btn-primary'>Save changes</button> </div> </div> </div></div>";
  }

  /**
   * Get Find Employees View.
   *
   * @return array
   *   The view render array.
   */
  public function getFindEmployeesView() {
    return views_embed_view('find_employees', $display_id = 'default');
  }

  /**
   * Get Form: Create New Employee.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   *
   * @return array
   *   The form render array.
   */
  public function getAddFirmEmployeeForm(UserInterface $user, TermInterface $firm) {
    return $form = \Drupal::formBuilder()
      ->getForm('Drupal\am_net_firms\Form\AddFirmEmployeeForm', $user, $firm);
  }

  /**
   * Get Form: Find Employees.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   *
   * @return array
   *   The form render array.
   */
  public function getFindEmployeesForm(UserInterface $user, TermInterface $firm) {
    return $form = \Drupal::formBuilder()
      ->getForm('Drupal\am_net_firms\Form\FindEmployeesForm', $user, $firm);
  }

}
