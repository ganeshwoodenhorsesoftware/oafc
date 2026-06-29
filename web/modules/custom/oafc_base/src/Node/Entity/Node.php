<?php

namespace Drupal\oafc_base\Node\Entity;

use Drupal\node\Entity\Node as NodeBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Overridden implementation of the node entity for OAFC.
 */
class Node extends NodeBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    if (!$entity_type->hasKey('owner')) {
      return $fields;
    }

    $fields[$entity_type->getKey('owner')]->addConstraint('OafcUniqueProfile');
    return $fields;
  }

}
