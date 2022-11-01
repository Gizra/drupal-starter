<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\intl_date\IntlDate;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\SocialShareTrait;
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
  use SocialShareTrait;
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
    $element = $this->buildHeader($entity);
    $elements[] = $this->wrapContainerWide($element);

    // Main content and sidebar.
    $element = $this->buildMainAndSidebar($entity);
    $elements[] = $this->wrapContainerWide($element);

    $elements = $this->wrapContainerVerticalSpacing($elements);
    $build[] = $this->wrapContainerBottomPadding($elements);

    return $build;
  }

  /**
   * Build the header.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array
   *
   * @throws \IntlException
   */
  protected function buildHeader(NodeInterface $entity): array {
    $elements = [];

    $elements[] = $this->buildConditionalPageTitle($entity);

    // Show the node type as a label.
    $node_type = NodeType::load($entity->bundle());
    $elements[] = $this->buildLabelsFromText([$node_type->label()]);

    // Date.
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');
    $element = IntlDate::formatPattern($timestamp, 'long');
    // Make text bigger.
    $elements[] = $this->wrapTextDecorations($element, FALSE, FALSE, 'lg');

    $elements = $this->wrapContainerVerticalSpacing($elements);
    return $this->wrapContainerNarrow($elements);
  }

  /**
   * Build the Main content and the sidebar.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array
   *
   * @throws \IntlException
   */
  protected function buildMainAndSidebar(NodeInterface $entity): array {
    $main_elements = [];
    $sidebar_elements = [];

    $main_elements[] = $this->buildMediaResponsiveImage($entity, 'field_featured_image', self::RESPONSIVE_IMAGE_STYLE_HERO);
    // Get the body text, wrap it with `prose` so it's styled.
    $main_elements[] = $this->buildProcessedText($entity);

    // Get the tags, and social share.
    $sidebar_elements[] = $this->buildTags($entity);
    $sidebar_elements[] = $this->buildSocialShare($entity);

    return [
      '#theme' => 'server_theme_main_and_sidebar',
      '#main' => $this->wrapContainerVerticalSpacing($main_elements),
      '#sidebar' => $this->wrapContainerVerticalSpacing($sidebar_elements),
    ];

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

  /**
   * Build "Search index" view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildSearchIndex(array $build, NodeInterface $entity) {
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');

    $element = [
      '#theme' => 'server_theme_search_result',
      '#labels' => $this->buildLabelsFromText(['News']),
      '#title' => $entity->label(),
      '#summary' => 'Drupal 9 starter kit for efficient and streamlined development featuring TailwindCSS!',
      '#date' => IntlDate::formatPattern($timestamp, 'short'),
      '#url' => $entity->toUrl(),
    ];

    $build[] = $element;

    return $build;
  }

}
