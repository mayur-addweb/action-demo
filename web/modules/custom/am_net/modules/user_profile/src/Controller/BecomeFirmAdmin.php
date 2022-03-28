<?php

namespace Drupal\am_net_user_profile\Controller;

use Drupal\user\UserInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Profile Update: Become Firm Admin page controller.
 */
class BecomeFirmAdmin extends ProfileUpdateBase {

  /**
   * {@inheritdoc}
   *
   * Builds the Add Employees form.
   */
  public function render(UserInterface $user) {

    // Check if the user has previously submitted the request.
    $build = [
      '#id' => 'become-firm-admin-controller',
      '#attributes' => ['class' => ['become-firm-admin-controller']],
    ];
    // Title.
    $title = $this->t('Become a Firm Admin');
    $header_description = '';
    $build['title'] = [
      '#type' => 'item',
      '#markup' => '<div class="page-header"><h4 class="accent-left purple">' . $title . ' <small>' . $header_description . '</small></h4></div>',
    ];
    // Get User firm.
    $firm = $this->getFirm($user);
    if ($firm) {
      $firm_item = $this->getFirmDescription($firm);
      $build['firm']['description'] = [
        '#markup' => $firm_item,
      ];
    }
    else {
      // The user does not have any linked firm yet.
    }
    // Become a firm admin form.
    $build['form'] = $this->getBecomeFirmAdminForm($user, $firm);
    return $build;
  }

  /**
   * Get Firm Description.
   *
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The user entity.
   *
   * @return string|null
   *   The Firm Description, Otherwise null.
   */
  public function getFirmDescription(TermInterface $firm = NULL) {
    if (!$firm) {
      return NULL;
    }
    $entity_type = 'taxonomy_term';
    $view_mode = 'firm_summary';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $pre_render = $view_builder->view($firm, $view_mode);
    return '<div class="card full-width"><div class="card-padding">' . render($pre_render) . '</div></div>';
  }

  /**
   * Loads User linked Firm.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Firm Term related ot the user, Otherwise null.
   */
  public function getFirm(UserInterface $user = NULL) {
    $firms = $user->get('field_firm')->referencedEntities();
    $firm = !empty($firms) ? current($firms) : NULL;
    return $firm;
  }

  /**
   * Become Firm Admin Form.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   * @param \Drupal\taxonomy\TermInterface|null $firm
   *   The user entity.
   *
   * @return array
   *   The form render array.
   */
  public function getBecomeFirmAdminForm(UserInterface $user, TermInterface $firm = NULL) {
    return $form = \Drupal::formBuilder()->getForm('Drupal\am_net_user_profile\Form\BecomeFirmAdminForm', $user, $firm);
  }

  /**
   * Redirects users to their 'Become a Firm Admin' page.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'am_net_firms.employee_management_tool' route
   * with the '_user_is_logged_in' requirement.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the Manage My firm page of the currently
   *   logged in user.
   */
  public function goToBecomeFirmAdminPage() {
    return $this->redirect('am_net_user_profile.become_firm_admin', ['user' => $this->currentUser()->id()]);
  }

}
