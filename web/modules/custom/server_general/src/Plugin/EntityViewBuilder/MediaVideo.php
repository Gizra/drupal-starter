<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\media\MediaInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ThemeTrait\ElementMediaThemeTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "Media: Video" plugin.
 *
 * @EntityViewBuilder(
 *   id = "media.video",
 *   label = @Translation("Media - Video"),
 *   description = "Media view builder for Video bundle."
 * )
 */
class MediaVideo extends EntityViewBuilderPluginAbstract {

  use ElementMediaThemeTrait;
  use ElementWrapThemeTrait;

  // Update from design as needed.
  const VIDEO_FULL_MAX_WIDTH = 1920;
  const VIDEO_FULL_MAX_HEIGHT = 1080;

  /**
   * The iFrame URL helper service, used for embedding videos.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->iFrameUrlHelper = $container->get('media.oembed.iframe_url_helper');
    return $plugin;
  }

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
    $url = $entity->get('field_media_oembed_video')->getString();
    if (empty($url)) {
      return $build;
    }

    $element = $this->buildElementVideo(
      $url,
      self::VIDEO_FULL_MAX_WIDTH,
      self::VIDEO_FULL_MAX_HEIGHT,
      TRUE,
      $this->getTextFieldValue($entity, 'field_media_credit'),
      $this->getTextFieldValue($entity, 'field_caption'),
    );

    $build[] = $element;

    return $build;
  }

}
