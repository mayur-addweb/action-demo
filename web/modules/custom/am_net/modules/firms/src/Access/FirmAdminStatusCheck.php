<?php

namespace Drupal\am_net_firms\Access;

use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\am_net_firms\EmployeeManagementTool;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Determines access to routes based on Firm Admin role status of current user.
 */
class FirmAdminStatusCheck implements AccessInterface {

  /**
   * The membership checker.
   *
   * @var \Drupal\am_net_firms\EmployeeManagementTool
   */
  protected $employeeManagementTool;

  /**
   * Constructs a new FirmAdminStatusCheck.
   *
   * @param \Drupal\am_net_firms\EmployeeManagementTool $employee_management_tool
   *   The Employee Management Tool service.
   */
  public function __construct(EmployeeManagementTool $employee_management_tool) {
    $this->employeeManagementTool = $employee_management_tool;
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
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, Route $route) {
    $required_status = filter_var($route->getRequirement('_user_is_firm_admin'), FILTER_VALIDATE_BOOLEAN);
    $actual_status = $account->isAuthenticated() && $this->employeeManagementTool->isFirmAdmin($account);
    $access_result = AccessResult::allowedIf($required_status === $actual_status)->addCacheContexts(['user.roles:authenticated']);
    if (!$access_result->isAllowed()) {
      $access_result->setReason($required_status === TRUE ? 'This route can only be accessed by Firm Admin users.' : 'This route can only be accessed by non-firm-admin users.');
    }
    return $access_result;
  }

}
