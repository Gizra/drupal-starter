<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\intl_date\IntlDate;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\server_general\CardTrait;
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

  use CardTrait;
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
    return [
      '#theme' => 'server_theme_main_and_sidebar',
      '#main' => $this->wrapContainerVerticalSpacingBig($elements),
    ];
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
    $sidebar_elements = $this->wrapContainerVerticalSpacingBig($sidebar_elements);

    return [
      '#theme' => 'server_theme_main_and_sidebar',
      '#main' => $this->wrapContainerVerticalSpacingBig($main_elements),
      '#sidebar' => $this->buildCard($sidebar_elements),
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
    $image = $media instanceof MediaInterface ? $this->buildImageStyle($media, 'card', 'field_media_image') : NULL;
    $title = $entity->label();
    $url = $entity->toUrl();
    $summary = $this->buildProcessedText($entity, 'field_body', FALSE);
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');

    $element = $this->buildCardWithImageForNews(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

    $build[] = $element;

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
  public function buildFeatured(array $build, NodeInterface $entity) {
    $media = $this->getReferencedEntityFromField($entity, 'field_featured_image');
    $image = $media instanceof MediaInterface ? $this->buildImageStyle($media, 'card', 'field_media_image') : NULL;
    $title = $entity->label();
    $url = $entity->toUrl();
    $summary = $this->buildProcessedText($entity, 'field_body', FALSE);
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');

    $element = $this->buildCardWithImageHorizontalForNews(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

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
    $element = $this->buildCardSearchResult(
      $this->t('News'),
      $entity->label(),
      $entity->toUrl(),
      $this->buildProcessedText($entity, 'field_body', FALSE),
      $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date')
    );

    $build[] = $element;

    return $build;
  }

}
