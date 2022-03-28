<?php

namespace Drupal\vscpa_sso\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class Event Registrants Report Controller.
 *
 *  Returns responses Order with Sync Issues.
 */
class EventRegistrantsReportController extends ControllerBase {

  /**
   * Generates an overview table of older revisions of a Event session.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function list(Request $request) {
    // Get Parameters.
    $event_code = $request->query->get('event-code');
    $event_year = $request->query->get('event-year');
    if (empty($event_code) || empty($event_year)) {
      $message = $this->t('Please provide a valid event code and a valid event year.');
      $this->messenger()->addWarning($message);
      return [];
    }
    $store_key = "event_generate_registrants_report.$event_year.$event_code";
    $report = \Drupal::state()->get($store_key);
    if (empty($report)) {
      $message = "No event registrant report found for the provided event code($event_code/$event_year), Please ask Adrian to generate the report.";
      $this->messenger()->addWarning($message);
      return [];
    }
    $good_accounts = $report['good_accounts'] ?? [];
    $count_good_accounts = count($good_accounts);
    $bad_accounts = $report['bad_accounts'] ?? [];
    $count_bad_accounts = count($bad_accounts);
    $total_accounts = $count_good_accounts + $count_bad_accounts;
    // Build the array of rows.
    $header = [
      $this->t('Name ID'),
      $this->t('Drupal User ID'),
      $this->t('Drupal Username'),
      $this->t('Drupal Email'),
      $this->t('AmNet email'),
      $this->t('Gluu email'),
      $this->t('Gluu # Accounts'),
      $this->t('Gluu ID(s)'),
      $this->t('Status'),
    ];
    $build['title'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">Event Registrants Report for: ' . $event_code . '/' . $event_year . '</h3>',
    ];
    $summary = '<div>Total # of registrants:<strong> ' . $total_accounts . '</strong></div>';
    $summary .= '<div>Total # of accounts with issues:<strong> ' . $count_bad_accounts . '</strong></div>';
    $summary .= '<div>Total # of accounts in good shape:<strong> ' . $count_good_accounts . '</strong></div>';
    $build['total_summary'] = [
      '#type' => 'item',
      '#markup' => $summary,
    ];
    // Add Bad Accounts report.
    $build['bad_accounts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Accounts with Issues'),
    ];
    $build['bad_accounts']['header'] = [
      '#type' => 'item',
      '#markup' => $this->t('The following is the list of accounts that have different emails and need correction.'),
    ];
    $build['bad_accounts']['table'] = [
      '#theme' => 'table',
      '#rows' => $bad_accounts,
      '#header' => $header,
      '#empty' => $this->t('There are no accounts with issues'),
    ];
    // Add Good Accounts report.
    $build['good_accounts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Accounts in good Shape'),
    ];
    $build['good_accounts']['header'] = [
      '#type' => 'item',
      '#markup' => $this->t('The following is the list of accounts in good shape.'),
    ];
    $build['good_accounts']['table'] = [
      '#theme' => 'table',
      '#rows' => $good_accounts,
      '#header' => $header,
      '#empty' => $this->t('There are no accounts on good shape'),
    ];
    return $build;
  }

}
