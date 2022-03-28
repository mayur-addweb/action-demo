<?php

namespace Drupal\vscpa_commerce\PaymentHistory\Controller;

use Drupal\user\UserInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller Payment History.
 *
 *  Returns list of all financial activity(Dues, Donations/Contributions, and
 *  Event Registrations and Product Sale) for a given user.
 */
class PaymentHistoryController extends ControllerBase {

  /**
   * Access Checker validation.
   *
   * @param \Drupal\user\UserInterface $user
   *   The given user.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|true
   *   TRUE if the users has access otherwise a redirect response.
   */
  public function accessChecker(UserInterface $user = NULL) {
    if (!$user) {
      return $this->redirect('user.page');
    }
    $current_user = $this->currentUser();
    $is_administrator = in_array('administrator', $current_user->getRoles());
    $is_firm_administrator = in_array('firm_administrator', $current_user->getRoles());
    $is_admin = $is_administrator || $is_firm_administrator;
    if (($this->currentUser()->id() != $user->id()) && !$is_admin) {
      return $this->redirect('user.page');
    }
    return TRUE;
  }

  /**
   * Generates an overview table of older revisions of a Event session.
   *
   * @param \Drupal\user\UserInterface $user
   *   The given user.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   An array as expected by drupal_render().
   */
  public function list(UserInterface $user = NULL) {
    $access = $this->accessChecker($user);
    if ($access instanceof RedirectResponse) {
      return $access;
    }
    $build = [];
    // Add Class Attributes.
    $build['#attributes']['class'][] = 'view-commerce-user-orders';
    // Add the Page Header.
    $this->addPageHeader($build);
    $build['#title'] = $this->t('List of financial activity.');
    /** @var \Drupal\vscpa_commerce\PaymentHistory\PaymentList $payments */
    $payments = \Drupal::service('vscpa_commerce.payment_history');
    $payments->buildPaymentList($user);
    // Build the array of rows.
    $transactions = [
      '#type' => 'table',
      '#header' => $payments->getHeader(),
      '#empty' => $this->t('There are no new financial activity.'),
      '#attributes' => [
        'class' => ['container-inline', 'payment-history-table'],
      ],
    ];
    /** @var \Drupal\vscpa_commerce\PaymentHistory\TransactionInterface $payment */
    foreach ($payments as $delta => $payment) {
      $transactions[$delta]['order_items'] = [
        '#markup' => $payment->getOrderItemsSummary(),
      ];
      $transactions[$delta]['date'] = [
        '#markup' => $payment->getPlacedDate(),
        '#wrapper_attributes' => [
          'class' => [
            'td-date',
          ],
        ],
      ];
      $transactions[$delta]['credit'] = [
        '#markup' => $payment->getCredits(),
        '#wrapper_attributes' => [
          'class' => [
            'td-credit',
          ],
        ],
      ];
      $transactions[$delta]['payment_ref'] = [
        '#markup' => $payment->getPaymentRefNumber(),
      ];
      $transactions[$delta]['total'] = [
        '#markup' => $payment->getTotal(),
      ];
      $transactions[$delta]['operations'] = $payment->getOperations();
    }
    $build['payments_table'] = $transactions;
    return $build;
  }

  /**
   * Generates an overview header for the Payment history page.
   *
   * @param array $render
   *   An array as expected by drupal_render().
   */
  public function addPageHeader(array &$render = []) {
    $content = "<div class='view-commerce-user-orders'><div class='view-header'><p>The Electronic Materials Archive is located underneath the payment table. Also, check back soon, as we'll be adding receipts for self-study courses!</p></div></div>";
    $render['header'] = [
      '#markup' => $content,
      '#allowed_tags' => ['strong', 'class', 'div', 'p'],
    ];
  }

}
