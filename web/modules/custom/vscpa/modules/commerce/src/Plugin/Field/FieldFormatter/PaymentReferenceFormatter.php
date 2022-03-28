<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;

/**
 * Plugin implementation of the 'payment_reference_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "payment_reference_summary",
 *   label = @Translation("Payment Reference"),
 *   description = @Translation("Display the Payment Reference of the referenced order."),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class PaymentReferenceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /* @var \Drupal\commerce_order\Entity\OrderItemInterface $entity */
      if ($entity = $item->getEntity()) {
        if ($entity instanceof OrderInterface) {
          $reference = $this->getPayments($entity);
        }
        else {
          /* @var \Drupal\core\Entity\EntityInterface $entity */
          $reference = $entity->label();
        }
        if (empty($reference)) {
          continue;
        }
        $elements[$delta] = [
          '#markup' => $reference,
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
  public function getPayments(OrderInterface $order = NULL) {
    if (!$order) {
      return NULL;
    }
    try {
      /* @var \Drupal\commerce_payment\PaymentStorage $payment_storage */
      $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    }
    catch (InvalidPluginDefinitionException $e) {
      return NULL;
    }
    catch (PluginNotFoundException $e) {
      return NULL;
    }
    $payments = $payment_storage->loadMultipleByOrder($order);
    if (empty($payments)) {
      return NULL;
    }
    $items = [];
    /* @var \Drupal\commerce_payment\Entity\Payment $payment */
    foreach ($payments as $delta => $payment) {
      $remote_id = $payment->getRemoteId() ?: NULL;
      if (empty($remote_id)) {
        continue;
      }
      $ids = explode('|', $remote_id);
      $id = current($ids);
      $items[] = $id;
    }
    if (empty($items)) {
      return NULL;
    }
    return implode(',', $items);
  }

}
