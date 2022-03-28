<?php

namespace Drupal\vscpa_commerce\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\vscpa_commerce\PeerReview\PeerReviewInfo;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface;

/**
 * Plugin implementation of the 'am_net_peer_review_transaction' field type.
 *
 * @FieldType(
 *   id = "am_net_peer_review_transaction",
 *   label = @Translation("AM.net Peer Review Transaction"),
 *   module = "vscpa_commerce",
 *   description = @Translation("AM.net Peer Review Transaction"),
 *   category = @Translation("Commerce"),
 *   default_widget = "am_net_peer_review_transaction",
 *   default_formatter = "am_net_peer_review_transaction"
 * )
 */
class PeerReviewTransaction extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'firm_id' => [
          'description' => 'The Firm ID.',
          'type' => 'varchar',
          'length' => 20,
        ],
        'aicpa_number' => [
          'description' => 'The AICPA Number.',
          'type' => 'varchar',
          'length' => 20,
        ],
        'contact_email' => [
          'description' => 'The Contact Email.',
          'type' => 'varchar',
          'length' => 100,
        ],
        'contact_phone' => [
          'description' => 'The Contact Phone.',
          'type' => 'varchar',
          'length' => 100,
        ],
        'previous_billing_code' => [
          'description' => 'The Previous Billing Class Code.',
          'type' => 'varchar',
          'length' => 5,
        ],
        'new_billing_code' => [
          'description' => 'The New Billing Class Code.',
          'type' => 'varchar',
          'length' => 5,
        ],
        'firm_size_changes' => [
          'description' => '1 or 0 Flag that determines if this transaction includes Firm Size changes.',
          'type' => 'int',
          'size' => 'tiny',
        ],
        'items' => [
          'description' => 'Stores Billing Items data.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
        'data' => [
          'description' => 'Stores the Peer Review raw data.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // The Firm ID.
    $properties['firm_id'] = DataDefinition::create('string')
      ->setLabel(t('The Firm ID.'))
      ->setRequired(FALSE);
    // The AICPA Number.
    $properties['aicpa_number'] = DataDefinition::create('string')
      ->setLabel(t('The AICPA Number.'))
      ->setRequired(FALSE);
    // The Contact Email.
    $properties['contact_email'] = DataDefinition::create('string')
      ->setLabel(t('The Contact Email.'))
      ->setRequired(FALSE);
    // The Contact Phone.
    $properties['contact_phone'] = DataDefinition::create('string')
      ->setLabel(t('The Contact Phone.'))
      ->setRequired(FALSE);
    // The Previous Billing Class Code.
    $properties['previous_billing_code'] = DataDefinition::create('string')
      ->setLabel(t('The Previous Billing Class Code.'))
      ->setRequired(FALSE);
    // The New Billing Class Code.
    $properties['new_billing_code'] = DataDefinition::create('string')
      ->setLabel(t('The New Billing Class Code.'))
      ->setRequired(FALSE);
    // The Flag: Firm size changes?.
    $properties['firm_size_changes'] = DataDefinition::create('integer')
      ->setLabel(t('The Flag: Firm size changes?.'))
      ->setRequired(FALSE);
    // Additional Billing Items Info.
    $properties['items'] = MapDataDefinition::create()
      ->setLabel(t('Billing Items data'))
      ->setDescription(t('Stores Billing Items data.'));
    // Additional Peer Review raw data.
    $properties['data'] = MapDataDefinition::create()
      ->setLabel(t('The Peer Review raw data'))
      ->setDescription(t('Stores the Peer Review raw data.'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->aicpa_number);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Allow callers to pass a Price value object as the field item value.
    if ($values instanceof PeerReviewInfoInterface) {
      $info = $values;
      $values = [
        'firm_id' => $info->getFirmId(),
        'aicpa_number' => $info->getAmNetAicpaNumber(),
        'contact_email' => $info->getAmNetContactEmail(),
        'contact_phone' => $info->getAmNetContactPhone(),
        'previous_billing_code' => $info->getPreviousBillingCode(),
        'new_billing_code' => $info->getNewBillingCode(),
        'firm_size_changes' => $info->hasFirmSizeChanges(),
        'items' => $info->getItems(),
        'data' => $info->toArray(),
      ];
    }
    if (!empty($values)) {
      parent::setValue($values, $notify);
    }
  }

  /**
   * Gets the Peer Review Info value object for the current field item.
   *
   * @return \Drupal\vscpa_commerce\PeerReview\PeerReviewInfoInterface
   *   The Peer Review Info object.
   */
  public function toPeerReviewInfo() {
    $info = new PeerReviewInfo();
    $info->setFirmId($this->firm_id);
    $info->setData($this->data);
    $info->setPreviousBillingCode($this->previous_billing_code);
    $info->setNewBillingCode($this->new_billing_code);
    $info->setFirmSizeChanges($this->firm_size_changes);
    return $info;
  }

}
