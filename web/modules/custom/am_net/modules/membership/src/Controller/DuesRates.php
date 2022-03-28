<?php

namespace Drupal\am_net_membership\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Dues Rates.
 */
class DuesRates extends ControllerBase {

  /**
   * Render a list of Dues Rates in the AM.net database.
   */
  public function render() {
    $content = [];

    $content['message'] = [
      '#markup' => '<br><h2>' . $this->t('List of Dues Rates from the database of the Association Management System (AMS) AM.net.') . '</h2></br>',
    ];

    $suffix = '';
    $headers = [
      t('Billing Class Code'),
      t('Dues Amount'),
      t('January') . $suffix,
      t('February') . $suffix,
      t('March') . $suffix,
      t('April') . $suffix,
      t('May') . $suffix,
      t('June') . $suffix,
      t('July') . $suffix,
      t('August') . $suffix,
      t('September') . $suffix,
      t('October') . $suffix,
      t('November') . $suffix,
      t('December') . $suffix,
    ];

    $rows = [];
    $rates = \Drupal::service('am_net.client')->getDuesRates();
    if ($rates == FALSE) {
      // Show user-friendly message.
      drupal_set_message(t('We can not process the list of Dues Rates at this time, please try it later, If the problem persists, contact the administrator.'), 'error');
    }
    else {
      $column_numbers = count($headers);
      foreach ($rates as $dues_rate) {
        $values = array_values($dues_rate);
        // Ensure that the length of the row is the same that the numbers
        // of columns of the table.
        $rows[] = array_slice($values, 0, $column_numbers);
      }
    }

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => ['id' => 'dues-rates-list'],
      '#empty' => t('No Dues Rates available.'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;
    return $content;
  }

}
