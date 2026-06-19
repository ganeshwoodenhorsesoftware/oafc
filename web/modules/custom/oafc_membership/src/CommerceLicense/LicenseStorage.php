<?php

namespace Drupal\oafc_membership\CommerceLicense;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\LicenseStorage as LicenseStorageBase;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Overridden implementation of the license entity storage.
 */
class LicenseStorage extends LicenseStorageBase {

  /**
   * {@inheritdoc}
   *
   * We want to prevent users from having duplicate active licenses. Therefore
   * we consider as existing licenses all licenses that are not permanently
   * deactivated. Licenses in other states must be resolved to a state of
   * permanent deactivation (expired, revoked or cancelled) before we allow a
   * new license to be issued for the same user.
   *
   * We consider pending licenses as existing licenses so that they are
   * recognized when adding to cart. Otherwise users may submit multiple orders,
   * while they should wait until their order is approved. They can then renew
   * if needed.
   *
   * We also consider suspended licenses as existing licenses because it is not
   * a permanent revocation i.e. it may be reactivated again causing a duplicate
   * license.
   *
   * The `renewal_cancelled` state is temporary. When a user adds a license
   * product to the cart for renewing it, the license state is set to
   * `renewal_in_progress`. If the user removes the item it from the cart, it is
   * first moved to `renewal_cancelled` and immediately activated again. If,
   * however, an administrator by mistake uses this state the license can be
   * stuck there and a duplicate would be created. We therefore consider this an
   * existing license as well.
   */
  public function getExistingLicense(
    ProductVariationInterface $variation,
    $uid,
  ): LicenseInterface|false {
    $existing_licenses_ids = $this->getQuery()
      ->condition(
        'state',
        [
          'pending',
          'active',
          'renewal_in_progress',
          'renewal_cancelled',
          'suspended',
        ],
        'IN'
      )
      ->condition('uid', $uid)
      ->condition('product_variation', $variation->id())
      ->accessCheck()
      ->execute();

    if (!empty($existing_licenses_ids)) {
      $existing_license_id = array_shift($existing_licenses_ids);
      $license = $this->load($existing_license_id);
      return $license instanceof LicenseInterface ? $license : FALSE;
    }

    return FALSE;
  }

}
