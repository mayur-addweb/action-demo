<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldFormatter;

use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'Registrant reference summary' formatter.
 *
 * @FieldFormatter(
 *   id = "registrant_entity_reference_summary",
 *   label = @Translation("Registrant Entity Summary"),
 *   description = @Translation("Display the summary of the Registrant related to the order item."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class RegistrantSummaryFormatter extends EntityReferenceFormatterBase {

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
    if (!$order_item->hasField('field_user')) {
      return $elements;
    }
    $field = $order_item->get('field_user');
    if ($field->isEmpty()) {
      return $elements;
    }
    $users = $field->referencedEntities();
    $user = current($users);
    if (!$user || !($user instanceof UserInterface)) {
      return $elements;
    }
    $summary = $this->getUserEntitySummary($user);
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
