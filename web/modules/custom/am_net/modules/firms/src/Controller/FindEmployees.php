<?php

namespace Drupal\am_net_firms\Controller;

use Drupal\user\UserInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Employee Management Tool: Find Employees page controller.
 */
class FindEmployees extends EMTBase {

  /**
   * {@inheritdoc}
   *
   * Builds the term listing as render able array for table.html.twig.
   */
  public function render(UserInterface $user, TermInterface $firm) {
    $build = [
      '#id' => "manage-my-firm-find-employees",
      '#attributes' => ['class' => ['manage-my-firm-find-employees']],
    ];
    // Title.
    $title = $this->t('Find, Add and/or Create New Employees');
    $build['title'] = [
      '#type' => 'item',
      '#markup' => '<div class="page-header"><h1 class="accent-left purple">' . $title . '</h1></div>',
    ];
    $build['form'] = $this->getFindEmployeesView();
    return $build;
  }

}
