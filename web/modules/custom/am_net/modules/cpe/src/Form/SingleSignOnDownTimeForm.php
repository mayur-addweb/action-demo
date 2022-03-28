<?php

namespace Drupal\am_net_cpe\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Cache\Cache;

/**
 * Confirm form for: Single sign-on Downtime.
 */
class SingleSignOnDownTimeForm extends FormBase {

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Returns the state storage service.
   *
   * @return \Drupal\Core\State\StateInterface
   *   The state service.
   */
  public function state() {
    if (!$this->state) {
      $this->state = \Drupal::state();
    }
    return $this->state;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_cpe_sso_downtime_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['enable_downtime'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable SSO Downtime'),
      '#default_value' => am_net_cpe_is_sso_downtime_active(),
      '#description' => $this->t('If enabled, the Virtual conference pages will be publicly accessible and require the user to provide an email to shows the Sessions section.'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled = $form_state->getValue(['enable_downtime']);
    $enabled = boolval($enabled);
    $this->state()->set('am_net_cpe.sso_downtime', $enabled);
    Cache::invalidateTags(['sso_downtime_tag']);
    $this->messenger()->addMessage('Changes saved!.');
  }

}
