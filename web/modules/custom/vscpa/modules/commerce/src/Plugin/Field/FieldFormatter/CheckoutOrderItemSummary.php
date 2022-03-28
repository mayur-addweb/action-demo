<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'checkout order item summary' formatter.
 *
 * @FieldFormatter(
 *   id = "checkout_order_item_summary",
 *   label = @Translation("Checkout Order Item Summary"),
 *   description = @Translation("Display the summary of the Order Item."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class CheckoutOrderItemSummary extends EntityReferenceFormatterBase {

  use OrderItemFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['link'] = [
      '#title' => t('Link label to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('link') ? t('Link to the referenced entity') : t('No link');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $items->getEntity();
    $summary = $this->getCheckoutOrderItemSummary($order_item);
    $elements[0] = ['#markup' => $summary];
    $elements[0]['#cache']['tags'] = $order_item->getCacheTags();
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view', NULL, TRUE);
  }

}
