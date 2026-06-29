<?php

namespace Drupal\webform_vertical_tabs;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

class WebformVerticalTabsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('plugin.manager.element_info')->setClass(AlterableElementInfoManager::class);
  }

}
