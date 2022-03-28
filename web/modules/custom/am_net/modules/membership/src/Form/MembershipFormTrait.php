<?php

namespace Drupal\am_net_membership\Form;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * The Membership Form Helper trait implementation.
 *
 * Common functionality for Membership Renewal and Membership
 * Application processes.
 */
trait MembershipFormTrait {

  /**
   * Membership Class checker.
   *
   * @var \Drupal\am_net_membership\MembershipCheckerInterface|null
   */
  protected $membershipChecker = NULL;

  /**
   * Flag that determine if the current user is Anonymous.
   *
   * @var bool|null
   */
  protected $userIsAnonymous = NULL;

  /**
   * Defines an account interface which represents the current user.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected $currentUser = NULL;

  /**
   * The 'Dues Payment Plan' Manager.
   *
   * @var \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanManagerInterface
   */
  protected $duesPaymentPlanManager;

  /**
   * Get Field Value.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return mixed
   *   The value of the field.
   */
  public function getFieldValue($field_name = '') {
    if (empty($field_name)) {
      return NULL;
    }
    return $this->membershipChecker->getFieldValue($this->currentUser, $field_name);
  }

  /**
   * Get the referenced entity label.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return string|null
   *   The entity label, otherwise null.
   */
  public function referencedEntityLabel($field_name = '') {
    if (empty($field_name)) {
      return NULL;
    }
    $user = $this->currentUser;
    if (!$user) {
      return NULL;
    }
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $entity_reference_field_item_list */
    $entity_reference_field_item_list = $user->get($field_name);
    if (!$entity_reference_field_item_list) {
      return NULL;
    }
    $entities = $entity_reference_field_item_list->referencedEntities();
    if (!$entities) {
      return NULL;
    }
    $entity = current($entities);
    if (!($entity instanceof TermInterface)) {
      return NULL;
    }
    return $entity->getName();
  }

  /**
   * Determines whether the Form is visible.
   *
   * @return bool
   *   TRUE if the form is visible, FALSE otherwise.
   */
  public function isVisible() {
    return TRUE;
  }

  /**
   * Check if user has the role firm admin.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return bool
   *   TRUE if the given account has access, otherwise FALSE.
   */
  public function isFirmAdmin(AccountInterface $account) {
    // Check Role.
    return in_array('firm_administrator', $account->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function updateDuesPaymentContainerCallback(array &$form, FormStateInterface $form_state) {
    $duesPaymentPlan = $this->duesPaymentPlanManager->get($this->currentUser->id());
    if (!$duesPaymentPlan) {
      return NULL;
    }
    // Update contribution values.
    $contribution_amount = $form_state->getValue(['donations', 'ef'], 0);
    $duesPaymentPlan->addContributionAmount($contribution_amount, 'ef');
    $contribution_amount = $form_state->getValue(['donations', 'pac'], 0);
    $duesPaymentPlan->addContributionAmount($contribution_amount, 'pac');
    // Update cache.
    $this->duesPaymentPlanManager->save($duesPaymentPlan, $this->currentUser->id());
    // Preparate render elements.
    $element = [
      '#type' => 'modal_dues_payment_plan_enroll',
      '#payment_plan' => $duesPaymentPlan,
    ];
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#modal-dues-plan-container', $element));
    $element = [
      '#markup' => '<a class="btn btn-primary btn-lg modal-dues-payment-plan-button" id="modal-dues-payment-plan-button"><span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> Continue to billing information</a>',
      '#allowed_tags' => ['a', 'class', 'span', 'aria-hidden', 'id'],
      '#weight' => 2,
    ];
    $response->addCommand(new ReplaceCommand('#modal-dues-payment-plan-button', $element));
    return $response;
  }

}
