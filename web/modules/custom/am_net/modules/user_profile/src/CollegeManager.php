<?php

namespace Drupal\am_net_user_profile;

use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;

/**
 * Default implementation of the College Manager.
 */
class CollegeManager {

  /**
   * Fetch location Colleges from AM.net.
   *
   * @return array
   *   Array of processed colleges.
   */
  public function fetchColleges() {
    $info = [];
    $colleges = \Drupal::service('am_net.client')->getAllColleges();
    if (!empty($colleges)) {
      foreach ($colleges as $key => $college) {
        $code = isset($college['Code']) ? $college['Code'] : NULL;
        $description = isset($college['Description']) ? $college['Description'] : NULL;
        if (!empty($code) && !empty($description)) {
          /* @var \Drupal\user\UserInterface $user */
          $college = $this->loadCollegeByCode($code);
          if (!$college) {
            $college = Node::create([
              'type' => 'location',
              'title' => $description,
            ]);
          }
          // Update Title.
          $college->set('field_alt_title', $description);
          // Update amnet_id.
          $college->set('field_amnet_id', $code);
          // Set Location Type.
          $college->set('field_loc_type', AM_NET_USER_PROFILE_LOCATION_TYPE_EDUCATIONAL_FACILITY_TID);
          // Set the Owner.
          $college->set('uid', AM_NET_USER_PROFILE_ADMIN_UID);
          // Save Changes on the User account.
          $result = $college->save();
          $message = '';
          switch ($result) {
            case SAVED_NEW:
              $message = t('The College @id has been successfully Added.', ['@id' => $code]);
              break;

            case SAVED_UPDATED:
              $message = t('The College @id has been successfully Updated.', ['@id' => $code]);
              break;

          }
          $length = 30;
          $label = strlen($description) > $length ? substr($description, 0, $length) . '...' : $description;
          $info[] = [$label, $message];
        }
      }
    }
    return $info;
  }

  /**
   * Load College node By College Code.
   *
   * @param int $code
   *   Required param, The College Code ID.
   *
   * @return bool|\Drupal\node\NodeInterface
   *   TRUE when the operation was successfully completed, otherwise FALSE
   */
  public static function loadCollegeByCode($code = NULL) {
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'location', 'field_amnet_id' => $code]);
    if (!empty($nodes)) {
      $node = current($nodes);
      if (($node instanceof NodeInterface) && ($node->bundle() == 'location')) {
        return $node;
      }
    }
    return FALSE;
  }

}
