<?php

namespace Drupal\am_net_membership\Form;

use Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanInfo;
use Drupal\am_net_membership\Event\MembershipApplicationEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\am_net_membership\Event\MembershipEvents;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Url;

/**
 * Implements the Application/Membership Dues form controller.
 *
 * @see \Drupal\am_net_membership\Form\MembershipMultiStepFormBase
 */
class MembershipDuesForm extends MembershipMultiStepFormBase {

  /**
   * The List of fields keys used on this step.
   *
   * @var array
   */
  protected $stepFields = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'am_net_membership.application.membership_dues';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($form instanceof RedirectResponse) {
      return $form;
    }
    $eligibility = TRUE;
    // Check Felony Conviction.
    $value = $this->getFieldValue('field_convicted_felon');
    if ($value == 'Y') {
      return $this->redirect('am_net_membership.application.felony_conviction');
    }
    $form['member_dues_information'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'member_dues_information_wrapper',
        ],
      ],
      '#tree' => TRUE,
    ];
    $form['member_dues_information']['title'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . $this->t('Membership Dues') . '</h3>',
    ];
    $billingClassCode = $this->membershipChecker->getBillingClassCode($this->currentUser);
    $user_has_an_appropriate_code = !empty($billingClassCode) && ($billingClassCode > 0);
    $membership_price = NULL;
    if ($user_has_an_appropriate_code) {
      // Membership price.
      $membership_price = $this->membershipChecker->getMembershipPrice($this->currentUser);
      $price = number_format(floatval($membership_price), 2, '.', ',');
      // Membership license by default expires 6/9 of next year.
      $membership_expiration_date = $this->membershipChecker->getMembershipLicenseExpirationDate('F j, Y');
      $membership_dues = "As of today's date, your dues for this membership year are <strong>$" . $price . "</strong> covering your membership through <strong>{$membership_expiration_date}.</strong>";
    }
    else {
      $membership_dues = t('Based on your information, please contact VSCPA at (800) 733-8272 to determine your membership eligibility.');
      $eligibility = FALSE;
    }
    $form['member_dues_information']['membership_dues'] = [
      '#type' => 'item',
      '#markup' => $membership_dues,
    ];
    if (!$eligibility) {
      unset($form['actions']);
      $form['actions']['go_back'] = [
        '#title' => $this->t('Go back'),
        '#type' => 'link',
        '#url' => Url::fromRoute('am_net_membership.application.membership_qualification'),
        '#weight' => 1,
        '#attributes' => [
          'class' => ['btn btn-white'],
        ],
      ];
      return $form;
    }
    $form['donations'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'donations_wrapper',
        ],
      ],
      "#tree" => TRUE,
    ];
    $form['donations']['title'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="accent-left purple">' . t('Donation') . '</h3>',
    ];
    $form['donations']['description'] = [
      '#type' => 'item',
      '#markup' => "Please enter a donation amount or $0.",
    ];
    $contribution_amount = '35';
    $form['donations']['pac'] = [
      '#type' => 'number',
      '#title' => t('VSCPA PAC'),
      '#default_value' => $contribution_amount,
      '#min' => 0,
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => t('PAC (VSCPA Political Action Committee)'),
      '#attributes' => [
        'class' => ['contribution-amount'],
      ],
    ];
    $form['donations']['ef'] = [
      '#type' => 'number',
      '#title' => t('VSCPA Educational Foundation contribution'),
      '#default_value' => $contribution_amount,
      '#min' => 0,
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => t('VSCPA Educational Foundation contribution'),
      '#attributes' => [
        'class' => ['contribution-amount'],
      ],
    ];
    // Actions.
    $form['actions']['go_back'] = [
      '#title' => $this->t('Go back'),
      '#type' => 'link',
      '#url' => Url::fromRoute('am_net_membership.application.membership_qualification'),
      '#weight' => 1,
      '#attributes' => [
        'class' => ['btn btn-white'],
      ],
    ];
    // Build the 'Dues Payment Plan Info'.
    $duesPaymentPlan = new DuesPaymentPlanInfo();
    $duesPaymentPlan->setBaseMembershipFee($membership_price);
    $duesPaymentPlan->setEndDateOfCurrentFiscalYear($this->membershipChecker->getEndDateOfCurrentFiscalYear());
    $duesPaymentPlan->addContributionAmount($contribution_amount, 'ef');
    $duesPaymentPlan->addContributionAmount($contribution_amount, 'pac');
    $duesPaymentPlan->setMembershipApplication(TRUE);
    // The the 'Dues Payment plan Info' in a cache.
    $this->duesPaymentPlanManager->save($duesPaymentPlan, $this->currentUser->id());
    if ($duesPaymentPlan->getPlanMonths() == 0) {
      // No dues plan balance can be applied.
      $form['enroll'] = [
        '#type' => 'hidden',
        '#value' => FALSE,
      ];
      // Submit.
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('<span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> Continue to billing information</a>'),
        '#weight' => 2,
        '#attributes' => [
          'class' => [
            'btn',
            'btn-primary',
            'btn-lg',
          ],
        ],
      ];
      return $form;
    }
    // Add Ajax Callback into the donations fields.
    $form['donations']['pac']['#ajax'] = [
      'callback' => [$this, 'updateDuesPaymentContainerCallback'],
      'wrapper' => 'modal-dues-plan-container',
      'event' => 'change',
    ];
    $form['donations']['ef']['#ajax'] = [
      'callback' => [$this, 'updateDuesPaymentContainerCallback'],
      'wrapper' => 'modal-dues-plan-container',
      'event' => 'change',
    ];
    // Addd Submit action button!.
    $form['actions']['modal_dues_payment_plan'] = [
      '#markup' => '<a class="btn btn-primary btn-lg modal-dues-payment-plan-button" id="modal-dues-payment-plan-button"><span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> Continue to billing information</a>',
      '#allowed_tags' => ['a', 'class', 'span', 'aria-hidden', 'id'],
      '#weight' => 2,
    ];
    $form['modal_dues_payment_plan_enroll'] = [
      '#type' => 'modal_dues_payment_plan_enroll',
      '#payment_plan' => $duesPaymentPlan,
    ];
    $form['enroll'] = [
      '#type' => 'hidden',
      '#value' => FALSE,
    ];
    $form['#attributes']['class'][] = 'trigger-js-submit';
    // Submit.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#attributes' => [
        'class' => ['btn-opacity-hidden'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $required_fields_error = $this->t('Please complete all the required fields.');
    $errors = $form_state->getErrors();
    if (!empty($errors)) {
      $form_state->setErrorByName('membership_dues', $required_fields_error);
    }
    else {
      // Verify that the user has completed all the required fields.
      if (!$this->userCompletedAllRequiredFields()) {
        $form_state->setErrorByName('membership_dues', $required_fields_error);
      }
      else {
        // Remove the user sync lock.
        $this->membershipChecker->unlockUserSync($this->currentUser);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the 'Dues Payment Plan' enroll flag.
    $user_input = $form_state->getUserInput();
    $enroll_dues_payment_plan = $user_input['enroll'] ?? FALSE;
    $enroll_dues_payment_plan = (bool) $enroll_dues_payment_plan;
    // Get the Current Dues Payment Plan.
    $duesPaymentPlan = $this->duesPaymentPlanManager->get($this->currentUser->id());
    $duesPaymentPlan->enroll($enroll_dues_payment_plan);
    $donations = [];
    $values = $form_state->getValues();
    $pac = new Price($values['donations']['pac'] ?: 0, 'USD');
    if (!$pac->isZero()) {
      $donations[] = [
        'amount' => $pac,
        'anonymous' => FALSE,
        'destination' => 'PAC',
        'source' => 'P',
      ];
      $duesPaymentPlan->addContributionAmount($pac->getNumber(), 'pac');
    }
    $ef = new Price($values['donations']['ef'] ?: 0, 'USD');
    if (!$ef->isZero()) {
      $donations[] = [
        'amount' => $ef,
        'anonymous' => FALSE,
        'destination' => 'EF',
        'source' => 'P',
      ];
      $duesPaymentPlan->addContributionAmount($ef->getNumber(), 'ef');
    }
    $event = new MembershipApplicationEvent($this->currentUser, $donations, NULL, $duesPaymentPlan);
    $this->eventDispatcher->dispatch(MembershipEvents::SUBMIT_APPLICATION, $event);
    $form_state->setRedirectUrl(Url::fromRoute('commerce_cart.page'));
  }

}
