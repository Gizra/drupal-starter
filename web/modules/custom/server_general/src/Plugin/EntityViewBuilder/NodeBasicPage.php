<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\node\NodeInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;

/**
 * The "Node Basic Page" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.page",
 *   label = @Translation("Node - Basic page"),
 *   description = "Node view builder for Basic page bundle."
 * )
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
    $this->messenger()->addMessage('Add your Node Article elements in \Drupal\server_general\Plugin\EntityViewBuilder\NodeBasicPage');

    // Hero header.
    $build[] = $this->buildHeroHeader($entity, 'field_basic_page_header_image');

    // Body.
    $element = $this->buildProcessedText($entity);
    $build[] = $this->wrapElementWideContainer($element);

    return $build;
  }

}
