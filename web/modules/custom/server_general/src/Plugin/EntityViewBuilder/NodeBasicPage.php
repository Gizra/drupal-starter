<?php

namespace Drupal\server_general;

use Drupal\node\NodeInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;

/**
 * Class NodeBasicPage.
 */
class NodeBasicPage extends NodeViewBuilderAbstract {

  /**
   * Build "Basic Page" in "Full" view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array[]
   *   A renderable array.
   */
  public function buildFull(array $build, NodeInterface $entity) {
    $build['server_theme_content__hero_header'] = $this->buildHeroHeader($entity, 'field_basic_page_header_image');
    $build['server_theme_content__body'] = $this->buildBody($entity);

    return $build;
  }

}
