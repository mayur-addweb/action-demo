<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'order_item_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "order_item_summary",
 *   label = @Translation("Order Item Summary"),
 *   description = @Translation("Display the summary of the referenced order item."),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class OrderItemSummaryFormatter extends FormatterBase {

  use OrderItemFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['link_to_entity'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the order'),
      '#default_value' => $this->getSetting('link_to_entity'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /* @var \Drupal\commerce_order\Entity\OrderItemInterface $entity */
      if ($entity = $item->getEntity()) {
        $elements[$delta] = [
          '#markup' => $this->getEntitySummary($entity, TRUE),
          '#cache' => [
            'tags' => $entity->getCacheTags(),
          ],
        ];
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'commerce_order_item';
  }

}
