<?php

namespace Drupal\am_net_cpe\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;

/**
 * Provides the 'My CPE - Electronic Materials' block.
 *
 * @Block(
 *   id = "am_net_cpe_electronic_materials_block",
 *   admin_label = @Translation("My CPE - Electronic Materials")
 * )
 */
class ElectronicMaterialsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!\Drupal::currentUser()->isAuthenticated()) {
      return [
        '#markup' => '<h5>Log in <i><a href="/user/login">here</a></i> to see your Electronic Materials.</h5>',
      ];
    }
    /** @var \Drupal\am_net_cpe\CpeProductManagerInterface $manager */
    $uid = \Drupal::currentUser()->id();
    $user = User::load($uid);
    $cpe_info = \Drupal::service('am_net_cpe.product_manager')->getMyCpe($user, $include_expired_product = TRUE);
    if (empty($cpe_info)) {
      return [];
    }
    $on_demand = $this->filterByElectronicMaterials($cpe_info['on_demand']);
    $in_person = $this->filterByElectronicMaterials($cpe_info['in_person']);
    $online = $this->filterByElectronicMaterials($cpe_info['online']);
    $show_title = !empty($online) || !empty($in_person) || !empty($online);
    $elements = [
      '#theme' => 'my_cpe_electronic_materials',
      '#on_demand' => $on_demand,
      '#in_person' => $in_person,
      '#online' => $online,
      '#show_title' => $show_title,
      '#uid' => $uid,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function filterByElectronicMaterials(array $items = []) {
    if (empty($items)) {
      return NULL;
    }
    $products = [];
    foreach ($items as $delta => $item) {
      $electronic_materials = $item['electronic_materials'] ?? NULL;
      if (empty($electronic_materials)) {
        continue;
      }
      $products[] = $item;
    }
    if (empty($products)) {
      // Items has no electronic materials related.
      return NULL;
    }
    return $products;
  }

}
