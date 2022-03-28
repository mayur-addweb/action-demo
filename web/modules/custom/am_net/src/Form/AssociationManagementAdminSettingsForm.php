<?php

namespace Drupal\am_net\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure (AMS) AM.net Connection settings for this site.
 */
class AssociationManagementAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['am_net.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('am_net.settings');

    $form['production_connection_details'] = [
      '#type' => 'details',
      '#title' => t('Production Connection details'),
      '#weight' => 1,
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['production_connection_details'],
      ],
      'api_prod_base_url' => [
        '#type' => 'textfield',
        '#title' => t('Base Url'),
        '#required' => FALSE,
        '#default_value' => $config->get('api_prod_base_url'),
        '#description' => t('The Base Url for your (AMS) AM.net account. Get or generate a valid API key at your @apilink.'),
      ],
      'api_prod_auth_user' => [
        '#type' => 'textfield',
        '#title' => t('HTTP Authorization User'),
        '#required' => FALSE,
        '#default_value' => $config->get('api_prod_auth_user'),
        '#description' => t('The user for basic HTTP authentication.'),
      ],
      'api_prod_auth_key' => [
        '#type' => 'password',
        '#title' => t('HTTP Authorization Key'),
        '#required' => FALSE,
        '#default_value' => $config->get('api_prod_auth_key'),
        '#description' => t('The key for basic HTTP authentication.'),
      ],
    ];

    $form['development_connection_details'] = [
      '#type' => 'details',
      '#title' => t('Development Connection details'),
      '#weight' => 1,
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['development_connection_details'],
      ],
      'api_dev_base_url' => [
        '#type' => 'textfield',
        '#title' => t('Base Url'),
        '#required' => FALSE,
        '#default_value' => $config->get('api_dev_base_url'),
        '#description' => t('The Base Url for your (AMS) AM.net account. Get or generate a valid API key at your @apilink.'),
      ],
      'api_dev_auth_user' => [
        '#type' => 'textfield',
        '#title' => t('HTTP Authorization User'),
        '#required' => FALSE,
        '#default_value' => $config->get('api_dev_auth_user'),
        '#description' => t('The user for basic HTTP authentication.'),
      ],
      'api_dev_auth_key' => [
        '#type' => 'password',
        '#title' => t('HTTP Authorization Key'),
        '#required' => FALSE,
        '#default_value' => $config->get('api_dev_auth_key'),
        '#description' => t('The key for basic HTTP authentication.'),
      ],
    ];

    $form['connection_environment'] = [
      '#type' => 'select',
      '#options' => [
        'production' => 'Production',
        'development' => 'Development',
      ],
      '#required' => TRUE,
      '#weight' => 3,
      '#title' => t('Connection Environment'),
      '#description' => t('Please select Connection Environment'),
      '#default_value' => $config->get('connection_environment'),
    ];

    $state = \Drupal::state();
    $form['cron'] = [
      '#type' => 'checkbox',
      '#title' => 'Use batch processing.',
      '#weight' => 4,
      '#description' => 'Puts all (AMS) AM.net operations into the cron queue.',
      '#default_value' => $state->get('am_net.settings.cron', FALSE),
    ];

    $form['batch_limit'] = [
      '#type' => 'select',
      '#options' => [
        '1' => '1',
        '10' => '10',
        '25' => '25',
        '50' => '50',
        '75' => '75',
        '100' => '100',
        '250' => '250',
        '500' => '500',
        '750' => '750',
        '1000' => '1000',
        '2500' => '2500',
        '5000' => '5000',
        '7500' => '7500',
        '10000' => '10000',
      ],
      '#title' => t('Batch limit for Fetch and Sync all Constituent Records.'),
      '#description' => t('Maximum number of entities to process in a single cron run on teh task Fetch and Sync all Constituent Records.'),
      '#default_value' => $config->get('batch_limit'),
      '#weight' => 5,
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => 'Active Debug Mode?',
      '#weight' => 6,
      '#description' => 'The debug mode will to show the trace of some API calls during the request.',
      '#default_value' => $state->get('am_net.settings.debug', FALSE),
    ];

    $form['disable_profile_update_email'] = [
      '#type' => 'checkbox',
      '#title' => 'Disable profile update email trigger?',
      '#weight' => 7,
      '#description' => 'Check this box if you need to temporarily disable the profile update email trigger.',
      '#default_value' => $state->get('am_net.settings.disable_profile_update_email', FALSE),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $config = $this->config('am_net.settings');

    if (isset($values['api_prod_base_url'])) {
      $config->set('api_prod_base_url', $values['api_prod_base_url']);
    }

    if (isset($values['api_prod_auth_user'])) {
      $config->set('api_prod_auth_user', $values['api_prod_auth_user']);
    }

    if (isset($values['api_prod_auth_key']) && !empty($values['api_prod_auth_key'])) {
      $config->set('api_prod_auth_key', $values['api_prod_auth_key']);
    }

    if (isset($values['api_dev_base_url'])) {
      $config->set('api_dev_base_url', $values['api_dev_base_url']);
    }

    if (isset($values['api_dev_auth_user'])) {
      $config->set('api_dev_auth_user', $values['api_dev_auth_user']);
    }

    if (isset($values['api_dev_auth_key']) && !empty($values['api_dev_auth_key'])) {
      $config->set('api_dev_auth_key', $values['api_dev_auth_key']);
    }

    if (isset($values['connection_environment'])) {
      $config->set('connection_environment', $values['connection_environment']);
    }

    if (isset($values['batch_limit'])) {
      $config->set('batch_limit', $values['batch_limit']);
    }

    $state = \Drupal::state();
    if (isset($values['cron'])) {
      $state->set('am_net.settings.cron', $values['cron']);
    }

    if (isset($values['debug'])) {
      $state->set('am_net.settings.debug', $values['debug']);
    }

    if (isset($values['disable_profile_update_email'])) {
      $state->set('am_net.settings.disable_profile_update_email', $values['disable_profile_update_email']);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
