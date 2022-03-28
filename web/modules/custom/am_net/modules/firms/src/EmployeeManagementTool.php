<?php

namespace Drupal\am_net_firms;

use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\am_net_membership\MembershipCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of the Employee Management Tool service.
 */
class EmployeeManagementTool implements EMTInterface {

  /**
   * The vocabulary ID.
   *
   * @var string
   */
  protected $vid = 'firm';

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId = 'taxonomy_term';

  /**
   * The membership checker.
   *
   * @var \Drupal\am_net_membership\MembershipCheckerInterface
   */
  protected $membershipChecker;

  /**
   * Constructs a new EventPriceResolver.
   *
   * @param \Drupal\am_net_membership\MembershipCheckerInterface $am_net_membership_checker
   *   The AM.net membership checker.
   */
  public function __construct(MembershipCheckerInterface $am_net_membership_checker) {
    $this->membershipChecker = $am_net_membership_checker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_membership.checker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipChecker() {
    return $this->membershipChecker;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipStatusInfo(UserInterface $user = NULL) {
    return $this->membershipChecker->getMembershipStatusInfo($user);
  }

  /**
   * {@inheritdoc}
   */
  public function linkUserToFirm(TermInterface $firm = NULL, UserInterface $user = NULL) {
    if ($firm && $user) {
      // Link the given user to the given firm.
      $user->set('field_firm', $firm->id());
      // Save User.
      $user->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unLinkUserToFirm(TermInterface $firm = NULL, UserInterface $user = NULL) {
    if ($firm && $user) {
      // Link the given user to the given firm.
      $user->set('field_firm', NULL);
      // Save User.
      $user->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function firmAdministratorAccessCheck(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    if (!$this->isFirmAdmin($account)) {
      return FALSE;
    }
    // Check if there are a valid user in the current context.
    $user = \Drupal::routeMatch()->getParameter('user');
    if ($user && ($user instanceof AccountInterface) && ($user->id() != $account->id())) {
      return FALSE;
    }
    // User has access!.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFirmAdmin(AccountInterface $account) {
    // Check Role.
    return in_array('firm_administrator', $account->getRoles());
  }

  /**
   * Get Firm's Employee list.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   * @param int $limit
   *   Add limit and pager.
   * @param string $filter
   *   Filter by member status.
   *
   * @return array
   *   The array of employees linked to the given Company.
   */
  public function getFirmEmployeeList(TermInterface $firm = NULL, $limit = -1, $filter = 'all') {
    if (!$firm) {
      return [];
    }
    $firm_id = $firm->id();
    $table_name = 'user__field_firm';
    $field_target_id = 'field_firm_target_id';
    // Database instance.
    $database = \Drupal::database();
    // Select users ids linked to the given firm id.
    $query = $database->select($table_name, 'us')
      ->fields('us', ['entity_id'])
      ->condition("us.$field_target_id", $firm_id)
      ->distinct();
    // Set Filters.
    $filter = strtolower($filter);
    if (in_array($filter, ['members', 'nonmembers'])) {
      $filter_field_name = 'field_member_status_value';
      $filter_field_value = ($filter == 'members') ? 'M' : 'N';
      $query->leftJoin('user__field_member_status', 'um', 'um.entity_id = us.entity_id');
      $query->condition("um.$filter_field_name", $filter_field_value);
    }

    // Set Limit.
    if ($limit > 0) {
      $page = pager_find_page();
      $start = $page * $limit;
      // Get total.
      $result = $query->execute();
      $total_user_ids = $result->fetchCol();
      $total = count($total_user_ids);
      // Add limit.
      $query->range($start, $limit);
      // Now that we have the total number of results, initialize the pager.
      pager_default_initialize($total, $limit);
    }
    // Get user linked to the firm.
    $result = $query->execute();
    $ids = $result->fetchCol();
    if (empty($ids)) {
      return [];
    }
    // Return employees.
    return User::loadMultiple($ids);
  }

  /**
   * Get Firm's Employee Options.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm term entity.
   * @param \Drupal\commerce\PurchasableEntityInterface|null $purchased_entity
   *   The purchased entity entity.
   *
   * @return array
   *   The array of employees linked to the given Company.
   */
  public function getFirmEmployeesOptions(TermInterface $firm = NULL, PurchasableEntityInterface $purchased_entity = NULL) {
    if (!$firm) {
      return [];
    }
    $options = [];
    $employees = $this->getFirmEmployeeList($firm);
    if (empty($employees)) {
      return [];
    }
    $excluded = vscpa_commerce_get_product_customers($purchased_entity);
    foreach ($employees as $delta => $employee) {
      $field_amnet_id = $employee->get('field_amnet_id')->getString();
      if (empty($field_amnet_id)) {
        continue;
      }
      $field_amnet_id = trim($field_amnet_id);
      if (isset($excluded[$field_amnet_id])) {
        // The user already purchased this product.
        continue;
      }
      $options[$employee->id()] = $this->getUserSummary($employee, $single_line = TRUE);
    }
    // Return employees options.
    asort($options);
    return $options;
  }

  /**
   * Get firm's employees with Dues Balances.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The Firm entity.
   *
   * @return array
   *   The array the employees with Dues Balances.
   */
  public function getEmployeesWithDuesBalances(TermInterface $firm = NULL) {
    if (!$firm) {
      return [];
    }
    // Get user linked to the firm.
    $query = \Drupal::entityQuery('user')->condition('field_firm', $firm->id());
    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }
    $employees_with_dues_balance = [];
    // Return employees.
    $employees = User::loadMultiple($ids);
    foreach ($employees as $uid => $employee) {
      // Membership status info.
      $membership_status_info = $this->getMembershipStatusInfo($employee);
      // Check if the current employee has Dues Balance.
      if (($membership_status_info['is_membership_application'] && $membership_status_info['user_has_dues_defined']) || $membership_status_info['is_membership_renewal']) {
        $employees_with_dues_balance[$uid] = $employee;
      }
    }
    return $employees_with_dues_balance;
  }

  /**
   * Get all firm employees by Firm admin User.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The Firm admin user.
   * @param \Drupal\commerce\PurchasableEntityInterface|null $purchased_entity
   *   The purchased entity entity.
   *
   * @return array
   *   The array of employees grouped by firm Offices.
   */
  public function getAllFirmEmployees(AccountInterface $user = NULL, PurchasableEntityInterface $purchased_entity = NULL) {
    $employees = [];
    if (!$user) {
      return $employees;
    }
    if (!($user instanceof UserInterface)) {
      $user = user_load($user->id());
    }
    // Main office.
    $firm = $this->getParentFirm($user);
    if (!$firm) {
      return $employees;
    }
    $employees[] = $this->getEmployeesByFirm($firm, 'main', $purchased_entity);
    // Load branch offices.
    $branch_offices = $this->getFirmBranchOffices($firm);
    foreach ($branch_offices as $delta => $office) {
      $employees[] = $this->getEmployeesByFirm($office, 'branch', $purchased_entity);
    }
    return $employees;
  }

  /**
   * Do remove employees already registered.
   *
   * @param array $employees
   *   The base employees listing.
   * @param \Drupal\commerce\PurchasableEntityInterface|null $purchased_entity
   *   The purchased entity entity.
   *
   * @return array
   *   The employees that are not registered for the given purchased entity.
   */
  public function doRemoveEmployeesAlreadyRegistered(array $employees = [], PurchasableEntityInterface $purchased_entity = NULL) {
    if (!$purchased_entity) {
      return $employees;
    }
    $excluded = vscpa_commerce_get_product_customers($purchased_entity);
    if (empty($excluded)) {
      return $employees;
    }
    $items = [];
    foreach ($employees as $delta => $employee) {
      $field_amnet_id = $employee->amnet_id ?? NULL;
      if (empty($field_amnet_id)) {
        $items[$delta] = $employee;
        continue;
      }
      $field_amnet_id = trim($field_amnet_id);
      if (isset($excluded[$field_amnet_id])) {
        // The user already purchased this product.
        continue;
      }
      $items[$delta] = $employee;
    }
    return $items;
  }

  /**
   * Get all firm employees by Firm.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The given Firm.
   * @param string $type
   *   The Firm type(main or branch).
   * @param \Drupal\commerce\PurchasableEntityInterface|null $purchased_entity
   *   The purchased entity entity.
   *
   * @return array
   *   The array of employees.
   */
  public function getEmployeesByFirm(TermInterface $firm = NULL, $type = NULL, PurchasableEntityInterface $purchased_entity = NULL) {
    $type_label = NULL;
    if ($type == 'main') {
      $type_label = 'Main office';
    }
    elseif ($type == 'branch') {
      $type_label = 'Branch office';
    }
    $item = [
      'employees' => [],
      'firm' => $firm,
      'firm_id' => NULL,
      'firm_type' => $type,
      'type' => $type_label,
    ];
    if (!$firm) {
      return $item;
    }
    $firm_id = $firm->id();
    $item['firm_id'] = $firm_id;
    // Get the list of employees.
    $employees = $this->doQuerySearchEmployeesByFirm($firm_id);
    $item['employees'] = $this->doRemoveEmployeesAlreadyRegistered($employees, $purchased_entity);
    return $item;
  }

  /**
   * Do database Query to get all the employees by firm ID.
   *
   * @param string $firm_id
   *   The Firm ID.
   * @param string $member_status
   *   The user's member status.
   *
   * @return array
   *   The array of employees.
   */
  public function doQuerySearchEmployeesByFirm($firm_id = NULL, $member_status = NULL) {
    if (empty($firm_id)) {
      return NULL;
    }
    // Database instance.
    $database = \Drupal::database();
    $query = $database->select('user__field_firm', 'us');
    // Condition by firm ID.
    $query->condition('us.field_firm_target_id', $firm_id);
    // Join with last name field.
    $query->leftJoin('user__field_familyname', 'usl', 'usl.entity_id = us.entity_id');
    // Join with first name field.
    $query->leftJoin('user__field_givenname', 'usf', 'usf.entity_id = us.entity_id');
    // Join with Email field.
    $query->leftJoin('users_field_data', 'usd', 'usd.uid = us.entity_id');
    // Join with Virginia Certification # field.
    $query->leftJoin('user__field_cert_va_no', 'usc', 'usc.entity_id = us.entity_id');
    if (!empty($member_status)) {
      // Join with membership status field.
      $query->leftJoin('user__field_member_status', 'usm', 'usm.entity_id = us.entity_id');
      // Condition by members status.
      $query->condition('usm.field_member_status_value', $member_status, 'IN');
    }
    // Join with AM.Net ID field.
    $query->leftJoin('user__field_amnet_id', 'usid', 'usid.entity_id = us.entity_id');
    // Get the fields.
    $query->addField('us', 'entity_id', 'uid');
    $query->addField('usf', 'field_givenname_value', 'first_name');
    $query->addField('usl', 'field_familyname_value', 'last_name');
    $query->addField('usd', 'mail', 'mail');
    $query->addField('usc', 'field_cert_va_no_value', 'certificate_number');
    $query->addField('usid', 'field_amnet_id_value', 'amnet_id');
    // Soft by last name.
    $query->orderBy('usl.field_familyname_value', 'ASC');
    // Get user linked to the firm.
    $result = $query->execute();
    $ids = $result->fetchAllAssoc('uid');
    return $ids;
  }

  /**
   * Return the list related of a Firm Admin.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The Firm admin user.
   * @param bool $with_users_linked
   *   Flag for get firms with users linked only.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The array the Branch firm Offices.
   */
  public function getFirmsList(UserInterface $user = NULL, $with_users_linked = FALSE) {
    if (!$user) {
      return [];
    }
    // Main office.
    $firm = $this->getParentFirm($user);
    if (!$firm) {
      return [];
    }
    $firms = [];
    $suffix = ' — Main office';
    $firms[$firm->id()] = $this->getFirmDetailedTitle($firm) . $suffix;
    // Load branch offices.
    $branch_offices = $this->getFirmBranchOffices($firm, $with_users_linked);
    $suffix = ' — Branch office';
    /* @var \Drupal\taxonomy\TermInterface $office */
    foreach ($branch_offices as $delta => $office) {
      $firms[$office->id()] = $this->getFirmDetailedTitle($office) . $suffix;
    }
    return $firms;
  }

  /**
   * Loads Parent Firm Term related ot the user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Parent Firm Term related ot the user, Otherwise null.
   */
  public function getParentFirm(UserInterface $user = NULL) {
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemList $item */
    $item = $user->get('field_firm');
    if (empty($item)) {
      return NULL;
    }
    $firms = $item->referencedEntities();
    if (empty($firms)) {
      return NULL;
    }
    /* @var \Drupal\taxonomy\TermInterface $firm */
    $firm = current($firms);
    $user_firm_amnet_id = $firm->get('field_amnet_id')->getString();
    if (empty($user_firm_amnet_id)) {
      return NULL;
    }
    $amnet_main_office = $firm->get('field_amnet_main_office')->getString();
    if (empty($amnet_main_office)) {
      // The firm does not have main office associated.
      return $firm;
    }
    if ($user_firm_amnet_id == $amnet_main_office) {
      return $firm;
    }
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('field_amnet_id', $amnet_main_office);
    $ids = $query->execute();
    if (empty($ids)) {
      return NULL;
    }
    $firms = Term::loadMultiple($ids);
    /* @var \Drupal\taxonomy\TermInterface $firm */
    $firm = current($firms);
    return $firm;
  }

  /**
   * Loads Branch Offices by Parent Firm Term.
   *
   * @param \Drupal\taxonomy\TermInterface|null $parent_firm
   *   The parent firm entity.
   * @param bool $with_users_linked
   *   Flag for get firms with users linked only.
   * @param int $limit
   *   Add limit and pager.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The array the Branch firm Offices.
   */
  public function getFirmBranchOffices(TermInterface $parent_firm = NULL, $with_users_linked = FALSE, $limit = -1) {
    if (!$parent_firm) {
      return [];
    }
    $terms = [];
    // Database instance.
    $database = \Drupal::database();
    if ($with_users_linked) {
      // Select firms ids with linked users.
      $query = $database->select('user__field_firm', 'us');
      $result = $query->fields('us', ['field_firm_target_id'])
        ->distinct()
        ->execute();
      $firm_ids_linked_to_users = $result->fetchCol();
      // Get Branch firms.
      $query = $database->select('taxonomy_term_field_data', 't');
      $query->leftJoin('taxonomy_term__parent', 'h', 'h.entity_id = t.tid');
      $result = $query->fields('t', ['tid'])
        ->condition('t.vid', $this->vid)
        ->condition('h.parent_target_id', $parent_firm->id())
        ->condition('t.tid', $firm_ids_linked_to_users, 'IN')
        ->orderBy('t.weight')
        ->orderBy('t.name')
        ->execute();
      $firm_ids = $result->fetchCol();
    }
    else {
      $query = $database->select('taxonomy_term_field_data', 't');
      $query->extend('Drupal\\Core\\Database\\Query\\PagerSelectExtender');
      $query->leftJoin('taxonomy_term__parent', 'h', 'h.entity_id = t.tid');
      $query->leftJoin('taxonomy_term__field_address', 'address', 'address.entity_id = t.tid');
      $query->fields('t', ['tid'])
        ->condition('t.vid', $this->vid)
        ->condition('h.parent_target_id', $parent_firm->id())
        ->orderBy('t.weight')
        ->orderBy('address.field_address_locality');
      if ($limit > 0) {
        $page = pager_find_page();
        $start = $page * $limit;
        // Get total.
        $result = $query->execute();
        $ids = $result->fetchCol();
        $total = count($ids);
        // Add limit.
        $query->range($start, $limit);
        $result = $query->execute();
        $firm_ids = $result->fetchCol();
        // Now that we have the total number of results, initialize the pager.
        pager_default_initialize($total, $limit);
      }
      else {
        $result = $query->execute();
        $firm_ids = $result->fetchCol();
      }
    }
    // Load the terms.
    if (!empty($firm_ids)) {
      $terms = Term::loadMultiple($firm_ids);
    }
    return $terms;
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
    return $firm_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmDescription(TermInterface $firm = NULL, $single_line = FALSE) {
    if (!$firm) {
      return NULL;
    }
    if ($single_line) {
      $summary = $firm->label();
    }
    else {
      $entity_type = 'taxonomy_term';
      $view_mode = 'firm_summary';
      $view_builder = \Drupal::entityTypeManager()
        ->getViewBuilder($entity_type);
      $pre_render = $view_builder->view($firm, $view_mode);
      $summary = render($pre_render);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmDetailedTitle(TermInterface $firm = NULL) {
    if (!$firm) {
      return NULL;
    }
    $summary[] = $firm->label();
    // Add Address.
    $address = $firm->get('field_address')->getString();
    if (!empty($address)) {
      $summary[] = $address;
    }
    return implode(' ', $summary);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserSummary(UserInterface $user = NULL, $single_line = FALSE) {
    if (!$user) {
      return NULL;
    }
    if ($single_line) {
      // Get First name.
      $first_name = $user->get('field_givenname')->getString();
      // Get Last name.
      $last_name = $user->get('field_familyname')->getString();
      // Email.
      $email = ' — ' . $user->getEmail();
      // Set the summary.
      $summary = implode(' ', [$first_name, $last_name, $email]);
    }
    else {
      $entity_type = 'user';
      $view_mode = 'user_summary';
      $view_builder = \Drupal::entityTypeManager()
        ->getViewBuilder($entity_type);
      $pre_render = $view_builder->view($user, $view_mode);
      $summary = render($pre_render);
    }
    return $summary;
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
   * @param string $col
   *   The column classes.
   *
   * @return string
   *   The Firm Panel.
   */
  public function buildFirmPanel($firm_title = '', $firm_description = '', $firm_actions = '', $col = 'col-sm-6 col-md-6') {
    return "<div class='firm-panel-wrapper'><div class='{$col}'><div class='panel panel-primary no-padding'><div class='panel-heading'><h3 class='panel-title'>{$firm_title}</h3></div><div class='panel-body padding-10'>{$firm_description}</div>{$firm_actions}</div></div></div>";
  }

}
