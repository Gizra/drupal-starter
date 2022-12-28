<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\intl_date\IntlDate;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\LineSeparatorTrait;
use Drupal\server_general\LinkTrait;
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
  use LineSeparatorTrait;
  use LinkTrait;
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

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
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
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'lg');

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
    $social_share_elements = [];

    // Show the featured image using the `hero` view mode.
    // See MediaImage::buildHero.
    $medias = $entity->get('field_featured_image')->referencedEntities();
    $main_elements[] = $this->buildEntities($medias, 'hero');

    // Get the body text, wrap it with `prose` so it's styled.
    $main_elements[] = $this->buildProcessedText($entity);

    // Get the tags, and social share.
    $sidebar_elements[] = $this->buildTags($entity);

    // Add a line separator above the social share buttons.
    $social_share_elements[] = $this->buildLineSeparator();
    $social_share_elements[] = $this->buildSocialShare($entity);

    $sidebar_elements[] = $this->wrapContainerVerticalSpacing($social_share_elements);

    return [
      '#theme' => 'server_theme_main_and_sidebar',
      '#main' => $this->wrapContainerVerticalSpacingBig($main_elements),
      '#sidebar' => $this->wrapContainerVerticalSpacingBig($sidebar_elements),
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
      '#date' => IntlDate::formatPattern($timestamp, 'short'),
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
    $elements = [];
    // Labels.
    $elements[] = $this->buildLabelsFromText([$this->t('News')]);

    // Title as link, wrapped in h3 tag.
    $element = $this->buildLink($entity->toUrl(), $entity->label(), 'dark-gray');
    $element = $this->wrapTextResponsiveFontSize($element, '3xl');
    $element = $this->wrapTextFontWeight($element, 'bold');
    $elements[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => render($element),
    ];

    // Summary.
    $element = $this->buildProcessedText($entity, 'field_body', FALSE);
    $elements[] = $this->wrapTextLineClamp($element, 4);

    // Date.
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');
    $element = IntlDate::formatPattern($timestamp, 'short');
    $element = $this->wrapTextColor($element, 'light-gray');
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    $element = [
      '#theme' => 'server_theme_card',
      '#items' => $this->wrapContainerVerticalSpacing($elements),
    ];

    $build[] = $element;

    return $build;
  }

}
