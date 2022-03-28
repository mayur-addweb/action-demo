<?php

namespace Drupal\am_net_firms\Controller;

use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Url;

/**
 * Employee Management Tool: Manage Employees page controller.
 */
class ManageEmployees extends EMTBase {

  /**
   * {@inheritdoc}
   *
   * Builds the term listing as render able array for table.html.twig.
   */
  public function render(UserInterface $user, TermInterface $firm) {
    $firm_label = $this->employeeManagementTool->getFirmTitle($firm);
    $build = [
      '#attributes' => ['class' => ['manage-my-firm-manage-employees']],
    ];
    // Title.
    $title = $this->t('Manage Employees/Pay Dues');
    $description = '<p>' . $this->t('Use this page to update your current roster and employee information, find and/or add new employees, access employee CPE History and upcoming CPE, pay employee membership dues or submit VSCPA membership applications for nonmembers currently associated with your firm. We strongly recommend updating your roster first.') . '<p>';
    $build['title'] = [
      '#markup' => '<div class="page-header"><h1>' . $title . ' <small><i>' . $firm_label . '</i></small></h1></div>' . $description,
    ];
    // Employees list.
    $section_key = 'employees_list';
    $build[$section_key] = [
      '#type' => 'container',
      '#prefix' => '<div class="row employees-list-section"><div class="col-xs-12 col-lg-12">',
      '#suffix' => '</div></div>',
    ];
    $build[$section_key]['header'] = [
      '#markup' => '<h3 class="accent-left purple">' . $this->t('Employee Information & Roster Management') . '</h3>',
    ];
    $description = $this->t("<p>Click the sections below to see current employees. Use the Actions menu to:</p><ol> <li>View employee information (takes you to the employee's profile, where you can):<ol><li>Update their information</li><li>View any upcoming CPE</li><li>Download CPE Certificates</li><li>View their payment history and download receipts</li></ol> </li> <li>Remove an employee from your roster</li> <li>Submit VSCPA membership applications for nonmembers at this location.</li></ol>");
    $build[$section_key]['description'] = [
      '#markup' => $description,
    ];
    $build[$section_key]['list'] = $this->getFirmEmployeeListViewGroupedByMembershipStatus($firm);
    $build[$section_key]['list']['#attributes']['class'][] = 'firm-employee-list';
    // Renew VSCPA Memberships.
    $title = $this->t('Renew VSCPA Memberships');
    $firm_dues_url = Url::fromRoute('am_net_firms.employee_management_tool.manage_dues', ['user' => $user->id(), 'firm' => $firm->id()], [])->toString();
    $description = '<a class="btn btn-purple btn-sm" href="' . $firm_dues_url . '"><span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> ' . $this->t('Pay Dues') . '</a>';
    $build['firm_dues'] = [
      '#markup' => '<h3 class="accent-left purple">' . $title . '</h3>' . $description,
      '#prefix' => '<div class="row"><div class="col-xs-12 col-lg-6">',
      '#suffix' => '</div>',
    ];
    // Find/Add Employees.
    $section_key = 'add_employees';
    $build[$section_key] = [
      '#type' => 'container',
      '#prefix' => '<div class="col-xs-12 col-lg-6 find-employee-form">',
      '#suffix' => '</div></div>',
    ];
    $build[$section_key]['header'] = [
      '#markup' => '<h3 class="accent-left purple">' . $this->t('Find/Add Employees') . '</h3>',
    ];
    $description = '<p>' . $this->t('Have you hired someone? Search below to see if your employee is already in our system. If they are, you will able to associate them with your firm by following the instructions from the page that loads.') . '<p>';
    $build[$section_key]['description'] = [
      '#markup' => $description,
    ];
    $build[$section_key]['form'] = $this->getFindEmployeesForm($user, $firm);
    return $build;
  }

}
