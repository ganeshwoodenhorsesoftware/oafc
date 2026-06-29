<?php

namespace Drupal\oafc_no_tax;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * HST helper class.
 */
class HstHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new HST helper class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_manager
  ) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * Provides the HST number associated with an order.
   *
   * If the order contains only order items that are all
   * associated with the same webform (event or course),
   * and that webform has a custom HST number, then return that.
   * If the order contains other products or items associated with
   * other events/courses, then we display the global HST number;
   * otherwise the HST number for those other products/events/courses
   * would be wrong.
   *
   * @param Drupal\commerce_order\Entity\OrderInterface $order
   *   The order object.
   *
   * @return string
   *   The HST number.
   */
  public function getHstNumberForOrder(OrderInterface $order) {
    $hst_number = NULL;
    $webform_id = NULL;
    foreach ($order->getItems() as $order_item) {
      $item_webform_id = $order_item->getData('webform_id');

      // If we have even one non-webform order item, no need
      // to go through all of them; display the global HST.
      if ($item_webform_id === NULL) {
        $hst_number = NULL;
        break;
      }

      // Already got the number for this webform.
      if ($item_webform_id === $webform_id) {
        continue;
      }

      // We have already had a webform that is different than the
      // webform of the current item i.e. more than one event/course
      // on the same order; display the global HST.
      if ($webform_id !== NULL) {
        $hst_number = NULL;
        break;
      }

      // Got order's webform, store it for next iterations.
      // We will only ever reach here once.
      $webform_id = $item_webform_id;
      $hst_number = $this->entityTypeManager
        ->getStorage('webform')
        ->load($webform_id)
        ->getThirdPartySetting(
          'oafc_base',
          'hst_number'
        );
    }

    // If we have an HST number at this point, we have items
    // from one and only one event/course with a custom HST number; return that.
    if ($hst_number !== NULL) {
      return $hst_number;
    }

    // In all other cases we return the global HST number.
    return $order->getStore()->get('field_hst_number')->getString();
  }

}
