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
    $build['server_theme_content__header'] = $this->buildCollectionHeader($entity);
    $build['server_theme_content__tags'] = $this->buildContentTags($entity);
    $build['server_theme_content__image_and_teaser'] = $this->buildImageAndTeaser($entity, 'field_collection_preview_image');
    $build['server_theme_content__buttons'] = $this->buildCollectionButtons($entity);
    $build['server_theme_collections'] = $this->buildCollectionElements($entity);

    return $build;
  }

}
