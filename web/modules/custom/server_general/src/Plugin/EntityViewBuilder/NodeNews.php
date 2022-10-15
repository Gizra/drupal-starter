<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\intl_date\IntlDate;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\TitleAndLabelsTrait;

/**
 * The "Node News" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.news",
 *   label = @Translation("Node - News"),
 *   description = "Node view builder for News bundle."
 * )
 */
class NodeNews extends NodeViewBuilderAbstract {

  use EntityDateTrait;
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

    // Header.
    $element = $this->buildHeroImageAndTitle($entity, 'field_featured_image');
    // No wrapper, as the hero image takes the full width.
    $elements[] = $element;



    // Get the body text, wrap it with `prose` so it's styled.
    $element = $this->buildProcessedText($entity);
    $elements[] = $this->wrapContainerWide($element);

    $build[] = $this->wrapContainerVerticalSpacing($elements);

    return $build;
  }

  /**
   * Build Teaser view mode.
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
    $media = $this->getReferencedEntityFromField($entity, 'field_featured_image');
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');

    $element = [
      '#theme' => 'server_theme_card',
      '#title' => $entity->label(),
      '#image' => $media instanceof MediaInterface ? $this->buildImageStyle($media, 'card', 'field_media_image') : NULL,
      '#date' => IntlDate::formatPattern($timestamp, 'long'),
      '#url' => $entity->toUrl(),
    ];
    $build[] = $element;

    return $build;
  }

  protected function buildHeader(NodeInterface $entity): array {
    $main_elements = [];
    $sidebar_elements= [];

    $elements= [];

    // Show the node type as a label.
    $node_type = NodeType::load($entity->bundle());

    // Labels.
    $elements[] = $this->buildTitleAndLabelsFromText($entity, [$node_type->label()]);

    // Date.
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');
    $elements[] = [
      '#theme' => 'server_theme_text_large',
      '#text' => IntlDate::formatPattern($timestamp, 'long'),
    ];




  }


}
