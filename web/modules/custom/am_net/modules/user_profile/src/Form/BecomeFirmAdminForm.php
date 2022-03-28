<?php

namespace Drupal\am_net_user_profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Implements the Become Firm Admin Form.
 */
class BecomeFirmAdminForm extends FormBase {

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The firm entity.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $firm;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_user_profile.become_firm_admin_form.';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL, TermInterface $firm = NULL) {
    if (empty($user) || empty($firm)) {
      return [];
    }
    $this->user = $user;
    $this->firm = $firm;
    $form['#id'] = 'become-firm-admin-form';
    $form['#attributes'] = ['class' => ['become-firm-admin-form']];
    $field_name = 'field_become_a_firm_admin';
    $form['become_firm_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do you want to become a firm administrator of the firm <i><u>@firm_name</u></i>?', ['@firm_name' => $firm->label()]),
      '#default_value' => ($user->get($field_name)->getString() == '1'),
    ];
    $form['summary'] = [
      '#type' => 'item',
      '#input' => FALSE,
      '#markup' => $this->t('Please allow 5 business days to process this request.'),
    ];
    // Actions.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send request'),
      '#button_type' => 'primary',
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $send_request = $form_state->getValue('become_firm_admin');
    $message = \Drupal::service('am_net_user_profile_update.helper')->sendRequestBecomeFirmAdmin($send_request, $this->user, $this->firm);
    drupal_set_message($message);
  }

}
