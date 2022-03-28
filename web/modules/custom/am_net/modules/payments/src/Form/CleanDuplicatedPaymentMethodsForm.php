<?php

namespace Drupal\am_net_payments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for clean duplicated payment methods.
 */
class CleanDuplicatedPaymentMethodsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_payments';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'sync_firms',
      '#value' => $this->t('Clean Duplicated Payment Methods'),
      '#attributes' => [
        'class' => [
          'button--primary button--small',
        ],
      ],
      '#submit' => [[$this, 'submitCleanDuplicatedPaymentMethods']],
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitCleanDuplicatedPaymentMethods(array &$form, FormStateInterface $form_state) {
    // Get payments methods related to users.
    // Database instance.
    $database = \Drupal::database();
    $query = $database->select('commerce_payment_method', 'payment_method');
    $result = $query->fields('payment_method', ['uid'])->distinct()->execute();
    $payment_method_uids = $result->fetchCol();
    if (empty($payment_method_uids)) {
      $message = t('There are no pending payment method to be processed!.');
      drupal_set_message($message, 'warning');
    }
    $operations = [];
    // Loop over the firm codes.
    foreach ($payment_method_uids as $key => $uid) {
      $operations[] = [
        '\Drupal\am_net_payments\Form\CleanDuplicatedPaymentMethodsForm::cleanDuplicatedPaymentMethods',
        [$uid],
      ];
    }
    $batch = [
      'title' => t('Cleaning duplicate payment methods...'),
      'operations' => $operations,
      'finished' => '\Drupal\am_net_payments\Form\CleanDuplicatedPaymentMethodsForm::cleanDuplicatedPaymentMethodsFinishedCallback',
    ];
    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit is optional due batch_set operations.
  }

  /**
   * Clean Duplicated Payment Methods.
   *
   * @param string $uid
   *   Required param, The User ID.
   * @param array $context
   *   Required param, The batch $context.
   */
  public static function cleanDuplicatedPaymentMethods($uid, array &$context = []) {
    $message = '<h3>Processing Uid: <strong>' . $uid . '</strong>...</h3>';
    $context['message'] = $message;
    $results = $context['results'];
    $context['results'] += $results;
    if (empty($uid)) {
      return;
    }
    // Get the Payment Methods related to the given user.
    $paymentMethodStorage = \Drupal::entityTypeManager()->getStorage('commerce_payment_method');
    $result = $paymentMethodStorage->getQuery()
      ->condition('uid', $uid)
      ->sort('created', $direction = 'DESC')
      ->execute();
    if (empty($result)) {
      return;
    }
    $deltas = [];
    foreach ($result as $key => $id) {
      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $payment_method = $paymentMethodStorage->load($id);
      $card_type = $payment_method->get('card_type')->getString();
      $card_number = $payment_method->get('card_number')->getString();
      $expires = $payment_method->get('expires')->getString();
      $type = $payment_method->get('type')->getString();
      $delta = "$card_type.$card_number.$expires.$type.$uid";
      if (!isset($deltas[$delta])) {
        $deltas[$delta] = TRUE;
        $payment_method->set('status', TRUE);
      }
      else {
        // Duplicated Payment Method, Disabled it.
        $payment_method->set('status', FALSE);
      }
      if ($payment_method->isExpired()) {
        // Duplicated Payment Method, Disabled it.
        $payment_method->set('status', FALSE);
      }
      $payment_method->save();
    }
  }

  /**
   * Clean Duplicated Payment Methods records Finished Callback.
   *
   * @param bool $success
   *   Required param, The batch $success.
   * @param array $results
   *   Required param, The batch $results.
   * @param array $operations
   *   Required param, The batch $operations.
   */
  public static function cleanDuplicatedPaymentMethodsFinishedCallback($success, array $results = [], array $operations = []) {
    if ($success) {
      $message = t('The Duplicated Payment Methods have been cleaned successfully.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
