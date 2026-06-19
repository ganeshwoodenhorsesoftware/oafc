<?php

namespace Drupal\webform_vertical_tabs;


use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

class AlterableElementInfoManager extends ElementInfoManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ThemeHandlerInterface|CacheTagsInvalidatorInterface $theme_handler, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager) {
    parent::__construct($namespaces, $cache_backend, $theme_handler, $module_handler, $theme_manager);
    $this->alterInfo('element');
  }

}
