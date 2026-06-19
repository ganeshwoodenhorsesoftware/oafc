<?php

namespace Drupal\oafc_job_post\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Attaches the job post to the order when it is created.
 */
class OrderAttachJobPostSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_UPDATE => ['onUpdate'],
    ];
  }

  /**
   * Attaches the job post to the order.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The event.
   */
  public function onUpdate(OrderEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();

    if ($order->bundle() === 'job_post' &&
      $order->getData('job_post_id') &&
      $order->get('field_job_post')->isEmpty()) {
      $job_post_id = $order->getData('job_post_id');
      $order->set('field_job_post', $job_post_id);
      $order->save();
    }
  }

}
