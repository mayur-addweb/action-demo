<?php

namespace Drupal\am_net_firms\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Implements the Find Employees Form.
 */
class FindEmployeesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_firms.employee_management_tool.find_employees';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL, TermInterface $firm = NULL) {
    $form['#id'] = 'add-employees-form';
    $form['#attributes'] = ['class' => ['add-employees-form']];
    // Keywords.
    $form['keywords'] = [
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => t('Name, Certification #, or Email Address'),
      ],
    ];
    $form['uid'] = [
      '#type' => 'hidden',
      '#value' => $user->id(),
    ];
    $form['firm_id'] = [
      '#type' => 'hidden',
      '#value' => $firm->id(),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
      '#weight' => 10,
      '#attributes' => [
        'class' => ['btn-sm'],
      ],

    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keywords = $form_state->getValue('keywords');
    $uid = $form_state->getValue('uid');
    $firm_id = $form_state->getValue('firm_id');
    $form_state->setRedirectUrl(Url::fromRoute('am_net_firms.employee_management_tool.find_employees', ['user' => $uid, 'firm' => $firm_id], ['query' => ['keywords' => $keywords]]));
  }

}
