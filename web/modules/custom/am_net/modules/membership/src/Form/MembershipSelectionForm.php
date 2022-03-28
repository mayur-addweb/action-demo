<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements the Application/Membership Selection form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipMultiStepFormBase
 */
class MembershipSelectionForm extends MembershipMultiStepFormBase {

  /**
   * The List of fields keys used on this step.
   *
   * @var array
   */
  protected $stepFields = [
    // - Step 1. Membership Selection form.
    'field_member_select' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.application.selection';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($form instanceof RedirectResponse) {
      return $form;
    }
    // Section: Application for Membership.
    $field_name = 'field_member_select';
    $fields = [$field_name];
    $section_title = $this->t('Application for Membership');
    $description = '<p><strong>Current VSCPA members: <a href="/user/login">update your information</a> and/or <a href="/user/login">renew</a> your membership today.</strong></p>';
    $this->addFormGroup($fields, $form, '1', $section_title, $description);
    // Ad extra Class for hide field title.
    $form[$field_name]['#attributes']['class'][] = 'field--hide-legend';
    unset($form[$field_name][$field_name]['#options']['_none']);
    // Set required.
    $form[$field_name][$field_name]['#required'] = TRUE;
    // Set Default Values.
    $this->setDefaultValues([$field_name], $form);
    // Actions.
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Put the lock if is a authenticated user.
    $this->membershipChecker->lockUserSync($this->currentUser);
    // Save the Changes.
    $values = $form_state->getValues();
    $this->setFieldsValues($this->stepFields, $values);
    // Go to Next Step.
    $form_state->setRedirect('am_net_membership.application.general_information');
  }

}
