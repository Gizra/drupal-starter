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
    // Header.
    $build[] = $this->buildHeroHeader($entity);

    // Tags.
    $build[] = $this->buildContentTags($entity);

    // Body.
    $build[] = $this->buildBody($entity);

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
    $image_info = $this->getImageAndAlt($entity, 'field_image');

    $element = parent::buildTeaser($build, $entity);
    $element += [
      '#image' => $image_info['url'],
      '#image_alt' => $image_info['alt'],
      '#tags' => $this->buildTags($entity),
      '#body' => $this->buildProcessedText($entity, 'body', TRUE),
    ];

    $build[] = $element;

    return $build;
  }

}
