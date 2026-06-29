<?php

namespace Drupal\oafc_membership;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service provider for the OAFC membership module.
 */
class OafcMembershipServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   *
   * Replaces with the classes for the following services with our own.
   * - The order subscriber that handles license creation/activation during
   *   order placement.
   * - The license availability checker.
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('commerce_license.order_subscriber')
      ->setClass('\Drupal\oafc_membership\EventSubscriber\OrderSubscriber');

    $container
      ->getDefinition('commerce_license.license_availability_checker_existing')
      ->setClass('\Drupal\oafc_membership\CommerceLicense\LicenseAvailabilityCheckerExistingRights');
  }

}
