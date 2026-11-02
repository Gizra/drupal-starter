<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\node\Entity\NodeType;

/**
 * Simple Sitemap related tests.
 */
class ServerGeneralSimpleSitemapTest extends ServerGeneralTestBase {

  // Node types that should be excluded.
  const EXCLUDED_TYPES = [];

  /**
   * Verfies all non exluded content type are indexed by simple sitemap.
   */
  public function testIndexedContentTypes() {
    $node_types = array_keys(NodeType::loadMultiple());

    $config_factory = \Drupal::configFactory();
    foreach ($node_types as $type) {
      $id = sprintf("simple_sitemap.bundle_settings.default.node.%s", $type);
      $config = $config_factory->get($id);

      if (!in_array($type, self::EXCLUDED_TYPES)) {
        $this->assertTrue($config->get('index'), sprintf('%s node type should be indexed by sitemap', $type));
      }
      else {
        $this->assertTrue(!$config->get('index'), sprintf('%s node type should not be indexed by sitemap', $type));
      }
    }
  }

}
