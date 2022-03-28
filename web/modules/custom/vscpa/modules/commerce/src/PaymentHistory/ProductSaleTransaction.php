<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

use Drupal\Core\Url;
use Drupal\am_net\AMNetEntityTypeContext;
use Drupal\am_net\AMNetEntityTypesInterface;

/**
 * Defines object that represents Product Sale Transaction.
 */
class ProductSaleTransaction extends Transaction {

  /**
   * The AM.net order data.
   *
   * @var array
   */
  protected $order = [
    'ID' => '',
    'Firm' => '',
    'CompanyCode' => '',
    'OrderNumber' => '',
    'PurchaseOrderDate' => '',
    'AddressPreferenceCode' => '',
    'OrderStatusCode' => '',
    'Paid' => '',
    'Items' => [],
  ];

  /**
   * Set Target Entities IDs.
   */
  public function setTargetEntitiesIds() {
    $items = $this->order['Items'] ?? NULL;
    if (empty($items)) {
      return;
    }
    foreach ($items as $delta => $item) {
      $product_code = $item['ProductCode'] ?? NULL;
      if (empty($product_code)) {
        continue;
      }
      $this->targetEntityIDs[] = ['ProductCode' => $product_code];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeContext() {
    $data = [
      'type' => AMNetEntityTypesInterface::COURSE,
      'is_statically_cacheable' => TRUE,
    ];
    return new AMNetEntityTypeContext($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlacedTime() {
    $default = time();
    $added_date = $this->order['PurchaseOrderDate'] ?? NULL;
    if (empty($added_date)) {
      return $default;
    }
    return strtotime($added_date);
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTemplate($title = NULL, array $attributes = []) {
    $template = NULL;
    if (!empty($title)) {
      $title = trim($title);
      $data = $this->parseOrderItemAttributes($attributes);
      $template = "<div class='order-item-summary' {$data}><strong class='label inline'>Course:</strong> {$title}.</div>";
    }
    return $template;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentRefNumber() {
    $payments = NULL;
    $items = $this->order['Transactions'] ?? [];
    foreach ($items as $delta => $item) {
      $payment_ref = $item['PaymentRef'] ?? NULL;
      if (empty($payment_ref)) {
        continue;
      }
      $payments .= "<div class='order-item-summary'>{$payment_ref}</div>";
    }
    return $payments;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    $entities = $this->getReferencedEntities();
    if (empty($entities)) {
      return [];
    }
    $links = [];
    // Add Receipt link.
    $links[] = [
      'title' => t('<span class="glyphicon glyphicon-file" aria-hidden="true"></span> Contact cpe@vscpa.com for your receipt.'),
      'attributes' => [
        'class' => [
          'receipt_link',
        ],
      ],
    ];
    $items = $this->order['Items'] ?? [];
    foreach ($items as $delta => $item) {
      $pair_info = $this->getPairInfo($item);
      if (empty($pair_info)) {
        continue;
      }
      $completion_date = $pair_info['SelfStudyCompletion'] ?? NULL;
      $pr_code = $pair_info['ProductCode'] ?? NULL;
      $names_id = $this->order['ID'] ?? NULL;
      if (empty($completion_date) || empty($pr_code) || empty($names_id)) {
        continue;
      }
      $pr_code = trim($pr_code);
      $names_id = trim($names_id);
      $completion_date = trim($completion_date);
      $params = [
        'id' => $names_id,
        'completion_date' => $completion_date,
        'pr_code' => $pr_code,
      ];
      $links[] = [
        'title' => t('<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> CPE Certificate'),
        'url' => Url::fromRoute('vscpa_commerce.download_vac_self_study_certificate', $params),
        'attributes' => [
          'class' => [
            'certificate_link',
          ],
          'data-completion-date' => $completion_date,
          'data-pr-code' => $pr_code,
          'data-name-id' => $names_id,
        ],
      ];
    }
    if (empty($links)) {
      return [];
    }
    return [
      '#theme' => 'links',
      '#attributes' => [
        'class' => [
          'order-history-operations',
        ],
      ],
      '#links' => $links,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCredits() {
    $credits = 0;
    $items = $this->order['Items'] ?? [];
    foreach ($items as $delta => $item) {
      for ($i = 1; $i <= 6; $i++) {
        $key = "CreditCategory{$i}Credits";
        $credit = $item[$key] ?? 0;
        $credits += $credit;
      }
    }
    return $credits;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitleFromReferencedEntity(array $entity = []) {
    return $entity['Description'] ?? NULL;
  }

  /**
   * Get the product sale pair info.
   *
   * @param array $entity
   *   The array with the product sale info.
   *
   * @return bool|array
   *   Return the completion date & the product code, otherwise FALSE.
   */
  public function getPairInfo(array $entity = []) {
    if (empty($entity)) {
      return FALSE;
    }
    $self_study_completion = $entity['SelfStudyCompletion'] ?? FALSE;
    $product_code = $entity['ProductCode'] ?? FALSE;
    if (empty($self_study_completion) || empty($product_code)) {
      return FALSE;
    }
    $product_code = trim($product_code);
    // The Self Study certificate is a custom AM.Net report for VSCPA.
    // It will only generate a certificate for products with the phrase
    // "disclosures" in the product name, or if the product code starts
    // with "S-" and is 5 characters long.
    $start_with_preffix = substr($product_code, 0, 2) === 'S-';
    if (!$start_with_preffix) {
      return FALSE;
    }
    if (strlen($product_code) != 5) {
      return FALSE;
    }
    return [
      'SelfStudyCompletion' => $self_study_completion,
      'ProductCode' => $product_code,
    ];
  }

  /**
   * Get the completion date related to a given product sale.
   *
   * @param array $entity
   *   The array with the product sale info.
   *
   * @return bool|string
   *   Return the completion date, otherwise FALSE.
   */
  public function getCompletionDates(array $entity = []) {
    $items = $entity['Items'] ?? NULL;
    if (empty($items)) {
      return FALSE;
    }
    foreach ($items as $key => $item) {
      $self_study_completion = $item['SelfStudyCompletion'] ?? FALSE;
      if (empty($self_study_completion)) {
        continue;
      }
      return $self_study_completion;
    }
    return FALSE;
  }

  /**
   * Checks if the given product sale has certificate available.
   *
   * @param array $entity
   *   The array with the product sale info.
   *
   * @return bool
   *   Return TRUE if the given product sale has certificate available,
   *   otherwise FALSE.
   */
  public function hasAvailableCertificate(array $entity = []) {
    // A certificate should only be available if the 'SS Completed' is
    // populated.
    return $this->isSsCompletedPopulated($entity);
  }

  /**
   * Checks if the SS Completed field related to the product sale was populated.
   *
   * @param array $entity
   *   The array with the product sale info.
   *
   * @return bool
   *   Return TRUE if the product has SSCompleted filled out, otherwise FALSE.
   */
  public function isSsCompletedPopulated(array $entity = []) {
    $items = $entity['Items'] ?? NULL;
    if (empty($items)) {
      return FALSE;
    }
    foreach ($items as $key => $item) {
      $self_study_completion = $item['SelfStudyCompletion'] ?? FALSE;
      if (empty($self_study_completion)) {
        continue;
      }
      return TRUE;
    }
    return FALSE;
  }

}
