<?php

namespace Drupal\vscpa_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Gluu server Connection settings for this site.
 */
class GluuSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vscpa_sso_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vscpa_sso.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vscpa_sso.settings');
    $form['connection_details'] = [
      '#type' => 'details',
      '#title' => t('Gluu Server Connection details'),
      '#weight' => 1,
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['gluu_connection_details'],
      ],
      'api_provider_url' => [
        '#type' => 'textfield',
        '#title' => t('Provider Url'),
        '#required' => TRUE,
        '#default_value' => $config->get('api_provider_url'),
      ],
      'api_client_id' => [
        '#type' => 'textfield',
        '#title' => t('Client ID'),
        '#required' => TRUE,
        '#default_value' => $config->get('api_client_id'),
        '#description' => t('Client ID, identifier (inum), example: @!XXXXX.XXXX.XXXX.XXX!0001!D716.B0F4!XXXX!201F.XXXX.XXXX.9E7D.'),
      ],
      'api_client_secret' => [
        '#type' => 'password',
        '#title' => t('Client Secret'),
        '#required' => TRUE,
        '#default_value' => $config->get('api_client_secret'),
        '#description' => t('Client Secret, auth password'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $config = $this->config('vscpa_sso.settings');

    if (isset($values['api_provider_url'])) {
      $config->set('api_provider_url', $values['api_provider_url']);
    }

    if (isset($values['api_client_id'])) {
      $config->set('api_client_id', $values['api_client_id']);
    }

    if (isset($values['api_client_secret']) && !empty($values['api_client_secret'])) {
      $config->set('api_client_secret', $values['api_client_secret']);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
