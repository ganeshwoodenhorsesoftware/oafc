<?php

namespace Drupal\oafc_membership\Plugin\Commerce\LicenseType;

use Drupal\commerce_license\Plugin\Commerce\LicenseType\Role as RoleBase;

/**
 * Overridden implementation of the Role license type.
 *
 * All that we do here is to override the workflow for the Role license
 * type. This is not configurable at the moment. At the moment there is no need
 * to create a new plugin ID; we may need in the future if we have other
 * licenses that follow a different workflow.
 */
class Role extends RoleBase {

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId() {
    return 'license_oafc_membership';
  }

}
