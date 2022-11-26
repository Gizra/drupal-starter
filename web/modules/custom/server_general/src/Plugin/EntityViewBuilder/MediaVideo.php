<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
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

    $element = [
      '#theme' => 'server_theme_video',
      '#video' => $this->buildVideo($url, self::VIDEO_FULL_MAX_WIDTH, self::VIDEO_FULL_MAX_HEIGHT, TRUE),
      '#caption' => $this->getTextFieldValue($entity, 'field_caption'),
    ];
    $build[] = $element;
    return $build;
  }

  /**
   * Prepare video render array.
   *
   * @param string $url
   *   Video url.
   * @param int $width
   *   Iframe width.
   * @param int $height
   *   Iframe height.
   * @param bool $iframe_full_width
   *   Defines if iframe has 100% width/height.
   *
   * @return array
   *   The render array.
   */
  protected function buildVideo(string $url, int $width, int $height, bool $iframe_full_width = FALSE): array {
    $url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => $url,
        'max_width' => $width,
        'max_height' => $height,
        'hash' => $this->iFrameUrlHelper->getHash($url, $width, $height),
      ],
    ]);

    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $url->toString(),
        'frameborder' => 0,
        'scrolling' => FALSE,
        'width' => $iframe_full_width ? '100%' : $width,
        'height' => $iframe_full_width ? '100%' : $height,
        'allowtransparency' => TRUE,
        'class' => ['media-oembed-content'],
        'title' => $this->t('Video frame for @url', [
          '@url' => $url->toString(),
        ]),
      ],
      '#attached' => [
        'library' => [
          'media/oembed.formatter',
        ],
      ],
    ];
  }

}
