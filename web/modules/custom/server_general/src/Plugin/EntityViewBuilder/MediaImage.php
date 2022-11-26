<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\media\MediaInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\MediaCaptionTrait;

/**
 * The "Media: Image" plugin.
 *
 * @EntityViewBuilder(
 *   id = "media.image",
 *   label = @Translation("Media - Image"),
 *   description = "Media view builder for Image bundle."
 * )
 */
class MediaImage extends EntityViewBuilderPluginAbstract {

  use ElementWrapTrait;
  use MediaCaptionTrait;

  /**
   * The responsive image style to use.
   */
  const RESPONSIVE_IMAGE_STYLE = 'prose_image';

  /**
   * Build 'Card' view mode.
   *
   * @param array $build
   *   The build array.
   * @param \Drupal\media\MediaInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildFull(array $build, MediaInterface $entity): array {
    $image = $entity->get('thumbnail')->view([
      'label' => 'hidden',
      'type' => 'responsive_image',
      'settings' => [
        'responsive_image_style' => self::RESPONSIVE_IMAGE_STYLE,
        'image_link' => '',
      ],
    ]);

    $element = [
      '#theme' => 'server_theme_media__image',
      '#image' => $image,
      '#caption' => $this->buildCaption($entity),
    ];

    $build[] = $element;
    return $build;
  }

}
