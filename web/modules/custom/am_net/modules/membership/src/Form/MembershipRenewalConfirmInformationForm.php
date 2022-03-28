<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements the Membership/Renewal Confirm information form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipRenewalFormBase
 */
class MembershipRenewalConfirmInformationForm extends MembershipRenewalFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.renewal.confirm.information';
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
    // Description.
    $description = '<p>As you know, our members maintain high ethical standards. Please help us maintain our high ethical standards by letting us know:</p>';
    $form['header']['description'] = [
      '#type' => 'item',
      '#markup' => '<div class="group-field-description">' . $description . '</div>',
    ];
    // Add fields.
    $field_name = 'field_convicted_felon';
    $form[$field_name] = [
      '#type' => 'radios',
      '#title' => $this->t('Were you convicted of a felony this year?'),
      '#required' => TRUE,
      '#default_value' => '',
      '#options' => [
        'Y' => $this->t('Yes'),
        'N' => $this->t('No'),
      ],
    ];
    $field_name = 'field_revoked_license';
    $form[$field_name] = [
      '#type' => 'radios',
      '#title' => $this->t('Were any of your professional licenses revoked this year?'),
      '#required' => TRUE,
      '#default_value' => '',
      '#options' => [
        'Yes' => $this->t('Yes'),
        'No' => $this->t('No'),
      ],
    ];
    // User Profile Summary.
    $field_name = 'summary';
    $form[$field_name] = [
      '#type' => 'item',
      '#markup' => $this->t('Our records indicate the following about your CPA license:'),
    ];
    $form[$field_name]['table'] = [
      '#type' => 'table',
      '#caption' => '',
      '#attributes' => [
        'class' => ['current-member-info'],
      ],
      '#header' => [],
    ];
    $i = 1;
    // Check if the user is a college student.
    if ($this->membershipChecker->isCollegeStudent($this->currentUser)) {
      $form[$field_name]['table'][$i]['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('I am a college student?'),
      ];
      $form[$field_name]['table'][$i]['value'] = [
        '#type' => 'item',
        '#markup' => "<strong>{$this->t('Yes')}</strong>",
      ];
      // Educational information: Undergraduate College or University.
      $i += $i;
      $field_value = $this->membershipChecker->getUndergraduateCollegeOrUniversity($this->currentUser, $label = TRUE);
      $form[$field_name]['table'][$i]['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('Undergraduate College or University'),
      ];
      $form[$field_name]['table'][$i]['value'] = [
        '#type' => 'item',
        '#markup' => "<strong>{$field_value}</strong>",
      ];
      // Educational information: Undergraduate Date of Graduation.
      $i += $i;
      $field_value = $this->membershipChecker->getUndergraduateDate($this->currentUser);
      $field_value = !empty($field_value) ? date('Y-m-d', strtotime($field_value)) : $field_value;
      $form[$field_name]['table'][$i]['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('Undergraduate Date'),
      ];
      $form[$field_name]['table'][$i]['value'] = [
        '#type' => 'item',
        '#markup' => "<strong>{$field_value}</strong>",
      ];
      // Educational information: Graduate College or University.
      $i += $i;
      $field_value = $this->membershipChecker->getGraduateCollegeOrUniversity($this->currentUser, $label = TRUE);
      $form[$field_name]['table'][$i]['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('Graduate College or University'),
      ];
      $form[$field_name]['table'][$i]['value'] = [
        '#type' => 'item',
        '#markup' => "<strong>{$field_value}</strong>",
      ];
      // Educational information: Date of Graduation.
      $i += $i;
      $field_value = $this->membershipChecker->getGraduateDate($this->currentUser);
      $field_value = !empty($field_value) ? date('Y-m-d', strtotime($field_value)) : $field_value;
      $form[$field_name]['table'][$i]['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('Date of Graduation'),
      ];
      $form[$field_name]['table'][$i]['value'] = [
        '#type' => 'item',
        '#markup' => "<strong>{$field_value}</strong>",
      ];
    }
    // Certified?.
    $field_value = $this->membershipChecker->isCertified($this->currentUser) ? $this->t('Yes') : $this->t('No');
    $i += $i;
    $form[$field_name]['table'][$i]['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Certified?'),
    ];
    $form[$field_name]['table'][$i]['value'] = [
      '#type' => 'item',
      '#markup' => "<strong>{$field_value}</strong>",
    ];
    // Licensed?.
    $i += $i;
    $field_value = $this->membershipChecker->isLicensed($this->currentUser) ? $this->t('Yes') : $this->t('No');
    $form[$field_name]['table'][$i]['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Licensed?'),
    ];
    $form[$field_name]['table'][$i]['value'] = [
      '#type' => 'item',
      '#markup' => "<strong>{$field_value}</strong>",
    ];
    // Virginia CPA license number.
    $i += $i;
    $form[$field_name]['table'][$i]['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Virginia CPA license number:'),
    ];
    $cpa_number = $this->getFieldValue('field_cert_va_no');
    $form[$field_name]['table'][$i]['value'] = [
      '#type' => 'item',
      '#markup' => "<strong>{$cpa_number}</strong>",
    ];
    // Date Virginia CPA license was received.
    $i += $i;
    $date_cpa = $this->getFieldValue('field_cert_va_date');
    $date_cpa = !empty($date_cpa) ? date('Y-m-d', strtotime($date_cpa)) : $date_cpa;
    $form[$field_name]['table'][$i]['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Date Virginia CPA license was received:'),
    ];
    $form[$field_name]['table'][$i]['value'] = [
      '#type' => 'item',
      '#markup' => "<strong>{$date_cpa}</strong>",
    ];
    // Out-of-state license (if applicable).
    $i += $i;
    $out_of_state = $this->referencedEntityLabel('field_cert_other');
    $form[$field_name]['table'][$i]['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Out-of-state license (if applicable):'),
    ];
    $form[$field_name]['table'][$i]['value'] = [
      '#type' => 'item',
      '#markup' => "<strong>{$out_of_state}</strong>",
    ];
    // Out-of-state license (if applicable).
    $i += $i;
    $out_of_state_cpa_number = $this->getFieldValue('field_cert_other_no');
    $form[$field_name]['table'][$i]['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Out-of-state license number(if applicable):'),
    ];
    $form[$field_name]['table'][$i]['value'] = [
      '#type' => 'item',
      '#markup' => "<strong>{$out_of_state_cpa_number}</strong>",
    ];
    // Date out-of-state license was received (if applicable).
    $i += $i;
    $date_out_of_state_cpa = $this->getFieldValue('field_cert_other_date');
    $date_out_of_state_cpa = !empty($date_out_of_state_cpa) ? date('Y-m-d', strtotime($date_out_of_state_cpa)) : $date_out_of_state_cpa;
    $form[$field_name]['table'][$i]['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Date out-of-state license was received (if applicable):'),
    ];
    $form[$field_name]['table'][$i]['value'] = [
      '#type' => 'item',
      '#markup' => "<strong>{$date_out_of_state_cpa}</strong>",
    ];
    // Confirm info.
    $field_name = 'confirm_info';
    $form[$field_name] = [
      '#type' => 'radios',
      '#title' => $this->t('Is this information still correct?'),
      '#required' => TRUE,
      '#options' => [
        '1' => $this->t('Yes'),
        '0' => $this->t('No'),
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
    // Optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $save_changes = FALSE;
    // field_convicted_felon.
    $field_name = 'field_convicted_felon';
    $field_convicted_felon = $form_state->getValue($field_name);
    if ($this->getFieldValue($field_name) != $field_convicted_felon) {
      $this->currentUser->set($field_name, $field_convicted_felon);
      $save_changes = TRUE;
    }
    // field_revoked_license.
    $field_name = 'field_revoked_license';
    $field_revoked_license = $form_state->getValue($field_name);
    if ($this->getFieldValue($field_name) != $field_revoked_license) {
      $this->currentUser->set($field_name, $field_revoked_license);
      $save_changes = TRUE;
    }
    // field_revoked_license.
    $field_name = 'confirm_info';
    $confirm_info = $form_state->getValue($field_name);
    // Save changes in both fields.
    if ($save_changes) {
      $this->currentUser->save();
    }
    // Define Next step.
    if (($field_convicted_felon == 'N') && ($field_revoked_license == 'No') && ($confirm_info == '1')) {
      $route_name = 'am_net_membership.renewal.verification';
    }
    else {
      $route_name = 'am_net_membership.renewal.contact.us';
    }
    $options = [];
    if (($field_convicted_felon == 'Y') || ($field_revoked_license == 'Yes')) {
      $options = [
        'query' => ['ethic' => 'yes'],
      ];
    }
    // Go to Next Step.
    $form_state->setRedirect($route_name, ['user' => $this->currentUser->id()], $options);
  }

}
