<?php

namespace Drupal\oafc_profile\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the user has another profile.
 *
 * phpcs:disable
 * @Constraint(
 *   id = "OafcUniqueProfile",
 *   label = @Translation("Unique industry member profile field constraint", context = "Validation"),
 * )
 * phpcs:enable
 */
class UniqueProfileConstraint extends Constraint {

  /**
   * The message to display when validation fails.
   *
   * @var string
   */
  public $message = 'User <a href="@user_link" target="_blank">@user_name</a> already has another <a href="@node_link" target="_blank">associated profile</a>.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\oafc_profile\Plugin\Validation\Constraint\UniqueProfileValidator';
  }

}
