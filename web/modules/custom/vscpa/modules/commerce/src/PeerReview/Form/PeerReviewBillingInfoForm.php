<?php

namespace Drupal\vscpa_commerce\PeerReview\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vscpa_commerce\PeerReview\Event\PeerReviewPaymentEvent;
use Drupal\vscpa_commerce\PeerReview\Event\PeerReviewPaymentEvents;

/**
 * Peer Review Billing Info Form.
 */
class PeerReviewBillingInfoForm extends MultiStepFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vscpa_commerce_peer_review_billing_info_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $aicpa_number = $this->getRequest()->query->get('aicpa-number');
    if (empty($aicpa_number)) {
      return [];
    }
    $url = Url::fromRoute('vscpa_commerce.peer_review');
    $back_link = [
      '#type' => 'link',
      '#url' => $url,
      '#title' => $this->t('<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Go Back'),
      '#attributes' => [
        'class' => [
          'button',
          'button--default',
          'js-form-submit',
          'form-submit',
          'peer-review-button-go-back',
          'btn',
          'btn-default',
        ],
      ],
    ];
    $form['#attached']['library'][] = 'vscpa_commerce/peer_review';
    $form['#tree'] = TRUE;
    $form['title'] = [
      '#markup' => $this->t('<h3 class="accent-left purple"> Peer Review Administrative Fee Payment <small>- Billing Info.</small></h3>'),
    ];
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container'],
      ],
    ];
    // Retrieve info from AM.net.
    $this->rebuildPeerReviewInfo();
    $this->applyFirmSizeChanges($form_state);
    // Check the AICPA Firm ID.
    if ($this->info->getAmNetAicpaNumber() != $aicpa_number) {
      $message = $this->t('Sorry, you must be an employee of this firm in order to pay it\'s Peer Review administration fee. Please make sure you are using AICPA Firm ID, which should begin with the number 9. Please contact <a href="mailto:peerreview@vscpa.com">peerreview@vscpa.com</a> or call (800) 733-8272 for assistance.');
      $this->messenger()->addWarning($message);
      $form['actions']['back'] = $back_link;
      // Stop Here.
      return $form;
    }
    // Check if the Peer Review record for the firm has the status 'Active'.
    if (!$this->info->isFirmActiveOnPeerReviewSystem()) {
      $params = ['@firm_id' => $this->info->getFirmId()];
      $message = $this->t('Your Firm ID <strong>@firm_id</strong> has a status of "Inactive" in the VSCPA Peer Review Payment system, To pay your annual administrative fee now, please contact the VSCPA Peer Review Team at <a href="mailto:peerreview@vscpa.com">peerreview@vscpa.com</a> or (800) 733-8272, option 4.', $params);
      $this->messenger()->addWarning($message);
      $form['actions']['back'] = $back_link;
      // Stop Here.
      return $form;
    }
    $form['container']['firm_info'] = [
      '#markup' => $this->t('Your AICPA Firm ID: <strong>@aicpa_number</strong>.', ['@aicpa_number' => $aicpa_number]),
      '#attributes' => [
        'class' => ['firm_info'],
      ],
    ];
    $clear = [
      '#markup' => '<div class="clear clearfix"></div>',
      '#allowed_tags' => ['div', 'class'],
    ];
    // AICPA Info.
    $items = [];
    $aicpa_name = $this->info->getAmNetAicpaName();
    if (!empty($aicpa_name)) {
      $items[] = [
        '#markup' => "<strong>AICPA Name:</strong> " . $aicpa_name . ".",
        '#allowed_tags' => ['strong'],
      ];
    }
    $contact_email = $this->info->getAmNetContactEmail();
    if (!empty($contact_email)) {
      $items[] = [
        '#markup' => "<strong>Contact Email:</strong> " . $contact_email . ".",
        '#allowed_tags' => ['strong'],
      ];
    }
    $contact_phone = $this->info->getAmNetContactPhone();
    if (!empty($contact_phone)) {
      $items[] = [
        '#markup' => "<strong>Contact Phone:</strong> " . $contact_phone . ".",
        '#allowed_tags' => ['strong'],
      ];
    }
    if ($this->info->hasFirmSizeChanges()) {
      $items[] = [
        '#markup' => "<strong>Previous Firm size:</strong> " . $this->getPreviousBillingClassLabel(),
        '#allowed_tags' => ['strong'],
      ];
      $items[] = [
        '#markup' => "<strong>New Firm size:</strong> " . $this->getCurrentBillingClassLabel(),
        '#allowed_tags' => ['strong'],
      ];
    }
    else {
      $billing_class_label = $this->getCurrentBillingClassLabel();
      if (!empty($billing_class_label)) {
        $items[] = [
          '#markup' => "<strong>Current Firm size:</strong> " . $billing_class_label,
          '#allowed_tags' => ['strong'],
        ];
      }
    }
    $form['container']['aicpa_info'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
      '#attributes' => ['class' => 'aicpa_info'],
      '#wrapper_attributes' => ['class' => 'container'],
    ];
    $has_balance = $this->info->hasBalance();
    $current_balance = $has_balance ? $this->info->getFormattedBalance() : '$0.00';
    $form['container']['balance'] = [
      '#markup' => "<h4 class='current-balance-peer-review'>Current Balance: <strong>" . $current_balance . "</strong></h4>",
      '#allowed_tags' => ['strong', 'h4', 'class'],
    ];
    if (!$has_balance) {
      // User has no current Balance.
      $message = $this->t('The provided <strong>AICPA Firm ID</strong> does not have a balance for the current fiscal year!. Questions?, please contact the VSCPA Peer Review Team at (800) 733-8272 or <a href="mailto:peerreview@vscpa.com">peerreview@vscpa.com</a>.');
      $this->messenger()->addMessage($message);
    }
    else {
      $form['container']['fees'] = $this->getBalance();
      if ($this->rates->getAllowChangeFirmSize()) {
        $form['container']['container_firm_size_changes'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['container_firm_size_changes'],
          ],
        ];
        // Clear.
        $form['container']['container_firm_size_changes']['clear_1'] = $clear;
        // Does your linked firm has experienced changes in its size?.
        $form['container']['container_firm_size_changes']['firm_size_changes'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Check here if the number of employees in your firm/company has changed.'),
          '#default_value' => FALSE,
          '#description' => $this->getFirmChangesDescription(),
        ];
        // Clear.
        $form['container']['container_firm_size_changes']['clear_2'] = $clear;
        // Select Firm Size.
        $form['container']['container_firm_size_changes']['firm_size'] = [
          '#type' => 'radios',
          '#title' => $this->t('Please select your firm size:'),
          '#options' => $this->rates->getFirmSizeOptions(),
          '#default_value' => $this->info->getNewBillingCode(),
          '#states' => [
            'visible' => [
              ':input[name="container[container_firm_size_changes][firm_size_changes]"]' => ['checked' => TRUE],
            ],
          ],
          '#attributes' => ['class' => ['firm-size-options']],
        ];
        // Clear.
        $form['container']['container_firm_size_changes']['clear_3'] = $clear;
        $form['container']['container_firm_size_changes']['actions'] = [
          '#type' => 'actions',
          '#states' => [
            'visible' => [
              ':input[name="container[container_firm_size_changes][firm_size]"]' => ['!value' => $this->info->getNewBillingCode()],
            ],
          ],
        ];
        $form['container']['container_firm_size_changes']['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Confirm Firm size changes'),
          '#submit' => ['::changeFirmSizeFormSubmit'],
          '#attributes' => [
            'class' => [
              'button',
              'btn-primary',
              'btn',
              'js-form-submit',
              'form-submit',
              'btn-sm',
              'peer-review-button-change-firm-size',
            ],
          ],
        ];
      }
      $form['actions'] = [
        '#type' => 'actions',
        '#states' => [
          'visible' => [
            ':input[name="container[container_firm_size_changes][firm_size]"]' => ['value' => $this->info->getNewBillingCode()],
          ],
        ],
      ];
      $form['actions']['back'] = $back_link;
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Continue to Checkout'),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
            'js-form-submit',
            'form-submit',
            'peer-review-button-float-right',
            'btn',
            'btn-primary',
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $this->currentUser();
    // Retrieve info from AM.net.
    $this->rebuildPeerReviewInfo();
    // Handle Firm size changes, add Billing class code changes.
    $billing_class_code = $this->info->getBillingClassCode();
    $this->info->setPreviousBillingCode($billing_class_code);
    $info = $this->handleFirmSizeChanges($form_state);
    $this->info->setFirmSizeChanges($info['firm_size_changes']);
    $new_billing_code = ($info['firm_size_changes']) ? $info['new_billing_class_code'] : $billing_class_code;
    $this->info->setNewBillingCode($new_billing_code);
    // Trigger Peer Review Payment event.
    $event = new PeerReviewPaymentEvent($account, $this->info);
    $this->eventDispatcher->dispatch(PeerReviewPaymentEvents::SUBMIT_PAYMENT, $event);
    if ($redirect_url = $event->getRedirectUrl()) {
      $form_state->setRedirectUrl($redirect_url);
    }
  }

  /**
   * Implements submit callback for the 'Change Firm Size' button.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   */
  public function changeFirmSizeFormSubmit(array &$form, FormStateInterface $form_state) {
    // Handle Firm size changes.
    $info = $this->handleFirmSizeChanges($form_state);
    if ($info['firm_size_changes']) {
      $form_state->set('new_billing_class_code', $info['new_billing_class_code']);
      // Since our buildForm() method relies on the value of
      // 'peer_review_info' to generate 'billing items' form elements,
      // we have to tell the form to rebuild. If we don't do this,
      // the form builder will not call buildForm().
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * Handle firm size changes.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   *
   * @return array
   *   The firm size change info.
   */
  public function handleFirmSizeChanges(FormStateInterface $form_state) {
    $info = [
      'firm_size_changes' => FALSE,
      'new_billing_class_code' => NULL,
    ];
    if (!$this->rates->getAllowChangeFirmSize()) {
      return $info;
    }
    $firm_size_changes = $form_state->getValue([
      'container',
      'container_firm_size_changes',
      'firm_size_changes',
    ]);
    $new_billing_class_code = $form_state->getValue([
      'container',
      'container_firm_size_changes',
      'firm_size',
    ]);
    $changes = !empty($new_billing_class_code) && ($this->info->getPreviousBillingCode() != $new_billing_class_code) && ($this->info->getBillingClassCode() != $new_billing_class_code);
    $info['firm_size_changes'] = ($firm_size_changes && $changes);
    $info['new_billing_class_code'] = $new_billing_class_code;
    return $info;
  }

}
