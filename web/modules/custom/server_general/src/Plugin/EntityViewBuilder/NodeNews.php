<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\SocialShareTrait;
use Drupal\server_general\TagTrait;
use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\ElementNodeNewsThemeTrait;
use Drupal\server_general\ThemeTrait\LineSeparatorThemeTrait;
use Drupal\server_general\ThemeTrait\LinkThemeTrait;
use Drupal\server_general\ThemeTrait\NewsTeasersThemeTrait;
use Drupal\server_general\ThemeTrait\SearchThemeTrait;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

  use ElementLayoutThemeTrait;
  use ElementNodeNewsThemeTrait;
  use EntityDateTrait;
  use LineSeparatorThemeTrait;
  use LinkThemeTrait;
  use NewsTeasersThemeTrait;
  use SearchThemeTrait;
  use SocialShareTrait;
  use TagTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * The renderer.
   *
   * This is not used in this file, but the `SearchThemeTrait` uses it.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->renderer = $container->get('renderer');

    return $plugin;
  }

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
    // The node's label.
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
    $label = $node_type->label();

    // The hero responsive image.
    $medias = $entity->get('field_featured_image')->referencedEntities();
    $image = $this->buildEntities($medias, 'hero');

    $element = $this->buildElementNodeNews(
      $entity->label(),
      $label,
      $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date'),
      $image,
      $this->buildProcessedText($entity),
      $this->buildTags($entity),
      $this->buildSocialShare($entity),
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
  public function buildTeaser(array $build, NodeInterface $entity) {
    $media = $this->getReferencedEntityFromField($entity, 'field_featured_image');
    $image = $media instanceof MediaInterface ? $this->buildImageStyle($media, 'card', 'field_media_image') : [];
    $title = $entity->label();
    $url = $entity->toUrl();
    $summary = $this->buildProcessedTextTrimmed($entity, 'field_body');
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');

    $element = $this->buildElementNewsTeaser(
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
   * Build "Featured" view mode.
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
    $summary = $this->buildProcessedText($entity, 'field_body');
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');

    $element = $this->buildElementNewsTeaserFeatured(
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
    $element = $this->buildElementSearchResult(
      $this->t('News'),
      $entity->label(),
      $entity->toUrl(),
      $this->buildProcessedText($entity, 'field_body'),
      $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date')
    );

    $build[] = $element;

    return $build;
  }

}
