<?php

namespace Drupal\am_net_payments\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment method delete form.
 */
class PaymentMethodDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->getEntity();
    $form_state->setRedirectUrl($this->getRedirectUrl());
    try {
      // Change the status of the payment method to Inactive.
      if ($payment_method->hasField('status')) {
        $payment_method->set('status', FALSE);
        $payment_method->save();
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return;
    }

    drupal_set_message($this->getDeletionMessage());
    $this->logDeletionMessage();
  }

}
