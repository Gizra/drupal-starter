<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\Core\Url;

/**
 * Helper method for building caption for a Media of type Video.
 *
 * @property \Drupal\media\IFrameUrlHelper $iFrameUrlHelper
 *
 * To use this trait it is assumed above service is present.
 */
trait MediaVideoTrait {

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
