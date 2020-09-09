<?php

namespace Drupal\server_search;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the index builder service.
 */
class ServerElasticSearchServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replaces IndexFactory in order to modify index name on the fly per env.
    $definition = $container->getDefinition('elasticsearch_connector.index_factory');
    $definition->setClass('Drupal\server_search\ElasticSearch\Parameters\Factory\ServerIndexFactory');
  }

}
