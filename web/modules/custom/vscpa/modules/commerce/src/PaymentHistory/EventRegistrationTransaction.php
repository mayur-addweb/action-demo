<?php

namespace Drupal\vscpa_commerce\PaymentHistory;

use Drupal\Core\Url;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\am_net\AMNetEntityTypeContext;
use Drupal\am_net\AMNetEntityTypesInterface;

/**
 * Defines object that represents Event Registration Transaction.
 */
class EventRegistrationTransaction extends Transaction {

  /**
   * The AM.net order data.
   *
   * @var array
   */
  protected $order = [
    'NamesId' => '',
    'EventCode' => '',
    'EventYear' => '',
    'MaterialsSentDate' => '',
    'RegistrationDate' => '',
    'PrePrepMailingDate' => '',
    'ConfirmationDate' => '',
    'Fees' => '',
    'Paid' => '',
    'Note' => '',
    'RegistrationSourceCode' => '',
    'MarketingSourceCode' => '',
    'AddedBy' => '',
    'Added' => '',
    'RegistrationStatusCode' => '',
    'TotalCPECredits' => '',
    'Credits' => [],
    'FirmRegistration' => '',
    'LastInvoiceDate' => '',
    'AcknowledgementDate' => '',
  ];

  /**
   * Set Target Entities IDs.
   */
  public function setTargetEntitiesIds() {
    $id = [
      'EventCode' => $this->order['EventCode'] ?? NULL,
      'EventYear' => $this->order['EventYear'] ?? NULL,
    ];
    $this->targetEntityIDs[] = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeContext() {
    $data = [
      'type' => AMNetEntityTypesInterface::EVENT,
      'is_statically_cacheable' => TRUE,
    ];
    return new AMNetEntityTypeContext($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlacedTime() {
    $default = time();
    $added_date = $this->order['Added'] ?? NULL;
    if (empty($added_date)) {
      return $default;
    }
    return strtotime($added_date);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributesFromReferencedEntity(array $entity = []) {
    $attributes = [];
    $attributes['data-event-year'] = $entity['Year'] ?? NULL;
    $attributes['data-event-code'] = $entity['Code'] ?? NULL;
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTemplate($title = NULL, array $attributes = []) {
    $template = NULL;
    if (!empty($title)) {
      $title = trim($title);
      $data = $this->parseOrderItemAttributes($attributes);
      $template = "<div class='order-item-summary' {$data}><strong class='label inline'>Event:</strong> {$title}.</div>";
    }
    return $template;
  }

  /**
   * Checks if the event date has already occurred.
   *
   * @param array $entity
   *   The array with the event info.
   *
   * @return bool
   *   Return TRUE if the event date has already occurred, otherwise FALSE.
   */
  public function eventDateHasAlreadyOccurred(array $entity = []) {
    $division = $entity['DivisionCode'] ?? NULL;
    if (am_net_is_self_study($division)) {
      $end_date = $entity['BeginDate'] ?? NULL;
    }
    else {
      $end_date = $entity['EndDate'] ?? NULL;
    }
    if (empty($end_date)) {
      return FALSE;
    }
    $time = strtotime($end_date);
    $end_date_time = new DrupalDateTime();
    $end_date_time->setTimestamp($time);
    $now = new DrupalDateTime();
    return ($now > $end_date_time);
  }

  /**
   * Checks if the Registrations Status is Attended.
   *
   * @param array $entity
   *   The array with the event info.
   *
   * @return bool
   *   Return TRUE if the Registrations Status is Attended, otherwise FALSE.
   */
  public function isRegistrationsStatusAttended(array $entity = []) {
    // "R" means Registered. In our UI if the event date has already occurred
    // we change the display text from Registered to Attended for the "R" code.
    $code = $this->order['RegistrationStatusCode'] ?? NULL;
    return ($code == 'R') && ($this->eventDateHasAlreadyOccurred($entity));
  }

  /**
   * Checks if the Reconcile Date related to the event was populated.
   *
   * @param array $entity
   *   The array with the event info.
   *
   * @return bool
   *   Return TRUE if the event has Reconcile Date, otherwise FALSE.
   */
  public function isReconcileDatePopulated(array $entity = []) {
    $date = $entity['ReconcileDate'] ?? NULL;
    return !empty($date);
  }

  /**
   * Checks if the Completion Date related to the event was populated.
   *
   * @param array $entity
   *   The array with the event info.
   *
   * @return bool
   *   Return TRUE if the event has Completion Date, otherwise FALSE.
   */
  public function isCompletionDatePopulated(array $entity = []) {
    $date = $this->order['CompletionDate'] ?? NULL;
    return !empty($date);
  }

  /**
   * Checks if the Registrations Status is Attended.
   *
   * @param array $entity
   *   The array with the event info.
   *
   * @return bool
   *   Return TRUE if the Registrations Status is Attended, otherwise FALSE.
   */
  public function hasAvailableCertificate(array $entity = []) {
    // 1. A certificate should only be available the registrant has "Attended"
    // as their status for the event.
    if (!$this->isRegistrationsStatusAttended($entity)) {
      return FALSE;
    }
    $division = $entity['DivisionCode'] ?? NULL;
    if (am_net_is_self_study($division)) {
      // For self-Study events we check that CompletionDate field is populated.
      return $this->isCompletionDatePopulated($entity);
    }
    else {
      // The Reconcile Date date field is populated.
      return $this->isReconcileDatePopulated($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEventRegistrationTransactions() {
    $data = [
      'type' => AMNetEntityTypesInterface::EVENT_REGISTRATION,
      'is_statically_cacheable' => TRUE,
    ];
    $context = new AMNetEntityTypeContext($data);
    // Set the ID.
    $name_id = $this->order['NamesId'] ?? NULL;
    if (!empty($name_id)) {
      $name_id = trim($name_id);
    }
    $id = [
      'EventCode' => $this->order['EventCode'] ?? NULL,
      'EventYear' => $this->order['EventYear'] ?? NULL,
      'id' => $name_id,
    ];
    $transactions = \Drupal::service('am_net.entity.repository')->getEntity($id, $context);
    if (empty($transactions)) {
      return NULL;
    }
    return $transactions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentRefNumber() {
    $transactions = $this->getEventRegistrationTransactions();
    $items = $transactions['Transactions'] ?? [];
    if (empty($items)) {
      return NULL;
    }
    $payments = NULL;
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
  public function addActionLinkCpeCertificate(array $entity) {
    if (!$this->hasAvailableCertificate($entity)) {
      return NULL;
    }
    $event_code = $entity['Code'] ?? NULL;
    $event_year = $entity['Year'] ?? NULL;
    $names_id = $this->order['NamesId'] ?? NULL;
    if (empty($event_code) || empty($event_year) || empty($names_id)) {
      return NULL;
    }
    $names_id = trim($names_id);
    $event_year = trim($event_year);
    $event_code = trim($event_code);
    $params = [
      'id' => $names_id,
      'event_code' => $event_code,
      'event_year' => $event_year,
    ];
    return [
      'title' => t('<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> CPE Certificate'),
      'url' => Url::fromRoute('vscpa_commerce.download_cpe_certificate', $params),
      'attributes' => [
        'class' => [
          'certificate_link',
        ],
        'data-event-code' => $event_code,
        'data-event-year' => $event_year,
        'data-name-id' => $names_id,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addActionLinkCpeReceipt(array $entity) {
    $event_code = $entity['Code'] ?? NULL;
    $event_year = $entity['Year'] ?? NULL;
    $names_id = $this->order['NamesId'] ?? NULL;
    if (empty($event_code) || empty($event_year) || empty($names_id)) {
      return NULL;
    }
    $names_id = trim($names_id);
    $event_year = trim($event_year);
    $event_code = trim($event_code);
    $params = [
      'id' => $names_id,
      'event_code' => $event_code,
      'event_year' => $event_year,
    ];
    return [
      'title' => t('<span class="glyphicon glyphicon-file" aria-hidden="true"></span> Receipt'),
      'url' => Url::fromRoute('vscpa_commerce.download_cpe_receipt', $params),
      'attributes' => [
        'class' => [
          'receipt_link',
        ],
        'data-event-code' => $event_code,
        'data-event-year' => $event_year,
        'data-name-id' => $names_id,
      ],
    ];
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
    foreach ($entities as $delta => $entity) {
      if (!$entity) {
        continue;
      }
      // Add Receipt link.
      $link = $this->addActionLinkCpeReceipt($entity);
      if (!empty($link)) {
        $links[] = $link;
      }
      // Add CPE Certificate link.
      $link = $this->addActionLinkCpeCertificate($entity);
      if (!empty($link)) {
        $links[] = $link;
      }
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
    return $this->order['TotalCPECredits'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitleFromReferencedEntity(array $entity = []) {
    return $entity['EventName'] ?? NULL;
  }

}
