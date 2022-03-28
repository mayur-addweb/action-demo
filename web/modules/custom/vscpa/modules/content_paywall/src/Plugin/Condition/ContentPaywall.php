<?php

namespace Drupal\content_paywall\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Content Paywall' condition.
 *
 * @Condition(
 *   id = "check_content_paywall_access",
 *   label = @Translation("Content Paywall"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", required = FALSE, label = @Translation("Current User")),
 *     "node" = @ContextDefinition("entity:node", required = FALSE, label = @Translation("Current Node"))
 *   }
 * )
 */
class ContentPaywall extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['check_content_paywall_access'] = [
      '#type' => 'radios',
      '#title' => $this->t('Check Access on Premium content?'),
      '#default_value' => $this->configuration['check_content_paywall_access'],
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#description' => $this->t('If you select no option, the condition will evaluate to NO for all users.'),
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
      'check_content_paywall_access' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['check_content_paywall_access'] = $form_state->getValue('check_content_paywall_access');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $check_content_paywall_access = $this->configuration['check_content_paywall_access'];
    if ($check_content_paywall_access) {
      return $this->t('Check Access on Premium content.');
    }
    else {
      return $this->t('No check Access.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $check_access = $this->configuration['check_content_paywall_access'];
    if (empty($check_access)) {
      return TRUE;
    }
    $node = $this->getContextValue('node');
    if (!$node) {
      return TRUE;
    }
    return \Drupal::service('content_paywall.helper')->accessCheck($node);
  }

}
