<?php

namespace Drupal\oafc_profile\Hook;

use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Holds methods implementing hooks related to form alterations.
 */
class FormAlter {

  /**
   * Constructs a new EntityField object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account proxy.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected AccountProxyInterface $account,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {
  }

  /**
   * Alters the profile form to hide fields accessible only to admins.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alterForm(
    array &$form,
    FormStateInterface $form_state
  ) {
    $user = $this->account->getAccount();
    if (!$user instanceof UserInterface) {
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($user->id());
    }

    $roles = $user->getRoles();
    if (in_array('administrator', $roles)) {
      return;
    }

    // Let's not confuse end users with advanced settings.
    $form['field_meta_tags']['#access'] = FALSE;
    $form['revision_information']['#access'] = FALSE;

    // Only administrators can publish profiles.
    $form['status']['#access'] = FALSE;
  }

}
