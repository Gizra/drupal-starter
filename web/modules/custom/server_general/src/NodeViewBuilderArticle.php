<?php

namespace Drupal\server_general;

use Drupal\node\NodeInterface;

/**
 * Class NodeViewBuilderArticle.
 */
class NodeViewBuilderArticle extends NodeViewBuilderAbstract {

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return mixed[]
   *   An array of elements for output on the page.
   */
  public function buildFull(array $build, NodeInterface $entity) {
    $build['server_theme_content__header'] = $this->buildHeroHeader($entity);
    $build['server_theme_content__tags'] = $this->buildContentTags($entity);
    $build['server_theme_content__body'] = $this->buildProcessedText($entity);

    return $build;
  }

}
