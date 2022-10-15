<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\node\NodeInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\TagBuilderTrait;

/**
 * An abstract class for Node View Builders classes.
 */
abstract class NodeViewBuilderAbstract extends EntityViewBuilderPluginAbstract {

  use ElementWrapTrait;
  use ProcessedTextBuilderTrait;
  use TagBuilderTrait;

  /**
   * The responsive image style to use on Hero images.
   */
  const RESPONSIVE_IMAGE_STYLE_HERO = 'hero';

  /**
   * Build the Hero Header section, with Title, and Background Image.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param string $image_field_name
   *   Optional; The field name. Defaults to "field_image".
   *
   * @return array
   *   A render array.
   */
  protected function buildHeroImageAndTitle(NodeInterface $entity, string $image_field_name = 'field_image'): array {
    return [
      '#theme' => 'server_theme_hero_image',
      '#title' => $entity->label(),
      '#image' => $this->buildMediaResponsiveImage($entity, $image_field_name, self::RESPONSIVE_IMAGE_STYLE_HERO),
    ];
  }

}
