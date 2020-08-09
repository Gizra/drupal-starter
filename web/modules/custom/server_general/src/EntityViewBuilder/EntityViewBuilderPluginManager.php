<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for Entity view builder.
 */
class EntityViewBuilderPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EntityViewBuilder', $namespaces, $module_handler, 'Drupal\server_general\EntityViewBuilder\EntityViewBuilderPluginInterface', 'Drupal\server_general\Annotation\EntityViewBuilder');
    $this->alterInfo('server_general_entity_view_build_info');
    $this->setCacheBackend($cache_backend, 'server_general_entity_view_build_plugins');
  }

}
