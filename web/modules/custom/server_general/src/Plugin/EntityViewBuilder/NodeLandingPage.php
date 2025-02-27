<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\node\NodeInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;

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

  use TitleAndLabelsThemeTrait;

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
    if (!$this->getBooleanFieldValue($entity, 'field_is_title_hidden')) {
      $element = $this->buildPageTitle($entity->label());
      $elements[] = $this->wrapContainerWide($element);
    }

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $paragraphs */
    $paragraphs = $entity->get('field_paragraphs');
    // Paragraphs.
    $element = $this->buildReferencedEntities($paragraphs);
    $elements[] = $this->wrapContainerVerticalSpacingBig($element);

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    // Add bottom padding, so there's some padding between all the paragraphs
    // and the footer.
    $build[] = $this->wrapConditionalContainerBottomPadding($elements, $paragraphs);

    return $build;
  }

}
