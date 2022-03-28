<?php

namespace Drupal\am_net_user_profile\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the AMNet ID's condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_amnet_id",
 *   label = @Translation("Name ID's"),
 *   display_label = @Translation("Limit by Name ID's"),
 *   category = @Translation("Customer"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderAmNetId extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'name_ids' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $ids = $this->configuration['name_ids'];
    $values = NULL;
    if (!empty($ids)) {
      $values = implode(',', $ids);
    }
    $form['name_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Name ID's"),
      '#default_value' => $values,
      '#required' => TRUE,
      '#maxlength' => NULL,
      '#description' => $this->t("Please provide one or more Name IDs separated by a comma."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $items = strtolower($values['name_ids']);
    $values = explode(',', $items);
    $ids = [];
    if (!empty($values)) {
      foreach ($values as $delta => $value) {
        $current_id = trim($value);
        if (is_numeric($current_id)) {
          $ids[] = trim($current_id);
        }
      }
    }
    $this->configuration['name_ids'] = $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $name_ids = $this->configuration['name_ids'];
    if (empty($name_ids)) {
      return FALSE;
    }
    // Get the user associated with the Order.
    $user = $order->getCustomer();
    if (!$user) {
      return FALSE;
    }
    // Get the NameId.
    $am_net_name_id = $user->get('field_amnet_id')->getString();
    if (empty($am_net_name_id)) {
      return FALSE;
    }
    // Remove empty spaces.
    $am_net_name_id = trim($am_net_name_id);
    return in_array($am_net_name_id, $name_ids);
  }

}
