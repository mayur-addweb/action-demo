<?php

namespace Drupal\am_net_membership\EventSubscriber;

use Drupal\user\UserInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderItemEvent;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\am_net_membership\MembershipCheckerInterface;
use Drupal\am_net_membership\MemberStatusCodesInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Performs membership transactions on order and order item events.
 */
class OrderEventSubscriber implements EventSubscriberInterface {

  /**
   * The membership service manager.
   *
   * @var \Drupal\am_net_membership\MembershipCheckerInterface
   */
  protected $membershipServiceManager;

  /**
   * Constructs a new OrderReceiptSubscriber object.
   *
   * @param \Drupal\am_net_membership\MembershipCheckerInterface $membership_service_manager
   *   The membership service manager.
   */
  public function __construct(MembershipCheckerInterface $membership_service_manager) {
    $this->membershipServiceManager = $membership_service_manager;
  }

  /**
   * Creates a membership transaction when an order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The order workflow event.
   */
  public function onOrderPlace(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $user = $order->getCustomer();
    $order_items = $order->getItems();
    foreach ($order_items as $orderItem) {
      if ($orderItem->bundle() == 'membership') {
        $this->tryAssignMembershipLicense($user, $orderItem);
      }
    }
  }

  /**
   * Assign Membership License to an user account tie to a order item.
   *
   * @param \Drupal\user\UserInterface $cart_owner
   *   The user account tie to the order.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item that generated the membership purchased.
   */
  protected function tryAssignMembershipLicense(UserInterface $cart_owner, OrderItemInterface $order_item) {
    /** @var \Drupal\user\UserInterface $user */
    $field_user = $order_item->get('field_user');
    $user = isset($field_user->entity) ? $field_user->entity : $cart_owner;
    $was_terminated_member_on_re_apply = $this->membershipServiceManager->isTerminatedMemberOnReApply($user);
    // Membership status info.
    $membership_status_info = $this->membershipServiceManager->getMembershipStatusInfo($user);
    // Determine if the user is coming from a Membership Application or if user
    // is coming from a Membership Renewal.
    $is_membership_application = ($membership_status_info['is_membership_application'] == TRUE);
    $format = 'Y-m-d';
    if ($is_membership_application) {
      // Set 'Dues Paid Through Date'.
      $dues_paid_through = $this->membershipServiceManager->getMembershipLicenseExpirationDate($format);
    }
    else {
      // Reset 'Dues Paid Through Date'.
      $dues_paid_through = $this->membershipServiceManager->getEndDateOfCurrentFiscalYear($format);
    }
    $user->set('field_amnet_dues_paid_through', $dues_paid_through);
    // Create the License.
    $this->membershipServiceManager->assignMembershipLicense($user);
    // Set membership status to good standing.
    $user->set('field_member_status', MemberStatusCodesInterface::MEMBER_IN_GOOD_STANDING);
    // Grant Member Role.
    if (!$user->hasRole(MemberStatusCodesInterface::ROLE_ID_MEMBER)) {
      $user->addRole(MemberStatusCodesInterface::ROLE_ID_MEMBER);
    }
    // Set Join date if applies.
    $current_user_join_date = $user->get("field_join_date")->getString();
    if (empty($current_user_join_date)) {
      $user->set("field_join_date", date("Y-m-d"));
    }
    // Set the Secondary Join Date if applies.
    if ($was_terminated_member_on_re_apply) {
      $user->set("field_join_date_2", date("Y-m-d"));
    }
    // Remove the user sync lock.
    $this->membershipServiceManager->unlockUserSync($user);
    $this->membershipServiceManager->setMembershipStatusInfoAvailableForPush($user, $available = TRUE);
    $user->save();
  }

  /**
   * Acts on the order update event to create transactions for new items.
   *
   * The reason this isn't handled by OrderEvents::ORDER_ITEM_INSERT is because
   * that event never has the correct values.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function onOrderUpdate(OrderEvent $event) {
  }

  /**
   * Performs a membership transaction for an order Cancel event.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The order workflow event.
   */
  public function onOrderCancel(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
  }

  /**
   * Performs a membership transaction on an order item update.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The order item event.
   */
  public function onOrderItemUpdate(OrderItemEvent $event) {
    $item = $event->getOrderItem();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $item->getOrder();
  }

  /**
   * Performs a membership transaction on an order item pre-save.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The order item event.
   */
  public function onOrderItemPreSave(OrderItemEvent $event) {
    $item = $event->getOrderItem();
    if ($item->bundle() != 'membership') {
      return;
    }
    $price = $item->getUnitPrice();
    $item->setUnitPrice($price, TRUE);
  }

  /**
   * Performs a membership transaction when an order item is deleted.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The order item event.
   */
  public function onOrderItemDelete(OrderItemEvent $event) {
    $item = $event->getOrderItem();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $item->getOrder();
    if ($order && !in_array($order->getState()->value, ['draft', 'canceled'])) {
      $entity = $item->getPurchasedEntity();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      // State change events fired on workflow transitions from state_machine.
      'commerce_order.place.post_transition' => ['onOrderPlace', -100],
      'commerce_order.cancel.post_transition' => ['onOrderCancel', -100],
      // Order storage events dispatched during entity operations in
      // CommerceContentEntityStorage.
      // ORDER_UPDATE handles new order items since ORDER_ITEM_INSERT doesn't.
      OrderEvents::ORDER_UPDATE => ['onOrderUpdate', -100],
      OrderEvents::ORDER_ITEM_UPDATE => ['onOrderItemUpdate', -100],
      OrderEvents::ORDER_ITEM_DELETE => ['onOrderItemDelete', -100],
      OrderEvents::ORDER_ITEM_PRESAVE => ['onOrderItemPreSave', -100],
    ];
    return $events;
  }

}
