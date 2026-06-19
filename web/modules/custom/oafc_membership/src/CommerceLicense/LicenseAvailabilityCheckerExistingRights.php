<?php

namespace Drupal\oafc_membership\CommerceLicense;

use Drupal\commerce\Context;
use Drupal\commerce_license\LicenseAvailabilityCheckerExistingRights as Base;
use Drupal\commerce_order\AvailabilityResult;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Overridden implementation of the license availability checker.
 *
 * On top of the checks done by the default implementation, we check if there is
 * a license pending approval. They may already be detected by the default
 * availability checker but the message is not clear.
 */
class LicenseAvailabilityCheckerExistingRights extends Base {

  /**
   * {@inheritdoc}
   */
  public function check(OrderItemInterface $order_item, Context $context) {
    $purchased_entity = $order_item->getPurchasedEntity();
    if (!$purchased_entity instanceof ProductVariationInterface) {
      return parent::check($order_item, $context);
    }
    /** @var \Drupal\oafc_membership\CommerceLicense\LicenseStorage $license_storage */
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    $existing_license = $license_storage->getExistingLicense(
      $purchased_entity,
      $context->getCustomer()->id()
    );
    if (!$existing_license) {
      return parent::check($order_item, $context);
    }
    if ($existing_license->getState()->getId() !== 'pending') {
      return parent::check($order_item, $context);
    }

    return AvailabilityResult::unavailable(
      $this->t(
        'You already have an existing license that is pending approval. Please
         wait until we review your order.'
      )
    );
  }

}
