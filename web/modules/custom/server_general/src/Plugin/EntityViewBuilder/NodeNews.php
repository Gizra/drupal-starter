<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;

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
    $this->messenger()->addMessage('Add your Node News elements in \Drupal\server_general\Plugin\EntityViewBuilder\NodeNews');

    // Header.
    $element = $this->buildHeroImageAndTitle($entity, 'field_featured_image');
    // No wrapper, as the hero image takes the full width.
    $build[] = $element;

    // Tags.
    $element=   $this->buildTags($entity);
    $build[] =  $this->wrapElementWideContainer($element);

    // Get the body text, wrap it with `prose` so it's styled.
    $element = $this->buildProcessedText($entity);
    $element = $this->wrapElementProseText($element);
    $build[] = $this->wrapElementWideContainer($element);

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
    $image_info = $this->getMediaImageAndAlt($entity, 'field_featured_image');

    $element = parent::buildTeaser($build, $entity);
    $element += [
      '#image' => $image_info['url'] ?? NULL,
      '#image_alt' => $image_info['alt'] ?? NULL,
      '#tags' => $this->buildTags($entity),
      '#body' => $this->buildProcessedText($entity),
    ];

    $build[] = $element;

    return $build;
  }

  /**
   * Build card view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildCard(array $build, NodeInterface $entity) {
    $media = $this->getReferencedEntityFromField($entity, 'field_featured_image');

    $body = $this->buildProcessedText($entity);
    $body = $this->wrapElementProseText($body);

    $element = [
      '#theme' => 'server_theme_card',
      '#title' => $entity->label(),
      '#image' => $media instanceof MediaInterface ? $this->buildImageStyle($media, 'large', 'field_media_image') : NULL,
      '#url' => $entity->toUrl(),
      '#body' => $body,
    ];
    $build[] = $element;
    return $build;
  }

}
