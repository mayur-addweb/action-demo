<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Implements the Application/Membership Felony Conviction form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipMultiStepFormBase
 */
class FelonyConvictionForm extends MembershipMultiStepFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.application.felony_conviction';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($form instanceof RedirectResponse) {
      return $form;
    }
    $form['title'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">Membership Application Status</h3>',
    ];
    $description = $this->t("Contact us at (800) 733-8272 or <a href='membership@vscpa.com'>membership@vscpa.com</a> as soon as possible to determine your membership eligibility.");
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '<div class="group-field-description">' . $description . '</div>',
    ];
    // Actions.
    $form['actions']['go_back'] = [
      '#title' => $this->t('Go back'),
      '#type' => 'link',
      '#url' => Url::fromRoute('am_net_membership.application.membership_qualification'),
      '#weight' => 1,
      '#attributes' => [
        'class' => ['btn btn-white'],
      ],
    ];
    unset($form['actions']['submit']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
