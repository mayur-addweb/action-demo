<?php

namespace Drupal\am_net_firms\MembershipQualification;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Membership Qualification Form.
 *
 * @package Drupal\am_net_firms\MembershipQualification\Form
 */
class MembershipQualificationForm extends StepsFormBase {

  use StepsDefinitionTrait;

  /**
   * Other college ID.
   */
  const OTHER_COLLEGE_ID = '234';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'membership.qualification';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $message = $this->t("Please complete all required fields on this form to determine the employee's membership fees.");
    drupal_set_message($message);
    $form['#attributes'] = [
      'class' => [
        'emt_employee_membership_qualification',
      ],
    ];
    $form['wrapper-messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'messages-wrapper',
      ],
      '#prefix' => '<div class="row"><div class="col-xs-8 col-md-8">',
      '#suffix' => '</div></div>',
    ];
    $messages = drupal_get_messages();
    $form['wrapper-messages']['default'] = [
      '#theme' => 'status_messages',
      '#message_list' => $messages,
      '#status_headings' => [
        'status' => $this->t('Status message'),
        'error' => $this->t('Error message'),
        'warning' => $this->t('Warning message'),
      ],
    ];
    // Build the employee selector form.
    $wrapper_id = Html::getUniqueId('emt-employee-membership-qualification-form');
    $form['wrapper'] = [
      '#wrapper_id' => $wrapper_id,
      '#prefix' => '<div class="form-wrapper-left-group">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#attributes' => [
        'id' => 'form-wrapper',
      ],
    ];
    // Attach step form elements.
    $form['wrapper'] += $this->buildStepFormElements($this->stepId);
    // Attach buttons.
    $form['wrapper']['actions']['#type'] = 'actions';
    $buttons = $this->getStepButtons($this->stepId);
    foreach ($buttons as $button) {
      $button_key = $button['key'];
      $button_submit_handler = $button['submit_handler'];
      $button_ajaxify = $button['ajaxify'];
      $form['wrapper']['actions'][$button_key] = $this->buildButtonElement($button);
      if ($button_ajaxify) {
        // Add ajax to button.
        $form['wrapper']['actions'][$button_key]['#ajax'] = [
          'callback' => [$this, 'loadStep'],
          'wrapper' => $form['wrapper']['#wrapper_id'],
          'effect' => 'fade',
        ];
      }
      $callable = [$this, $button_submit_handler];
      if ($button_submit_handler && is_callable($callable)) {
        // Attach submit handler to button, so we can execute it later on..
        $form['wrapper']['actions'][$button_key]['#submit_handler'] = $button_submit_handler;
      }
    }
    // Add Employee info.
    $key = 'employee_container';
    $form[$key] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'employee-wrapper',
      ],
      '#prefix' => '<div class="col-xs-4 col-md-4">',
      '#suffix' => '</div>',
    ];
    $form[$key]['employee'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => [
        'class' => [
          'item-list',
        ],
      ],
      '#attributes' => [
        'class' => [
          'list-group',
        ],
      ],
      '#items' => [
        [
          '#markup' => $this->t('Employee Summary Info'),
          '#wrapper_attributes' => [
            'class' => [
              'list-group-item',
              'active',
            ],
          ],
        ],
        [
          '#markup' => $this->employeeManagementTool->getUserSummary($this->employee),
          '#wrapper_attributes' => [
            'class' => [
              'list-group-item',
            ],
          ],
        ],
      ],
    ];
    // Firm Info.
    $form[$key]['firm'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => [
        'class' => [
          'item-list',
        ],
      ],
      '#attributes' => [
        'class' => [
          'list-group',
          'firm-summary-info',
        ],
      ],
      '#items' => [
        [
          '#markup' => $this->t('Firm Summary Info'),
          '#wrapper_attributes' => [
            'class' => [
              'list-group-item',
              'active',
            ],
          ],
        ],
        [
          '#markup' => $this->employeeManagementTool->getFirmDescription($this->firm),
          '#wrapper_attributes' => [
            'class' => [
              'list-group-item',
            ],
          ],
        ],
      ],
    ];
    // Return Form.
    return $form;
  }

  /**
   * Ajax callback to load new step.
   *
   * @param array $form
   *   Form array.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function loadStep(array &$form) {
    $response = new AjaxResponse();
    $messages = drupal_get_messages();
    if (!empty($messages)) {
      // Form did not validate, get messages and render them.
      $messages = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error' => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ];
      $response->addCommand(new HtmlCommand('#messages-wrapper', $messages));
    }
    else {
      // Remove messages.
      $response->addCommand(new HtmlCommand('#messages-wrapper', ''));
    }
    // Update Form.
    $response->addCommand(new HtmlCommand('#form-wrapper', $form['wrapper']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $field_values = $form_state->getValues();
    // Only validate if validation doesn't have to be skipped.
    // For example on "previous" button.
    $skip_validation = isset($triggering_element['#skip_validation']) && ($triggering_element['#skip_validation']);
    $fields_validators = $this->getStepFieldsValidators($this->stepId);
    if (!$skip_validation && !empty($fields_validators)) {
      // Validate fields.
      foreach ($fields_validators as $field => $validators) {
        // Validate all validators for field.
        $field_value = $form_state->getValue($field);
        $field_values[$field] = $field_value;
        foreach ($validators as $validator) {
          /* @var $validator \Drupal\am_net_firms\MembershipQualification\Validator\ValidatorInterface */
          if (!$validator->validates($field_value, $field_values)) {
            $form_state->setErrorByName($field, $validator->getErrorMessage());
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save filled values to step. So we can use them as default_value later on.
    $values = [];
    foreach ($this->getStepFieldNames($this->stepId) as $name) {
      $values[$name] = $form_state->getValue($name);
    }
    $current_values = $this->getValues();
    $new_values = array_merge($current_values, $values);
    $this->setValues($new_values);
    // Set step to navigate to.
    $triggering_element = $form_state->getTriggeringElement();
    $this->stepId = $triggering_element['#goto_step'];
    // If an extra submit handler is set, execute it.
    // We already tested if it is callable before.
    if (isset($triggering_element['#submit_handler'])) {
      $this->{$triggering_element['#submit_handler']}($form, $form_state);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for last step of form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   */
  public function submitValues(array &$form, FormStateInterface $form_state) {
    $values = $this->getValues();
    if (empty($values)) {
      return;
    }
    // Save Values.
    $save_user_changes = FALSE;
    foreach ($values as $field_name => $value) {
      if ($this->employee->hasField($field_name)) {
        $this->employee->set($field_name, $value);
        $save_user_changes = TRUE;
      }
    }
    // Save Changes.
    if ($save_user_changes) {
      // Get Membership Checker instance.
      $membership_checker = $this->employeeManagementTool->getMembershipChecker();
      // Inject Membership dues.
      $membership_checker->setMembershipDues($this->employee, $reset = TRUE);
      // Un-lock Sync process for this user.
      $membership_checker->unlockUserSync($this->employee);
      // Save.
      $this->employee->save();
    }
  }

}
