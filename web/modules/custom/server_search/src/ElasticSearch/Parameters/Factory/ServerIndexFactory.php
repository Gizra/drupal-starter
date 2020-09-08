<?php

namespace Drupal\server_search\ElasticSearch\Parameters\Factory;

use Drupal\elasticsearch_connector\ElasticSearch\Parameters\Factory\IndexFactory;
use Drupal\search_api\IndexInterface;

/**
 * Create Elasticsearch Indices.
 */
class ServerIndexFactory extends IndexFactory {

  /**
   * Helper function. Returns the Elasticsearch name of an index.
   *
   * Makes sure that the index name contains the right environment on Pantheon.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Index object.
   *
   * @return string
   *   The name of the index on the Elasticsearch server. Includes a prefix for
   *   uniqueness, the database name, and index machine name.
   */
  public static function getIndexName(IndexInterface $index) {

    $options = \Drupal::database()->getConnectionOptions();
    $site_database = $options['database'];

    $index_machine_name = is_string($index) ? $index : $index->id();

    // The structure of the index name is the following:
    // - elasticsearch_index_pantheon_server_dev
    // - elasticsearch_index_pantheon_server_test
    // - elasticsearch_index_pantheon_server_live
    // In every case, the first variant is in config / features.
    if (isset($_ENV['es_env'])) {
      $index_machine_name = str_replace('_dev', '_' . $_ENV['es_env'], $index_machine_name);
    }

    return strtolower(preg_replace(
      '/[^A-Za-z0-9_]+/',
      '',
      'elasticsearch_index_' . $site_database . '_' . $index_machine_name
    ));
  }

}
