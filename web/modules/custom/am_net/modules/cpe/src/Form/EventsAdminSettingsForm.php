<?php

namespace Drupal\am_net_cpe\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * AM.net Events settings for this site.
 */
class EventsAdminSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_cpe_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['digital_rewind_event'] = [
      '#type' => 'fieldset',
      '#title' => t('Digital Rewind Event.'),
      '#weight' => 1,
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['production_connection_details'],
      ],
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $default_value = \Drupal::state()->get('am_net_cpe.settings.digital.rewind.node_id', NULL);
    $form['digital_rewind_event']['node_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node ID'),
      '#size' => 20,
      '#description' => $this->t('Enter the Virtual conference base template node ID'),
      '#default_value' => $default_value,
    ];
    $form['digital_rewind_event']['actions'] = [
      '#type' => 'actions',
    ];
    $form['digital_rewind_event']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['digital_rewind_event', 'node_id']);
    \Drupal::state()->set('am_net_cpe.settings.digital.rewind.node_id', $values);
  }

}
