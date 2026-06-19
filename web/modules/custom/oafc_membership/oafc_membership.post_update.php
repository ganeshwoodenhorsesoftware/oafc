<?php

/**
 * @file
 * Post update functions for the OAFC Membership module.
 */

/**
 * Corrects existing membership products, and creates missing ones.
 */
function oafc_membership_post_update_1() {
  \Drupal::service('oafc_membership.updater.post_1')->run();
}

/**
 * Removes Board of Directors product/variation.
 */
function oafc_membership_post_update_2() {
  \Drupal::service('oafc_membership.updater.post_2')->run();
}
