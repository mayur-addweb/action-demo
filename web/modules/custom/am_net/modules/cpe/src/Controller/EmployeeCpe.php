<?php

namespace Drupal\am_net_cpe\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;

/**
 * Employee CPE Controller.
 */
class EmployeeCpe extends ControllerBase {

  /**
   * Render Employee CPE Page.
   *
   * @param \Drupal\user\Entity\User $user
   *   The given employee.
   *
   * @return array
   *   The redirect response.
   */
  public function render(User $user = NULL) {
    if (!$user) {
      throw new AccessDeniedHttpException();
    }
    $cpe_info = \Drupal::service('am_net_cpe.product_manager')->getMyCpe($user);
    if (empty($cpe_info)) {
      return [];
    }
    $elements = [
      '#theme' => 'my_cpe',
      '#on_demand' => $cpe_info['on_demand'],
      '#in_person' => $cpe_info['in_person'],
      '#online' => $cpe_info['online'],
      '#am_net_name_id' => $cpe_info['am_net_name_id'],
      '#full_name' => $cpe_info['full_name'],
      '#uid' => $user->id(),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $elements;
  }

}
