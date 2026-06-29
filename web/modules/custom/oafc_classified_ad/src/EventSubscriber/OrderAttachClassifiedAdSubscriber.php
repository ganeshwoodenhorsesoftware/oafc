<?php

namespace Drupal\oafc_classified_ad\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Attaches the classified ad to the order when it is created.
 */
class OrderAttachClassifiedAdSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_UPDATE => ['onUpdate'],
    ];
  }

  /**
   * Attaches the classified ad to the order.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The event.
   */
  public function onUpdate(OrderEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();

    if ($order->bundle() === 'classified_ad' &&
      $order->getData('classified_ad_id') &&
      $order->get('field_classified_ad')->isEmpty()) {
      $classified_ad_id = $order->getData('classified_ad_id');
      $order->set('field_classified_ad', $classified_ad_id);
      $order->save();
    }
  }

}
