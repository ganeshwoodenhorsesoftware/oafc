<?php

namespace Drupal\oafc_membership\Install;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Runs post updates 1.
 */
class PostUpdater1 {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PostUpdater1 object.
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
   * Corrects values for existing membership type products and variations, and
   * creates missing ones.
   */
  public function run() {
    $this->updateExistingProducts();
    $this->updateExistingVariations();
    $this->createMissingProducts();
    $this->createMissingVariations();
  }

  /**
   * Corrects values for existing membership type products.
   */
  protected function updateExistingProducts() {
    $storage = $this->entityTypeManager
      ->getStorage('commerce_product');

    // Disable test or no longer required membership product.
    $product_ids = [23, 25, 27];
    foreach ($product_ids as $product_id) {
      /** @var \Drupal\commerce_product\Entity\ProductInterface|null $product */
      $product = $storage->load($product_id);
      if ($product instanceof ProductInterface) {
        $product->setUnpublished();
        $storage->save($product);
      }
    }
  }

  /**
   * Corrects values for existing membership type product variations.
   */
  protected function updateExistingVariations() {
    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $storage */
    $storage = $this->entityTypeManager
      ->getStorage('commerce_product_variation');

    foreach ($this->getExistingVariations() as $sku => $values) {
      $variation = $storage->loadBySku($sku);
      if ($variation instanceof ProductVariationInterface) {
        foreach ($values as $key => $value) {
          $variation->set($key, $value);
        }
        $storage->save($variation);
      }
    }
  }

  /**
   * Creates missing products and their variations.
   */
  protected function createMissingProducts() {
    $product_storage = $this->entityTypeManager
      ->getStorage('commerce_product');
    $variation_storage = $this->entityTypeManager
      ->getStorage('commerce_product_variation');

    foreach ($this->getMissingProducts() as $values) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
      $variation = $variation_storage->create($values['variation']);
      $variation_storage->save($variation);

      $values['variation'] = NULL;
      /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
      $product = $product_storage->create($values);
      $product->addVariation($variation);
      $product_storage->save($product);
    }
  }

  /**
   * Creates missing variations for existing products.
   */
  protected function createMissingVariations() {
    $product_storage = $this->entityTypeManager
      ->getStorage('commerce_product');
    $variation_storage = $this->entityTypeManager
      ->getStorage('commerce_product_variation');

    foreach ($this->getMissingVariations() as $values) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
      $variation = $variation_storage->create($values);
      $variation_storage->save($variation);

      /** @var \Drupal\commerce_product\Entity\ProductInterface|null $product */
      $product = $product_storage->load($values['product_id']);
      if ($product instanceof ProductInterface) {
        $product->addVariation($variation);
        $product_storage->save($product);
      }
    }
  }

  /**
   * Returns the values that should be corrected for existing variations.
   *
   * @return array
   *   An associative array keyed by the SKU of the product variation and
   *   containing an associative array with the key/value pairs of the fields
   *   that need to be updated.
   */
  protected function getExistingVariations() {
    return [
      // Associate member.
      'ASSOCIATE-MEMBERSHIP' => [
        'license_expiration' => [
          'target_plugin_id' => 'fixed_reference_date_interval',
          'target_plugin_configuration' => [
            'reference_date' => '2019-01-01',
            'interval' => [
              'interval' => '1',
              'period' => 'year',
            ],
          ],
        ],
      ],
      // Retired member.
      'RETIRED-MEMBERSHIP' => [
        'license_expiration' => [
          'target_plugin_id' => 'fixed_reference_date_interval',
          'target_plugin_configuration' => [
            'reference_date' => '2019-01-01',
            'interval' => [
              'interval' => '1',
              'period' => 'year',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Returns the values that should be used for missing products.
   *
   * @return array
   *   An array containing associative arrays with the field key/value pairs of
   *   the products that need to be created.
   */
  protected function getMissingProducts() {
    return [
      // Board membership.
      [
        'type' => 'individual_membership',
        'title' => 'Board of directors member',
        'status' => TRUE,
        'variation' => [
          'type' => 'individual_membership',
          'sku' => 'BOARD-MEMBERSHIP',
          'title' => 'Board of Directors Membership',
          'price' => [
            'number' => '0',
            'currency_code' => 'CAD',
          ],
          'license_type' => [
            'target_plugin_id' => 'role',
            'target_plugin_configuration' => [
              'license_role' => 'board_member',
            ],
          ],
          'license_expiration' => [
            'target_plugin_id' => 'fixed_reference_date_interval',
            'target_plugin_configuration' => [
              'reference_date' => '2019-01-01',
              'interval' => [
                'interval' => '1',
                'period' => 'year',
              ],
            ],
          ],
        ],
      ],
      // OAFCAAA membership.
      [
        'type' => 'individual_membership',
        'title' => 'OFCAAA Member',
        'status' => TRUE,
        'variation' => [
          'type' => 'individual_membership',
          'sku' => 'OFCAAA-MEMBERSHIP',
          'title' => 'OFCAAA Membership',
          'price' => [
            'number' => '0',
            'currency_code' => 'CAD',
          ],
          'license_type' => [
            'target_plugin_id' => 'role',
            'target_plugin_configuration' => [
              'license_role' => 'ofcaaa_member',
            ],
          ],
          'license_expiration' => [
            'target_plugin_id' => 'fixed_reference_date_interval',
            'target_plugin_configuration' => [
              'reference_date' => '2019-01-01',
              'interval' => [
                'interval' => '1',
                'period' => 'year',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Returns the values that should be used for missing products.
   *
   * @return array
   *   An array containing associative arrays with the field key/value pairs of
   *   the variations that need to be created.
   */
  protected function getMissingVariations() {
    return [
      // Lifetime membership.
      [
        'type' => 'individual_membership',
        'sku' => 'LIFETIME-MEMBERSHIP',
        'product_id' => 24,
        'title' => 'Lifetime Membership',
        'price' => [
          'number' => '0',
          'currency_code' => 'CAD',
        ],
        'license_type' => [
          'target_plugin_id' => 'role',
          'target_plugin_configuration' => [
            'license_role' => 'lifetime_member',
          ],
        ],
        'license_expiration' => [
          'target_plugin_id' => 'fixed_reference_date_interval',
          'target_plugin_configuration' => [
            'reference_date' => '2019-01-01',
            'interval' => [
              'interval' => '1',
              'period' => 'year',
            ],
          ],
        ],
      ],
    ];
  }

}
