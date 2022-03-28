<?php

namespace Drupal\vscpa_commerce\PeerReview\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Peer Review form.
 */
class PeerReviewForm extends MultiStepFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vscpa_commerce_peer_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['title'] = [
      '#markup' => '<h3 class="accent-left purple">' . $this->t('Peer Review Administrative Fee Payment') . '</h3>',
    ];
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container'],
      ],
    ];
    $form['container']['firm_info'] = [
      '#markup' => $this->getFirmInfo(),
      '#attributes' => [
        'class' => ['firm_info'],
      ],
    ];
    $form['container']['aicpa_firm_id'] = [
      '#type' => 'number',
      '#title' => $this->t('AICPA Firm ID'),
      '#description' => $this->t("Please provide your firm's <strong>AICPA Firm ID</strong>."),
      '#required' => TRUE,
      '#weight' => 1,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue to Billing Info'),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
          'js-form-submit',
          'form-submit',
          'brn',
          'btn-primary',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirmInfo() {
    return $this->t('Welcome to the Peer Review Administrative Fee Payment System. To begin, please enter your AICPA Firm ID below (number should begin with a 9). <br>If you do not know your AICPA Firm ID, please contact <a href="mailto:peerreview@vscpa.com">peerreview@vscpa.com</a> or call (800) 733-8272 for assistance.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $aicpa_number = $form_state->getValue(['container', 'aicpa_firm_id']);
    $options = ['query' => ['aicpa-number' => $aicpa_number]];
    $form_state->setRedirect('vscpa_commerce.peer_review_confirm', [], $options);
  }

}
