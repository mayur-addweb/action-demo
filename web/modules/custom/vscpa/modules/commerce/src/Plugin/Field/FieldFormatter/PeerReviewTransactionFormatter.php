<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'am_net_peer_review_transaction' formatter.
 *
 * @FieldFormatter(
 *   id = "am_net_peer_review_transaction",
 *   module = "am_net_peer_review_transaction",
 *   label = @Translation("AM.net Peer Review Transaction formatter"),
 *   field_types = {
 *     "am_net_peer_review_transaction"
 *   }
 * )
 */
class PeerReviewTransactionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    return $elements;
  }

}
