<?php

namespace Drupal\oafc_mini;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OAFC mini entity.
 *
 * @see \Drupal\oafc_mini\Entity\OafcMiniEntity.
 */
class OafcMiniAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer oafc mini entities')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    /** @var \Drupal\oafc_mini\Entity\OafcMiniEntityInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowed();

      case 'update':
        return AccessResult::allowedIf($account->id() == $entity->getOwnerId() && $account->hasPermission('edit own oafc mini entities'))
          ->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete oafc mini entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add oafc mini entities');
  }

}
