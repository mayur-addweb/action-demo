<?php

namespace Drupal\am_net_membership\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'My Account' condition.
 *
 * @Condition(
 *   id = "user_my_account",
 *   label = @Translation("My Account"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", required = FALSE, label = @Translation("User"))
 *   }
 * )
 */
class MyAccount extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['my_account'] = [
      '#type' => 'radios',
      '#title' => $this->t('When the user is authenticated in his account.'),
      '#default_value' => $this->configuration['my_account'],
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
    ];
    $form = parent::buildConfigurationForm($form, $form_state);
    hide($form['negate']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'my_account' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['my_account'] = $form_state->getValue('my_account');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['negate'])) {
      return $this->t('Not use the <i>My Account</i> condition');
    }
    else {
      return $this->t('Use the <i>My Account</i> condition');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['my_account'])) {
      return TRUE;
    }
    /* @var $user \Drupal\user\UserInterface; */
    $user = $this->getContextValue('user');
    /* @var $path_user \Drupal\user\UserInterface; */
    $path_user = \Drupal::service('current_route_match')->getParameter('user');
    if (!$user || !$path_user) {
      return TRUE;
    }
    $path_user_id = is_string($path_user) ? $path_user : $path_user->id();
    return ($path_user_id == $user->id());
  }

}
