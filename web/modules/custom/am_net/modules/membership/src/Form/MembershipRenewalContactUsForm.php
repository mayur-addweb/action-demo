<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements the Membership/Renewal Contact Us form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipRenewalFormBase
 */
class MembershipRenewalContactUsForm extends MembershipRenewalFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.renewal.contact.us';
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
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $section_title . '</h3>',
    ];
    $ethic = \Drupal::request()->get('ethic');
    // Description.
    $description = ($ethic == 'yes') ? $this->t("Contact us at (800) 733-8272 or <a href='membership@vscpa.com'>membership@vscpa.com</a> as soon as possible to determine your membership eligibility.") : '';
    $form['header']['description'] = [
      '#type' => 'item',
      '#markup' => '<div class="group-field-description">' . $description . '</div>',
    ];
    // Remove actions buttons here.
    unset($form['actions']);
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
    // Silence is gold.
  }

}
