<?php

namespace Drupal\oafc_license_notifications\Entity;

use Drupal\commerce_license\Entity\LicenseInterface as BaseLicenseInterface;

/**
 * Extended interface for License entities with notification data support.
 *
 * This interface extends the base LicenseInterface to add methods for
 * managing notification tracking data.
 */
interface LicenseInterface extends BaseLicenseInterface {

  /**
   * Gets a license data value with the given key.
   *
   * Used to store temporary data during license processing.
   *
   * @param string $key
   *   The key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function getData($key, $default = NULL);

  /**
   * Sets a license data value with the given key.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setData($key, $value);

  /**
   * Unsets a license data value with the given key.
   *
   * @param string $key
   *   The key.
   *
   * @return $this
   */
  public function unsetData($key);

}
