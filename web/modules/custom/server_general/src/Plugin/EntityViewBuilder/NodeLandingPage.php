<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\node\NodeInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\TitleAndLabelsTrait;

/**
 * The "Node Landing Page" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.landing_page",
 *   label = @Translation("Node - Landing Page"),
 *   description = "Node view builder for Landing Page bundle."
 * )
 */
class NodeLandingPage extends NodeViewBuilderAbstract {

  use TitleAndLabelsTrait;

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
    $elements = [];
    // Show the page title, unless it was set to be hidden.
    $element = $this->buildConditionalPageTitle($entity);
    $elements[] = $this->wrapContainerWide($element);

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $paragraphs */
    $paragraphs = $entity->get('field_paragraphs');
    // Paragraphs.
    $element = $this->buildReferencedEntities($paragraphs);
    $elements[] = $this->wrapContainerVerticalSpacingBig($element);

    $build[] = $this->wrapContainerVerticalSpacingBig($elements);

    return $build;
  }

}
