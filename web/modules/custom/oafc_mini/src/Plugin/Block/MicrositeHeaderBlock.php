<?php

namespace Drupal\oafc_mini\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Provides the header of the current microsite.
 *
 * @Block(
 *   id = "microsite_header",
 *   admin_label = @Translation("Microsite Header"),
 *   category = @Translation("OAFC"),
 * )
 */
class MicrositeHeaderBlock extends BlockBase
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
    $node = Node::load(\Drupal::service('menu_section.helper')->getSectionId());
    if (!$node) {
      return [];
    }
    /** @var \Drupal\node\NodeViewBuilder $node_view_builder */
    $node_view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    return $node_view_builder->view($node, 'microsite');
  }

  /**
   * {@inheritdoc}
   */
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
