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
   * The responsive image style to use on Hero.
   */
  const RESPONSIVE_IMAGE_STYLE_HERO = 'hero';

  /**
   * The responsive image style to use on Prose.
   */
  const RESPONSIVE_IMAGE_STYLE_PROSE = 'prose_image';

  /**
   * Build 'Embed' view mode.
   *
   * @param array $build
   *   The build array.
   * @param \Drupal\media\MediaInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildEmbed(array $build, MediaInterface $entity): array {
    $element = $this->getElement($entity, self::RESPONSIVE_IMAGE_STYLE_PROSE);
    $build[] = $element;

    return $build;
  }

  /**
   * Build 'Hero' view mode.
   *
   * @param array $build
   *   The build array.
   * @param \Drupal\media\MediaInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildHero(array $build, MediaInterface $entity): array {
    $element = $this->getElement($entity, self::RESPONSIVE_IMAGE_STYLE_HERO);

    // Wrap the image with rounded corners.
    $element['#image'] = $this->wrapRoundedCornersBig($element['#image']);
    $build[] = $element;

    return $build;

  }

  /**
   * Helper; Build the image, taking the responsive image style as argument.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   The entity.
   * @param string $responsive_image_style
   *   The responsive image style.
   *
   * @return array
   *   The render array.
   */
  protected function getElement(MediaInterface $entity, string $responsive_image_style): array {
    $image = $entity->get('field_media_image')->view([
      'label' => 'hidden',
      'type' => 'responsive_image',
      'settings' => [
        'responsive_image_style' => $responsive_image_style,
        'image_link' => '',
      ],
    ]);

    return [
      '#theme' => 'server_theme_media__image',
      '#image' => $image,
      '#caption' => $this->buildCaption($entity),
    ];
  }

}
