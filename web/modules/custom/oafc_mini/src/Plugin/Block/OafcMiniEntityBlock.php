<?php

namespace Drupal\oafc_mini\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\oafc_mini\Entity\OafcMiniEntity;

/**
 * Provides images in the right menu and regino
 *
 * @Block(
 *   id = "oafc_mini_entity_block",
 *   admin_label = @Translation("OAFC Mini Entity block"),
 *   category = @Translation("Misc"),
 * )
 */
class OafcMiniEntityBlock extends BlockBase
{

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build()
  {
    if (isset($this->configuration['region'])  && ($current_menu = \Drupal::service('cache_context.current_menu')->getContext())) {
      $ids = \Drupal::entityQuery('oafc_mini')
        ->condition('region', $this->configuration['region'])
        ->condition('menu', $current_menu)
        ->sort('weight')
        ->execute();
      if ($entities = OafcMiniEntity::loadMultiple($ids)) {
        return \Drupal::entityTypeManager()->getViewBuilder('oafc_mini')->viewMultiple($entities);
      } else {
        // @TODO: Figure out what/where to print out for a placeholder.
        // So user can easily update content.
        return ['#markup' => 'Mini Entity Placeholder'];
      }
    }
    return [];
  }

  public function blockAccess(AccountInterface $account)
  {
    return AccessResult::allowedIf(\Drupal::service('menu_section.helper')->getSection())
      ->addCacheContexts(['current_menu']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts()
  {
    return Cache::mergeContexts(['current_menu'], parent::getCacheContexts());
  }
}
