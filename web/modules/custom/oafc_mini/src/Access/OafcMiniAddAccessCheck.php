<?php

namespace Drupal\oafc_mini\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\system\MenuInterface;

class OafcMiniAddAccessCheck {

  public function access(AccountInterface $account, MenuInterface $menu = NULL) {
    return AccessResult::allowedIf($menu && (int) $account->id() === (int) $menu->getThirdPartySetting('menu_section', 'uid'));
  }

}
