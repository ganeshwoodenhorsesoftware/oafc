<?php

namespace Drupal\oafc_course\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Drupal\webform\Entity\WebformSubmission;
use \Drupal\commerce_order\Entity\OrderType;

/**
 * Performs stock transactions on order and order item events.
 */
class OrderEventSubscriber implements EventSubscriberInterface
{

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents()
  {
    $events = [
      // State change events fired on workflow transitions from state_machine.
      'commerce_order.cancel.post_transition' => ['onOrderCancel'],
      'commerce_order.place.pre_transition' => ['onCartOrderTransition', 100],
      'commerce_order.validate.pre_transition' => ['onCartOrderTransition', -100],
      'commerce_order.fulfill.pre_transition' => ['onCartOrderTransition', -100],
      OrderEvents::ORDER_UPDATE => ['onOrderUpdate'],
    ];
    return $events;
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
  public function onOrderUpdate(OrderEvent $event)
  {
    $order = $event->getOrder();
    $order_state = $order->getState()->value;
    if ($order_state == 'draft') {
      $quantity = $number_of_course_items = [];
      foreach ($order->getItems() as $item) {
        $webform_id = \Drupal::service('oafc_course.seat_availability_checker')->getCourseWebformId($item);
        if ($webform_id === FALSE) {
          continue;
        }
        if (empty($number_of_course_items[$webform_id])) {
          $number_of_course_items[$webform_id] = 1;
        }
        if (empty($quantity[$webform_id])) {
          $quantity[$webform_id] = 0;
        }
        $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);
        if (!empty($webform)) {
          // There may be multiple attendees per registration.
          // Calculating the number of attendees via the webform components.
          $submission_id = $item->getData('submision_id');
          $webform_submission = WebformSubmission::load($submission_id);
          $contact_count = oafc_course_get_submission_contact_count($webform_submission->getData());
          if ($contact_count > 0) {
            $quantity[$webform_id] += $contact_count;
            $number_of_course_items[$webform_id] += $number_of_course_items[$webform_id];
          }
        }
      }
      \Drupal::service('oafc_course.seat_availability_checker')->checkForOpenSeats($order, $quantity, $number_of_course_items);
    }
  }

  /**
   * Acts on the order state change event to create stock transactions.
   *
   * The reason this isn't handled by OrderEvents::ORDER_ITEM_INSERT is because
   * that event never has the correct values.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function onCartOrderTransition(WorkflowTransitionEvent $event)
  {
    $order = $event->getEntity();
    // Get the states we are leaving and reaching.
    $from_state = $event->getFromState()->getId();
    $reached_state = $event->getToState()->getId();

    $order_type = OrderType::load($order->bundle());
    $workflow = $event->getWorkflow()->getId();
    if ($workflow == 'order_default_validation' || $workflow == 'order_fulfillment_validation') {
      $order_complete_states = ['validation'];
    } elseif ($workflow == 'order_fulfillment') {
      $order_complete_states = ['fulfillment'];
    } else {
      $order_complete_states = ['completed'];
    }
    foreach ($order->getItems() as $item) {
      if (in_array($reached_state, $order_complete_states)) {
        \Drupal::service('oafc_course.seat_availability_checker')->courseStockAdjustment($item);
      }
    }
  }

  /**
   * Performs a stock transaction for an order Cancel event.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The order workflow event.
   */
  public function onOrderCancel(WorkflowTransitionEvent $event)
  {
    $order = $event->getEntity();
    $items = $order->getItems();
    foreach ($items as $item) {
      \Drupal::service('oafc_course.seat_availability_checker')->courseStockAdjustment($item, 'delete');
    }
  }
}
