<?php

namespace Drupal\oafc_membership\EventSubscriber;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\EventSubscriber\OrderSubscriber as LicenseOrderSubscriber;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

/**
 * Overridden implementation of the subscriber provided by commerce_license.
 *
 * The default workflow provided by commerce_license activates the license
 * either when the order is placed or when the order is fully paid. This
 * behavior is hard-coded on the parent subscriber and it is not configurable.
 *
 * Instead, we create the license as pending when the order is placed and we
 * activate the license when an administrator validates the order.
 *
 * Due to these overrides the `activate_on_place` setting is not respected and
 * it is disabled in the variation types.
 */
class OrderSubscriber extends LicenseOrderSubscriber {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.validate.post_transition' => ['onValidate', 100],
    ] + parent::getSubscribedEvents();
  }

  /**
   * {@inheritdoc}
   *
   * Orders for purchasing memberships are always paid when the order is
   * placed. We always handle memberships on the `onPlace` method.
   */
  public function onPaid(OrderEvent $event): void {
    if ($this->isMembershipOrder($event->getOrder())) {
      return;
    }

    // Non-membership orders should be unaffected.
    parent::onPaid($event);
  }

  /**
   * {@inheritdoc}
   *
   * Orders for purchasing memberships are always created as pending when the
   * order is placed.
   */
  public function onPlace(WorkflowTransitionEvent $event) {
    $entity = $event->getEntity();
    if (!$entity instanceof OrderInterface) {
      return;
    }
    $order = $entity;

    // Non-membership orders should be unaffected.
    if (!$this->isMembershipOrder($order)) {
      parent::onPlace($event);
      return;
    }

    // Create licenses in pending state.
    $this->createMembershipLicenses($order);
  }

  /**
   * Reacts to an order being validated.
   *
   * It activates licenses associated with the order items.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function onValidate(WorkflowTransitionEvent $event) {
    $entity = $event->getEntity();
    if (!$entity instanceof OrderInterface) {
      return;
    }
    $order = $entity;

    // Non-membership orders should be unaffected.
    if (!$this->isMembershipOrder($order)) {
      return;
    }

    // Activate licenses when the order is validated.
    $this->activateMembershipLicenses($order);
  }

  /**
   * Returns whether the given order is an order for purchasing a membership.
   *
   * Currently we only have Individual and Industry membership. They have the
   * same workflow. If more membership types are added that are purchased as
   * licenses, their workflow should be reviewed and added here, if the same, so
   * that it gets the same handling by this subscriber.
   */
  protected function isMembershipOrder(OrderInterface $order): bool {
    $memberships = [
      'individual_membership',
      'industry_membership',
    ];

    return in_array($order->bundle(), $memberships, TRUE);
  }

  /**
   * Create licenses as needed when the order is placed.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  protected function createMembershipLicenses(OrderInterface $order) {
    foreach ($this->getLicensableOrderItems($order) as $order_item) {
      $license = $order_item->get('license')->entity;
      // Skip if this order item already has a license (e.g. renewal).
      if ($license !== NULL) {
        continue;
      }

      // The license is set to `pending`, saved, and associated with the order
      // item by this method. Nothing more to do here.
      $this->createLicenseFromOrderItem($order_item);
    }
  }

  /**
   * Activates licenses as needed when the order is validated.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  protected function activateMembershipLicenses(OrderInterface $order) {
    foreach ($this->getLicensableOrderItems($order) as $order_item) {
      // The license must already exist.
      $license = $order_item->get('license')->entity;
      if (!$license instanceof LicenseInterface) {
        continue;
      }

      // The license must already be in the `pending` or
      // `renewal_in_progress`state. If not, such as in the case that an
      // administrator manually changed its state, do nothing.
      $activation_states = ['pending', 'renewal_in_progress'];
      if (!in_array($license->getState()->getId(), $activation_states)) {
        continue;
      }

      $license->set('state', 'active');
      $license->save();
    }
  }

}
