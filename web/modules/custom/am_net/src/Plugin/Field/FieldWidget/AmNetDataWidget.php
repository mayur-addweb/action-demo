<?php

namespace Drupal\am_net\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'amnet_data' widget.
 *
 * @FieldWidget(
 *   id = "amnet_data",
 *   label = @Translation("AM.net Data"),
 *   field_types = {
 *     "amnet_data"
 *   }
 * )
 */
class AmNetDataWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    return $element;
  }

}
