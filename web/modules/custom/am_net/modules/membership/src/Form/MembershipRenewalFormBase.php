<?php

namespace Drupal\am_net_membership\Form;

use Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\am_net_membership\MembershipCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Implements the Membership Renewal Form Base.
 */
abstract class MembershipRenewalFormBase extends FormBase {

  use MembershipFormTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The 'Dues Payment Plan' Manager.
   *
   * @var \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanManagerInterface
   */
  protected $duesPaymentPlanManager;

  /**
   * Constructs a Membership Renewal Form Base object.
   *
   * @param \Drupal\am_net_membership\MembershipCheckerInterface $membership_checker
   *   The Membership Checker.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\am_net_membership\DuesPaymentPlan\DuesPaymentPlanManagerInterface $dues_payment_plan_manager
   *   The dues payment manager.
   */
  public function __construct(MembershipCheckerInterface $membership_checker, EventDispatcherInterface $event_dispatcher, DuesPaymentPlanManagerInterface $dues_payment_plan_manager) {
    $this->membershipChecker = $membership_checker;
    $this->userIsAnonymous = $this->membershipChecker->userIsAnonymous();
    $this->currentUser = $this->membershipChecker->getCurrentUser();
    $this->eventDispatcher = $event_dispatcher;
    $this->duesPaymentPlanManager = $dues_payment_plan_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('am_net_membership.checker'),
      $container->get('event_dispatcher'),
      $container->get('am_net_membership.payment_plans.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->userIsAnonymous) {
      // Redirect to Create Account page.
      return $this->redirect('user.register', [], ['query' => ['destination' => '/membership/application']]);
    }
    if (!$this->membershipChecker->userCanCompleteMembershipRenewalProcess($this->currentUser)) {
      // Redirect to the user view page.
      return $this->redirect('entity.user.canonical', ['user' => $this->currentUser->id()]);
    }
    $form = [];
    $form['#id'] = "user-form";
    $form['#attributes'] = ['class' => ['user-form']];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * Build Panel.
   *
   * @param string $panel_title
   *   The firm title.
   * @param string $panel_description
   *   The firm description.
   * @param string $panel_actions
   *   The firm actions.
   *
   * @return string
   *   The Firm Panel.
   */
  public function buildPanel($panel_title = '', $panel_description = '', $panel_actions = '') {
    return "<div class='panel panel-info no-padding'><div class='panel-heading'><h3 class='panel-title'>{$panel_title}</h3></div><div class='panel-body padding-10'>{$panel_description}</div>{$panel_actions}</div>";
  }

  /**
   * Get Home Address Info.
   *
   * @return string
   *   The rendered element.
   */
  public function getHomeAddressInfo() {
    $entity_type = 'user';
    $view_mode = 'home_address';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $build = $view_builder->view($this->currentUser, $view_mode);
    return render($build);
  }

  /**
   * Get Office Address Info.
   *
   * @return string
   *   The rendered element.
   */
  public function getOfficeAddressInfo() {
    $entity_type = 'user';
    $view_mode = 'office_address';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $build = $view_builder->view($this->currentUser, $view_mode);
    return render($build);
  }

}
