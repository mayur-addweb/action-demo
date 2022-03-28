<?php

namespace Drupal\am_net_firms\Plugin\EntityReferenceSelection;

use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Provides Firm By Field Entity reference selector.
 *
 * @EntityReferenceSelection(
 *   id = "default:firm_by_address",
 *   label = @Translation("Firm by Address selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "default",
 *   weight = 3
 * )
 */
class FirmByFieldSelection extends TermSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $terms = explode(' ', $match);
    if (count($terms) == 1) {
      $query = parent::buildEntityQuery($match, $match_operator);
      return $query;
    }
    $name = [];
    for ($i = 0; $i <= 3; $i++) {
      $input = $terms[$i] ?? NULL;
      if (!empty($input)) {
        $name[] = $input;
      }
    }
    $firm_name = implode(' ', $name);
    $query = parent::buildEntityQuery($firm_name, $match_operator);
    // Check By Country.
    $country = $terms[4] ?? NULL;
    if (!empty($country)) {
      // Search By Field Address - country_code.
      $field_name = 'field_address.%delta.country_code';
      $query->condition($field_name, $country, 'CONTAINS');
    }
    // Check By Administrative area.
    $administrative_area = $terms[5] ?? NULL;
    if (!empty($administrative_area)) {
      // Search By Field Address - administrative_area.
      $field_name = 'field_address.%delta.administrative_area';
      $query->condition($field_name, $administrative_area, 'CONTAINS');
    }
    // Check By Locality.
    $locality = $terms[6] ?? NULL;
    if (!empty($locality)) {
      // Search By Field Address - locality.
      $field_name = 'field_address.%delta.locality';
      $query->condition($field_name, $locality, 'CONTAINS');
    }

    return $query;
  }

}
