<?php

namespace Drupal\am_net_firms\Controller;

use Drupal\user\UserInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Employee Management Tool: Add Employees page controller.
 */
class AddEmployees extends EMTBase {

  /**
   * {@inheritdoc}
   *
   * Builds the Add Employees form.
   */
  public function render(UserInterface $user, TermInterface $firm) {
    $build = [
      '#id' => 'manage-my-firm-add-employees',
      '#attributes' => ['class' => ['manage-my-firm-add-employees']],
    ];
    // Title.
    $title = $this->t('Create New Employee');
    $header_description = '';
    $build['title'] = [
      '#type' => 'item',
      '#markup' => '<div class="page-header"><h1 class="accent-left purple">' . $title . ' <small>' . $header_description . '</small></h1></div>',
    ];
    // Edit form.
    $build['form'] = $this->getAddFirmEmployeeForm($user, $firm);
    return $build;
  }

}
