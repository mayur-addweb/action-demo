<?php

namespace Drupal\am_net_membership\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Member Status' condition.
 *
 * @Condition(
 *   id = "user_member_status",
 *   label = @Translation("Member Status"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 */
class MemberStatus extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['member_status'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('When the user has the Member Status'),
      '#default_value' => $this->configuration['member_status'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', am_net_membership_member_status_names()),
      '#description' => $this->t('If you select no member status, the condition will evaluate to TRUE for all users.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'member_status' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['member_status'] = array_filter($form_state->getValue('member_status'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Use the Member Status labels. They will be sanitized below.
    $member_status = array_intersect_key(am_net_membership_member_status_names(), $this->configuration['member_status']);
    if (count($member_status) > 1) {
      $member_status = implode(', ', $member_status);
    }
    else {
      $member_status = reset($member_status);
    }
    if (!empty($this->configuration['negate'])) {
      return $this->t('The user is not a member status of @member_status', ['@member_status' => $member_status]);
    }
    else {
      return $this->t('The user is a member status of @member_status', ['@member_status' => $member_status]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['member_status']) && !$this->isNegated()) {
      return TRUE;
    }
    $user = $this->getContextValue('user');
    $user_member_status = [$user->get('field_member_status')->getString()];
    return (bool) array_intersect($this->configuration['member_status'], $user_member_status);
  }

}
