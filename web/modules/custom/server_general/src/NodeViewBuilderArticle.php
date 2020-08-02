<?php

namespace Drupal\server_general;

use Drupal\node\NodeInterface;

/**
 * Class NodeViewBuilderArticle.
 */
class NodeViewBuilderArticle extends NodeViewBuilderAbstract {

  use TagBuilderTrait;

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, NodeInterface $entity) {
    $build['server_theme_content__header'] = $this->buildHeroHeader($entity);
    $build['server_theme_content__tags'] = $this->buildContentTags($entity);
    $build['server_theme_content__body'] = $this->buildBody($entity);

    return $build;
  }

  /**
   * Default build in "Teaser" view mode.
   *
   * Show nodes as "cards".
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildTeaser(array $build, NodeInterface $entity) {
    $build = parent::buildTeaser($build, $entity);

    list($image, $image_alt) = $this->buildImage($entity, 'field_image');
    $build['#image'] = $image;
    $build['#image_alt'] = $image_alt;
    $build['#tags'] = $this->buildTags($entity);
    $build['#body'] = $this->buildProcessedText($entity, 'body', TRUE);

    return $build;
  }

}
