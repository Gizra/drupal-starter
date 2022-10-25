<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\intl_date\IntlDate;
use Drupal\media\MediaInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElasticSearchQuery;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\MediaLicenseBuilderTrait;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\RelatedContentTrait;
use Drupal\server_general\SidebarBuilderTrait;
use Drupal\server_general\SocialShareTrait;
use Drupal\server_general\TagBuilderTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    $elements = [];
    $elements[] = $media->get('thumbnail')->view([
      'label' => 'hidden',
      'type' => 'responsive_image',
      'settings' => [
        'responsive_image_style' => self::RESPONSIVE_IMAGE_STYLE_HERO,
        'image_link' => '',
      ],
    ]);

    $caption = $this->getTextFieldValue($media, 'field_caption');
    $elements[] = $this->wrapTextDecorations($caption, FALSE, FALSE, 'sm');

    $build[] = $this->wrapContainerVerticalSpacing($elements);
    return $build;
  }


}
