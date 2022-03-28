<?php

namespace Drupal\am_net_membership\Form;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements the Membership/Renewal Selection form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipRenewalFormBase
 */
class MembershipRenewalSelectionForm extends MembershipRenewalFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.renewal.selection';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($form instanceof RedirectResponse) {
      return $form;
    }
    // Check if the user is a individual or is an Firm administrator.
    if (!($this->membershipChecker->isFirmAdministrator($this->currentUser))) {
      // Go to the Next steps to renew as individual.
      return $this->redirect('am_net_membership.renewal.confirm.information', ['user' => $this->currentUser->id()]);
    }
    // Header.
    $section_title = $this->t('Membership Renewal');
    $form['header'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
    ];
    // Description.
    $options = [
      'query' => ['destination' => '/membership/application'],
    ];
    $url_login = Url::fromRoute('entity.user.edit_form', ['user' => $this->currentUser->id()], $options)->toString();
    $description = "<p><strong>Current VSCPA members: <a href='{$url_login}'>update your information</a> and renew your membership today.</strong></p>";
    $form['header']['description'] = [
      '#type' => 'item',
      '#markup' => '<div class="group-field-description">' . $description . '</div>',
    ];
    // Add fields.
    $form['membership_renewal_option'] = [
      '#type' => 'radios',
      '#title' => $this->t('Membership Renewal Option'),
      '#default_value' => 1,
      '#required' => TRUE,
      '#options' => [
        'individual' => $this->t('I want to renew or reinstate membership for myself.'),
        'firm' => $this->t('I want to renew or reinstate membership for multiple people.'),
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Continue to next step'),
      '#weight' => 2,
      '#attributes' => [
        'class' => ['btn-purple'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Silence is gold.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the Changes.
    $value = $form_state->getValue('membership_renewal_option');
    $route_name = 'am_net_membership.renewal.confirm.information';
    if ($value == 'individual') {
      $route_name = 'am_net_membership.renewal.confirm.information';
    }
    // Go to Next Step.
    $form_state->setRedirect($route_name, ['user' => $this->currentUser->id()]);
  }

}
