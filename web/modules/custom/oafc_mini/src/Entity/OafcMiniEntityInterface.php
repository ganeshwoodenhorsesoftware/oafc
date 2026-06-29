<?php

namespace Drupal\oafc_mini\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OAFC mini entities.
 *
 * @ingroup oafc_mini
 */
interface OafcMiniEntityInterface extends ContentEntityInterface, EntityOwnerInterface {

}
