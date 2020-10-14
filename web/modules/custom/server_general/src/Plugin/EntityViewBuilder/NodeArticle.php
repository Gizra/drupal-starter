<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\node\NodeInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;

/**
 * The "Node Article" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.article",
 *   label = @Translation("Node - Article"),
 *   description = "Node view builder for Article bundle."
 * )
 */
class NodeArticle extends NodeViewBuilderAbstract {

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
    $build['header'] = $this->buildHeroHeader($entity);
    $build['tags'] = $this->buildContentTags($entity);
    $build['body'] = $this->buildBody($entity);

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
