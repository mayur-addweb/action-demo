<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'am_net_peer_review_transaction' widget.
 *
 * @FieldWidget(
 *   id = "am_net_peer_review_transaction",
 *   module = "am_net_peer_review_transaction",
 *   label = @Translation("AM.net Peer Review Transaction"),
 *   field_types = {
 *     "am_net_peer_review_transaction"
 *   }
 * )
 */
class PeerReviewTransactionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // This is a read-only type field, So we should not edit any info manually.
    $elements = [];
    return $elements;
  }

}
