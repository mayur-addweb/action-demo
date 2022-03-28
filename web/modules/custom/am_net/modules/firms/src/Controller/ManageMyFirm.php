<?php

namespace Drupal\am_net_firms\Controller;

use Drupal\am_net_firms\Form\EMTSettingForm;
use Drupal\user\UserInterface;
use Drupal\Core\Url;

/**
 * Employee Management Tool: Manage My Firm page controller.
 */
class ManageMyFirm extends EMTBase {

  /**
   * {@inheritdoc}
   *
   * Builds the term listing as render able array for table.html.twig.
   */
  public function render(UserInterface $user) {
    $build = [
      '#id' => "manage-my-firm",
      '#attributes' => ['class' => ['manage-my-firm']],
    ];
    // Title.
    $title = $this->t('Manage My Firm');
    $build['title'] = [
      '#markup' => '<div class="page-header page-header-manage-my-firm"><h1>' . $title . ' </h1></div>',
    ];
    // Manage my firm summary.
    $content = \Drupal::state()->get(EMTSettingForm::MANAGE_MY_FIRM_SUMMARY);
    if (!empty($content)) {
      $build['manage_my_firm_summary'] = [
        '#markup' => $content,
      ];
    }
    // CPE Courses.
    $section_key = 'cpe_courses';
    $cpe_courses_url = Url::fromUserInput('/node/16', [])->toString();
    $cpe_courses_action = "<h3 class='accent-left purple'>" . $this->t('CPE') . "</h3><p><a href='{$cpe_courses_url}' class='btn btn-purple' role='button'>Register Employee(s) for CPE</a></p>";
    $build[$section_key] = [
      '#markup' => $cpe_courses_action,
    ];
    // Manage employees.
    $section_key = 'manage_employees';
    $build[$section_key] = [
      '#markup' => '<h3 class="accent-left purple">' . $this->t('Manage Employees/Pay Dues') . '</h3>',
    ];
    // Main Office.
    $mainFirm = $this->employeeManagementTool->getParentFirm($user);
    if ($mainFirm) {
      $firm_title = $this->getFirmTitle($mainFirm) . ' <small>— Main Office</small>';
      $firm_description = $this->getFirmDescription($mainFirm);
      $firm_actions = $this->getFirmActions($user->id(), $mainFirm);
      $firm_item = $this->buildFirmPanel($firm_title, $firm_description, $firm_actions);
      $build[$section_key]['description'] = [
        '#markup' => $firm_item,
      ];
    }
    // Branch Offices.
    $section_key = 'branch_offices';
    $build[$section_key] = [
      '#markup' => $this->t('<h3 class="accent-left purple"> Branch Offices <small class="line-break">Offices are listed alphabetically by city</small></h3>'),
    ];
    // Finally, add the pager to the render array.
    $build[$section_key]["pager"] = [
      '#type' => 'pager',
      '#attributes' => ['class' => ['branch-offices-pager']],
      '#prefix' => "<div class='branch-offices-pager'>",
      '#suffix' => '</div>',
    ];
    $build[$section_key]["firms"] = [
      '#prefix' => "<div class='row'>",
      '#suffix' => '</div>',
    ];
    $branch_offices = $this->employeeManagementTool->getFirmBranchOffices($mainFirm, $with_users_linked = FALSE, $limit = 12);
    if (!empty($branch_offices)) {
      $iterator = 0;
      foreach ($branch_offices as $delta => $branch_firm) {
        $iterator += 1;
        $firm_title = $this->getFirmTitle($branch_firm) . ' <small>— Branch Office</small>';
        $firm_description = $this->getFirmDescription($branch_firm);
        $firm_actions = $this->getFirmActions($user->id(), $branch_firm);
        $firm_item = $this->buildFirmPanel($firm_title, $firm_description, $firm_actions);
        $prefix = "<div class='col-xs-12'>";
        $suffix = '</div><div class="w-100 clearfix margin-bottom-20"></div>';
        $build[$section_key]["firms"]["firm_{$branch_firm->id()}"] = [
          '#markup' => $firm_item,
          '#prefix' => $prefix,
          '#suffix' => $suffix,
        ];
      }
    }
    else {
      $build[$section_key]['description'] = [
        '#markup' => "<div class='alert alert-info' role='alert'> <strong>Heads up!</strong> Your main firm does not have associated branch offices.</div>",
      ];
    }
    return $build;
  }

  /**
   * Redirects users to their Manage My firm page.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'am_net_firms.employee_management_tool' route
   * with the '_user_is_logged_in' requirement.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the Manage My firm page of the currently
   *   logged in user.
   */
  public function goToManageMyFirmPage() {
    return $this->redirect('am_net_firms.employee_management_tool', ['user' => $this->currentUser()->id()]);
  }

}
