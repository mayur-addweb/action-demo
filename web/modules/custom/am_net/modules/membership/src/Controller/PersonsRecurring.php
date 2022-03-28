<?php

namespace Drupal\am_net_membership\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Persons Recurring (development/debug) page.
 */
class PersonsRecurring extends ControllerBase {

  /**
   * Render a list of Persons Recurring payment profiles in the AM.net database.
   */
  public function render() {
    /** @var \UnleashedTech\AMNet\Api\Client $client */
    $client = \Drupal::service('am_net.client')->getClient();

    $content = [];
    $content['message'] = [
      '#markup' => '<br><h2>' . $this->t('List of (some) recurring payment profiles from the database of the Association Management System (AMS) AM.net.') . '</h2></br>',
    ];

    $headers = [
      t('NamesId'),
      t('ProfileId'),
      t('ProfileName'),
      t('RecurringPeriodCode'),
      t('ProfileStart'),
      t('ProfileEnd'),
      t('ReferenceTransationNumber'),
      t('ReferenceTransactionAdded'),
      t('CardExpires'),
      t('CardNumber'),
      t('Payor'),
    ];
    $rows = [];

    // @todo Make filterable by date.
    $personChanges = $client->get('PersonChanges', [
      'since' => '2017-09-28',
    ]);
    if ($personChanges->hasError()) {
      // Show user-friendly message.
      drupal_set_message(t('We can not process the list of PersonChanges at this time, please try it later, If the problem persists, contact the administrator.'), 'error');
      // Logs an error.
      \Drupal::logger('amt_net')->error($personChanges->getErrorMessage());
    }
    else {
      $result = $personChanges->getResult();
      $person_ids = array_map(function ($person) {
        return $person['Person']['NamesID'];
      }, $result);
      foreach ($person_ids as $id) {
        if ($payment_profiles = $client->get("Person/{$id}/recurring")->getResult()) {
          foreach ($payment_profiles as $profile) {
            $values = array_values([$id] + $profile);
            $column_count = count($headers);
            $rows[] = array_slice($values, 0, $column_count);
          }
        }
      }
    }

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => ['id' => 'persons-list'],
      '#empty' => t('No Persons available.'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;
    return $content;
  }

}
