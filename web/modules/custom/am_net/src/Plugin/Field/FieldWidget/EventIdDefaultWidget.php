<?php

namespace Drupal\am_net\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'amnet_event_id_default' widget.
 *
 * @FieldWidget(
 *   id = "amnet_event_id_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "amnet_event_id"
 *   }
 * )
 */
class EventIdDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'amnet_event_id';
    if (!$items[$delta]->isEmpty()) {
      $element['#default_value'] = [
        'code' => $items[$delta]->code,
        'year' => $items[$delta]->year,
      ];
    }

    return $element;
  }

}
