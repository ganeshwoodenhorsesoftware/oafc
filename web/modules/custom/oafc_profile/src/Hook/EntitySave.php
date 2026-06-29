<?php

declare(strict_types=1);

namespace Drupal\oafc_profile\Hook;

use Drupal\node\NodeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Provides methods implementing hooks related to saving entities.
 */
class EntitySave {

  /**
   * Constructs a new EntitySave object.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   */
  public function __construct(
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator
  ) {
  }

  /**
   * Clears the caches that depend on the authors of the given node.
   *
   * We need to clear the cache entries for the view pages of the users related
   * to the profile node that was created or updated so that the rendered
   * content for the `profile` pseudo-field will be stale.
   * - The new author of the node, otherwise its view page will still render the
   *   instructions to create a new profile.
   * - If an update, the old author of the node, otherwise its view page will
   *   still render the teaser for the profile that is no longer associated with
   *   the user.
   *
   * It's a bit more complicated to directly clear the caches for the specific
   * pages from the `cache_page` bin because the `cid` contains context and
   * there may be potentially more than one entry. Needs a bit more research on
   * whether it can actually can be done and how. For now, the easiest way is to
   * clear the caches that contain the user(s) as one of its tags using tag
   * invalidation.
   *
   * For node updates, we could clear only the caches if the author has
   * changed. However, the title or other fields affecting the teaser view mode
   * used in the user view pages could have changed. Some testing would be
   * needed to see if that cache would be automatically cleared cascading up to
   * the user view page, and it could break if the cache page system changes
   * i.e. disable it to use another page caching system. For now, let's make
   * sure that it works always.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   */
  public function clearAuthorEntityCache(NodeInterface $node) {
    $this->cacheTagsInvalidator->invalidateTags(
      $node->getOwner()->getCacheTagsToInvalidate()
    );

    if (isset($node->original)) {
      $this->cacheTagsInvalidator->invalidateTags(
        $node->original->getOwner()->getCacheTagsToInvalidate()
      );
    }
  }

}
