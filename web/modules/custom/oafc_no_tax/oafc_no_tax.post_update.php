<?php

/**
 * @file
 * Post-update functions for the OAFC No Tax module.
 */

/**
 * Sets the default HST number for all stores.
 */
function oafc_no_tax_post_update_1() {
  $storage = \Drupal::service('entity_type.manager')
    ->getStorage('commerce_store');

  foreach ($storage->loadMultiple() as $store) {
    $store->set('field_hst_number', '122021785');
    $storage->save($store);
  }
}
