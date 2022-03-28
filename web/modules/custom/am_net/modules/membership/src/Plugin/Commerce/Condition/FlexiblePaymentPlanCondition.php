<?php

namespace Drupal\am_net_membership\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the 'Flexible Payment Plan' condition for orders.
 *
 * @CommerceCondition(
 *   id = "flexible_payment_plan",
 *   label = @Translation("Flexible Payment Plan"),
 *   display_label = @Translation("Exclude orders that contains Flexible Payment Plan."),
 *   category = @Translation("Flexible Payment Plan"),
 *   entity_type = "commerce_order",
 * )
 */
class FlexiblePaymentPlanCondition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'flexible_payment_plan' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['flexible_payment_plan']['#description'] = $this->t("Flexible Payment Plan Condition.");
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $exclude_payment_plan = $values['flexible_payment_plan'] ?? FALSE;
    $exclude_payment_plan = (bool) $exclude_payment_plan;
    $this->configuration['flexible_payment_plan'] = $exclude_payment_plan;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $exclude_payment_plan = $this->configuration['flexible_payment_plan'] ?? FALSE;
    $exclude_payment_plan = (bool) $exclude_payment_plan;
    if ($exclude_payment_plan == FALSE) {
      return FALSE;
    }
    $helper = \Drupal::service('am_net_membership.dues_payment_plan.helper');
    return $exclude_payment_plan ? (!$helper->orderContainsActivePaymentPlan($order)) : TRUE;
  }

}
