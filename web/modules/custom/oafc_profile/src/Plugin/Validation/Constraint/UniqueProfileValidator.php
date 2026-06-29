<?php

namespace Drupal\oafc_profile\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that the author of a profile does not have one already.
 */
class UniqueProfileValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $node = $items->getEntity();
    if ($node->bundle() !== 'profile') {
      return;
    }

    $user = $items->first()->entity;
    if (!$user) {
      return;
    }

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $query = $node_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'profile')
      ->condition('uid', $user->id());

    if (!$node->isNew()) {
      $query->condition('nid', $node->id(), '!=');
    }

    $node_ids = $query->execute();
    if (count($node_ids) === 0) {
      return;
    }

    $this->context->addViolation(
      $constraint->message,
      [
        '@user_name' => $user->getDisplayName(),
        '@user_link' => $user->toUrl()->toString(),
        '@node_link' => $node_storage->load(current($node_ids))->toUrl()->toString(),
      ]
    );
  }

}
