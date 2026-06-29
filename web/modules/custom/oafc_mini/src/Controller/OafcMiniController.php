<?php

namespace Drupal\oafc_mini\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\oafc_mini\Entity\OafcMiniEntity;
use Drupal\system\MenuInterface;

class OafcMiniController extends ControllerBase {

  public function add(MenuInterface $menu, $region) {
    $entity = OafcMiniEntity::create([
      'uid' => \Drupal::currentUser()->id(),
      'menu' => $menu->id(),
      'region' => $region,
    ]);
    return $this->entityFormBuilder()->getForm($entity);
  }

}
