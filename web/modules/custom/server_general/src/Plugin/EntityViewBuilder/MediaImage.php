<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\media\MediaInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementWrapTrait;

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

  /**
   * The responsive image style to use on Hero images.
   */
  const RESPONSIVE_IMAGE_STYLE_HERO = 'hero';

  /**
   * Build 'Card' view mode.
   *
   * @param array $build
   *   The build array.
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return array
   *   The render array.
   */
  public function buildFull(array $build, MediaInterface $media): array {
    $image = $media->get('thumbnail')->view([
      'label' => 'hidden',
      'type' => 'responsive_image',
      'settings' => [
        'responsive_image_style' => self::RESPONSIVE_IMAGE_STYLE_HERO,
        'image_link' => '',
      ],
    ]);

    $element = [
      '#theme' => 'server_theme_image_and_caption',
      '#image' => $image,
      '#caption' => $this->getTextFieldValue($media, 'field_caption'),
    ];

    $build[] = $element;
    return $build;
  }

}
