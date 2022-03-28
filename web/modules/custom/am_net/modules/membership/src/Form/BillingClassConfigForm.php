<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * The Billing Class configuration form.
 */
class BillingClassConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'am_net_membership_billing_class.checker_manager',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership_billing_class_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the default checker.
    $config = $this->config('am_net_membership.billing_class_checker_manager');
    $default_checker_id = $config->get('default_checker_id');
    $license_expiration_month = $config->get('license_expiration_month');
    $license_expiration_day = $config->get('license_expiration_day');
    $membership_renewal_on_days_left = $config->get('membership_renewal_on_days_left');

    $billing_class_checker_manager = \Drupal::service('am_net_membership.billing_class_checker_manager');
    $checker_options = $billing_class_checker_manager->listCheckerIds();

    $form['checker_manager'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Billing Class checkers'),
    ];

    $form['checker_manager']['default_checker_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Default checker'),
      '#options' => $checker_options,
      '#default_value' => $default_checker_id,
      '#ajax' => [
        'callback' => 'Drupal\am_net_membership\Form\BillingClassConfigForm::changeBillingClassHandlerHelpInfo',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];
    /** @var \Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface $checker */
    $checker = $billing_class_checker_manager->getCheckerById($default_checker_id);
    if ($checker) {
      $help = $checker->getHelp();
      $form['checker_manager']['checker_help'] = [
        '#type' => 'details',
        '#title' => $this->t('Help'),
        '#open' => TRUE,
        '#description' => $this->t("Provide online user help for know what is the logic associated with each Billing Class code according to the user's field values."),
      ];
      $form['checker_manager']['checker_help']['help'] = [
        '#type' => 'container',
        'help' => $help,
      ];
    }

    $form['membership_license'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Membership License Setting'),
      '#open' => TRUE,
    ];

    $form['membership_license']['expiration_date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('What is the Membership license expiration date for the next year(Month/Day)'),
      '#attributes' => ['class' => ['container-inline']],
    ];

    // Expiration date months.
    $months = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];
    $form['membership_license']['expiration_date']['expiration_date_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => array_combine($months, $months),
      '#default_value' => $license_expiration_month,
    ];

    // Expiration date days.
    $days = range(1, 31);
    $form['membership_license']['expiration_date']['expiration_date_day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => array_combine($days, $days),
      '#default_value' => $license_expiration_day,
    ];

    // Allows membership renewal on a specific number
    // of days left before it expires.
    $form['membership_license']['membership_renewal_on_days_left'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allows membership renewal on a specific number of days left before it expires.'),
      '#default_value' => $membership_renewal_on_days_left,
      '#required' => TRUE,
      '#description' => $this->t('Please enter a valid number of days left.'),
      '#size' => 10,
      '#acces' => 10,
    ];
    hide($form['membership_license']['membership_renewal_on_days_left']);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config_factory = $this->configFactory();
    $config_key = 'am_net_membership.billing_class_checker_manager';
    $editable_config = $config_factory->getEditable($config_key);
    // Set the default Checker ID.
    $editable_config->set('default_checker_id', $values['default_checker_id']);
    // Set the Expiration date month.
    $editable_config->set('license_expiration_month', $values['expiration_date_month']);
    // Set the Expiration date day.
    $editable_config->set('license_expiration_day', $values['expiration_date_day']);
    // Set the number of days left.
    $editable_config->set('membership_renewal_on_days_left', $values['membership_renewal_on_days_left']);
    // Save your data to the file system.
    $editable_config->save();
    drupal_set_message($this->t('The configuration has been updated.'));
  }

  /**
   * Ajax callback to change help info.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response.
   */
  public static function changeBillingClassHandlerHelpInfo(array &$form, FormStateInterface $form_state) {
    $billing_class_checker_manager = \Drupal::service('am_net_membership.billing_class_checker_manager');
    $values = $form_state->getValues();
    $default_checker_id = $values['default_checker_id'];
    /** @var \Drupal\am_net_membership\BillingClass\BillingClassCheckerInterface $checker */
    $checker = $billing_class_checker_manager->getCheckerById($default_checker_id);
    $helpElem = $checker->getHelp();
    $response = new AjaxResponse();
    $renderer = \Drupal::service('renderer');
    $response->addCommand(new ReplaceCommand('#edit-checker-help #edit-help > div:first-child', $renderer->render($helpElem)));
    return $response;
  }

}
