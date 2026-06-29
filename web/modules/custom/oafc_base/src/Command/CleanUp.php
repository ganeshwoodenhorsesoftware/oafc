<?php

declare(strict_types=1);

namespace Drupal\oafc_base\Command;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Provides a command for deleting test users and other entities before launch.
 *
 * It should probably be deleted after going live to prevent from accidentally
 * deleting content on production.
 */
class CleanUp extends DrushCommands {

  /**
   * Constructs a new CleanUp object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager
  ) {
  }

  /**
   * Deletes test users and other entities.
   *
   * This is only meant to be run once before going live. It should then be
   * removed from the code.
   *
   * @param string $type
   *   The ID of the entity type to delete, or 'all' to delete all supported
   *   types.
   *
   * @throws \InvalidArgumentException
   *   When the given entity type ID is not supported.
   *
   * @command oafc-base:clean-up-entities
   */
  public function cleanUp($type): void {
    switch ($type) {
      case 'commerce_license':
        $this->deleteEntities('commerce_license');
        break;

      case 'commerce_order':
        $this->deleteOrders();
        break;

      case 'webform_submission':
        $this->deleteEntities('webform_submission');
        break;

      case 'user':
        $this->deleteUsers();
        break;

      case 'all':
        $this->deleteEntities('commerce_license');
        $this->deleteOrders();
        $this->deleteEntities('webform_submission');
        $this->deleteUsers();
        break;

      default:
        $allowed_types = [
          'all',
          'webform_submission',
          'user',
          'commerce_license',
          'commerce_order',
        ];
        throw new \InvalidArgumentException(sprintf(
          'No matched type. The argument shoud be one of: %s',
          implode(', ', $allowed_types)
        ));
    }
  }

  /**
   * Removes all user roles from all users.
   *
   * The users that are left from the clean up still have some roles such as
   * membership roles. They shouldn't since they will need to get them again
   * through licenses.
   *
   * This is only meant to be run once before going live. It should then be
   * removed from the code.
   *
   * @command oafc-base:clean-up-roles
   */
  public function removeUserRoles(): void {
    $storage = $this->entityTypeManager->getStorage('user');
    $user_ids = $storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', [0], 'NOT IN')
      ->execute();
    if (!$user_ids) {
      return;
    }

    foreach ($storage->loadMultiple($user_ids) as $user) {
      $role_ids = $user->getRoles(TRUE);
      foreach ($role_ids as $role_id) {
        if ($role_id === 'administrator') {
          continue;
        }

        $user->removeRole($role_id);
      }
      $storage->save($user);
    }
  }

  /**
   * Deletes all entities of the given type.
   */
  protected function deleteEntities(
    string $entity_type_id,
    array|null $entity_ids = NULL
  ): void {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    $entities = NULL;
    if ($entity_ids === NULL) {
      $entities = $storage->loadMultiple();
    }
    else {
      $entities = $storage->loadMultiple($entity_ids);
    }

    if (!$entities) {
      $this->logger()->success(dt(
        'No entities of type "@entity_type_id" exist.',
        ['@entity_type_id' => $entity_type_id]
      ));
      return;
    }

    $storage->delete($entities);

    $this->logger()->success(dt(
      'All entities of type "@entity_type_id" have been deleted.',
      ['@entity_type_id' => $entity_type_id]
    ));
  }

  /**
   * Deletes all orders and related entities.
   */
  protected function deleteOrders(): void {
    $this->deleteEntities('commerce_payment');
    $this->deleteEntities('commerce_shipment');
    $this->deleteEntities('commerce_order_item');
    $this->deleteEntities('commerce_order');
  }

  /**
   * Deletes all users that are not in the exclude list.
   *
   * It reassigns the ownership of content belonging to the users being deleted.
   */
  protected function deleteUsers(): void {
    $storage = $this->entityTypeManager->getStorage('user');
    $user_ids = $storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('mail', $this->excludedUserEmails(), 'NOT IN')
      ->execute();
    if (!$user_ids) {
      $this->logger()->success(dt('No entities of type "user" exist.'));
      return;
    }

    $this->reassignOwnerships($user_ids);
    $this->deleteEntities('user', $user_ids);
  }

  /**
   * Returns the emails that define the users that should not be deleted.
   *
   * @return array
   *   A numerical array containing the emails.
   */
  protected function excludedUserEmails(): array {
    return [
      // OAFC users.
      'Shelley.Molica@oafc.on.ca',
      'Laura.Aivaliotis@oafc.on.ca',
      'mark.tishman@oafc.on.ca',
      'helaina.mulville@oafc.on.ca',
      'linda.ritchie@oafc.on.ca',
      // Acro Media users.
      'cbildstein@acromediainc.com',
      'dbozelos@acromedia.com',
      'bhodge@acromedia.com',
      'efreistatter@acromedia.com',
    ];
  }

  /**
   * Reassigns ownership of content belonging to the given users.
   *
   * @param array $user_ids
   *   The IDs of the users for which to reassign the content.
   */
  protected function reassignOwnerships(array $user_ids): void {
    $entity_type_ids = [
      'node',
      'commerce_product',
      'commerce_product_variation',
      'webform',
    ];

    $new_owner_email = 'Laura.Aivaliotis@oafc.on.ca';
    $new_owner_id = $this->getNewOwnerId($new_owner_email);

    foreach ($entity_type_ids as $entity_type_id) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entity_ids = $storage
        ->getQuery()
        ->accessCheck(FALSE)
        // All of the entity types that we are reassigning have the `uid` as the
        // `owner` entity key. No need to write more complicated code to fetch
        // it from the entity type, and that would also not work for the
        // `webform` entity type that does not properly define it.
        ->condition('uid', $user_ids, 'IN')
        ->execute();
      if (!$entity_ids) {
        return;
      }

      foreach ($storage->loadMultiple($entity_ids) as $entity) {
        // All of the entity types that we are reassigning implement the
        // `Drupal\user\EntityOwnerInterface` interface.
        $entity->setOwnerId($new_owner_id);
        $storage->save($entity);
      }

      $this->logger()->success(dt(
        'All entities of type "@entity_type_id" that belonged to users that will be deleted, and to the anonymous androot users, have been reassigned to the user with email "@new_owner_email".',
        [
          '@entity_type_id' => $entity_type_id,
          '@new_owner_email' => $new_owner_email,
        ]
      ));
    }
  }

  /**
   * Returns the ID of the user that all ownerships should be reassigned to.
   *
   * @param string $new_owner_email
   *   The email of the user.
   *
   * @return string
   *   The user ID.
   *
   * @throws \RuntimeException
   *   When no user was found for the given email.
   */
  protected function getNewOwnerId(string $new_owner_email): string {
    $storage = $this->entityTypeManager->getStorage('user');
    $user_ids = $storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('mail', $new_owner_email)
      ->execute();
    if (!$user_ids) {
      throw new \RuntimeException(sprintf(
        'No user found with email "%s"'
      ));
    }

    return current($user_ids);
  }

}
