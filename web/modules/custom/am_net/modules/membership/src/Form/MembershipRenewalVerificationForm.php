<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Implements the Membership/Renewal Verification form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipRenewalFormBase
 */
class MembershipRenewalVerificationForm extends MembershipRenewalFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.renewal.confirm.verification';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($form instanceof RedirectResponse) {
      return $form;
    }
    // Header.
    $section_title = $this->t('Membership Renewal');
    $form['header'] = [
      '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
    ];

    // Title.
    $title = 'Verification';
    $form['header']['verification'] = [
      '#type' => 'item',
      '#markup' => '<div class="page-header"><h3>' . $title . '</h3></div>',
    ];
    // Description.
    $membership_expiration_date = $this->membershipChecker->getMembershipLicenseExpirationDate('F j, Y');
    $description = "<p>To pay your dues for the {$membership_expiration_date} membership year verify the information below and then click 'Continue To Next Step'. Please verify the information below is accurate and update the information where necessary.</p>";
    $form['header']['verification']['description'] = [
      '#markup' => $description,
    ];
    $form['container'] = [
      '#type' => 'container',
      '#prefix' => "<div class='row'>",
      '#suffix' => '</div>',
    ];
    $form['container']['home_address'] = [
      '#type' => 'container',
      '#prefix' => "<div class='col-xs-12 col-md-6 home-address'>",
      '#suffix' => '</div>',
    ];
    // Home Address Info.
    // Title.
    $options = [
      'fragment' => 'home-information',
      'query' => ['destination' => Url::fromRoute('<current>')->toString()],
    ];
    $url = Url::fromRoute('entity.user.edit_form', ['user' => $this->currentUser->id()], $options)->toString();
    $title = "Home Address <a class='btn-white btn-sm' href='{$url}'>Update</a>";
    // Description.
    $content = $this->getHomeAddressInfo();
    $form['container']['home_address']['panel'] = [
      '#markup' => $this->buildPanel($title, $content),
    ];
    // Office Address Info.
    $form['container']['office_address'] = [
      '#type' => 'container',
      '#prefix' => "<div class='col-xs-12 col-md-6 office-address'>",
      '#suffix' => '</div>',
    ];
    // Title.
    $options = [
      'fragment' => 'your-place-of-employment',
      'query' => ['destination' => Url::fromRoute('<current>')->toString()],
    ];
    $url = Url::fromRoute('entity.user.edit_form', ['user' => $this->currentUser->id()], $options)->toString();
    $title = "Office Address <a class='btn-white btn-sm' href='{$url}'>Update</a>";
    // Description.
    $content = $this->getOfficeAddressInfo();
    $form['container']['office_address']['panel'] = [
      '#markup' => $this->buildPanel($title, $content),
    ];
    $form['other'] = [
      '#markup' => '<div class="page-header"><h3>Other Information</h3></div>',
    ];
    $form['other']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#default_value' => $this->currentUser->getEmail(),
    ];
    // Submit Action.
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
    // Optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $field_convicted_felon = $form_state->getValue('email');
    if ($email != $this->currentUser->getEmail()) {
      // Save Changes.
      $this->currentUser->setEmail($email);
      $this->currentUser->save();
      // @todo. validate implications of the email change on
      // the user profile sync process.
    }
    // Define Next step.
    $route_name = 'am_net_membership.renewal.membership_dues';
    // Go to Next Step.
    $form_state->setRedirect($route_name, ['user' => $this->currentUser->id()]);
  }

}
