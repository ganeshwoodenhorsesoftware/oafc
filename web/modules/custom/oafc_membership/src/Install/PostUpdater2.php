<?php

namespace Drupal\oafc_membership\Install;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Runs post updates 2.
 */
class PostUpdater2 {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PostUpdater2 object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Runs the updates.
   *
   * Removes the product/variation set up for purchasing the Board of Directors
   * role license. It is not needed, we only need the user role that will be
   * manually given to users.
   */
  public function run() {
    $variation_storage = $this->entityTypeManager
      ->getStorage('commerce_product_variation');
    $variation_ids = $variation_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('sku', 'BOARD-MEMBERSHIP')
      ->execute();
    if (!$variation_ids) {
      return;
    }

    $product_storage = $this->entityTypeManager->getStorage('commerce_product');

    // There should be only one, but let's make the code not fail just in case.
    $variations = $variation_storage->loadMultiple($variation_ids);
    foreach ($variations as $variation) {
      if (!$variation instanceof ProductVariationInterface) {
        continue;
      }
      // Deleting the product will delete the variation as well - see
      // `\Drupal\commerce\Entity\Product::postDelete()`.
      $product = $variation->getProduct();
      if ($product !== NULL) {
        $product_storage->delete([$product]);
      }
    }
  }

}
