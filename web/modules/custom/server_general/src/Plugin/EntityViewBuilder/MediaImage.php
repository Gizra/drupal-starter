<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\media\MediaInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ThemeTrait\ElementMediaThemeTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;

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

  use ElementMediaThemeTrait;
  use ElementWrapThemeTrait;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

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
    $image = $this->buildResponsiveImage($entity, 'field_media_image', self::RESPONSIVE_IMAGE_STYLE_PROSE);
    $element = $this->buildElementImage(
      $image,
      $this->getTextFieldValue($entity, 'field_media_credit'),
      $this->getTextFieldValue($entity, 'field_caption'),
    );

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
    $image = $this->buildResponsiveImage($entity, 'field_media_image', self::RESPONSIVE_IMAGE_STYLE_HERO);
    $element = $this->buildElementImageWithCreditOverlay(
      $image,
      $this->getTextFieldValue($entity, 'field_media_credit'),
    );

    $build[] = $element;

    return $build;
  }

}
