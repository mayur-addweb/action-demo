<?php

namespace Drupal\am_net_cpe\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;

/**
 * Provides the 'My CPE - Digital Rewind' block.
 *
 * @Block(
 *   id = "am_net_cpe_my_cpe_digital_rewind_block",
 *   admin_label = @Translation("My CPE - Digital Rewind")
 * )
 */
class DigitalRewindBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    if (!\Drupal::currentUser()->isAuthenticated()) {
      return [
        '#markup' => '<h5>Log in <i><a href="/user/login">here</a></i> to see your CPE courses & Registrations.</h5>',
      ];
    }
    /** @var \Drupal\am_net_cpe\CpeProductManagerInterface $manager */
    $uid = \Drupal::currentUser()->id();
    $user = User::load($uid);
    $cpe_info = \Drupal::service('am_net_cpe.virtual_conference_manager')->loadVirtualConferenceInfo($user);
    if (empty($cpe_info)) {
      return [
        '#cache' => [
          'max-age' => 0,
          'contexts' => [
            'user',
          ],
        ],
      ];
    }
    return [
      '#theme' => 'my_cpe_digital_rewind',
      '#registrations' => $cpe_info['registrations'],
      '#am_net_name_id' => $cpe_info['am_net_name_id'],
      '#full_name' => $cpe_info['full_name'],
      '#uid' => $uid,
      '#cache' => [
        'max-age' => 0,
        'contexts' => [
          'user',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
