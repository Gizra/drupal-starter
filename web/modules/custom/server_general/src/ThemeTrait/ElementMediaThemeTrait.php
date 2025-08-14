<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Url;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;

/**
 * Helper method for building caption for a Media of type Video.
 *
 * @property \Drupal\media\IFrameUrlHelper $iFrameUrlHelper
 *
 * To use this trait it is assumed above service is present.
 */
trait ElementMediaThemeTrait {

  use ElementWrapThemeTrait;

  /**
   * Build Media Image.
   *
   * @param array $image
   *   The image render array.
   * @param string|null $credit
   *   Optional; The credit.
   * @param string|null $caption
   *   Optional; The caption.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementImage(array $image, ?string $credit = NULL, ?string $caption = NULL): array {
    $elements = [];

    $image = $this->wrapRoundedCornersBig($image);
    $elements[] = $this->wrapImageWithFigureTag($image);

    // Photo credit and caption.
    $elements[] = $this->buildCreditAndCaption($credit, $caption);

    return $this->wrapContainerVerticalSpacing($elements);
  }

  /**
   * Build Media Image with credit overlay.
   *
   * @param array $image
   *   The image render array.
   * @param string|null $credit
   *   Optional; The credit.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementImageWithCreditOverlay(array $image, ?string $credit = NULL): array {

    return [
      '#theme' => 'server_theme_image_with_credit_overlay',
      '#image' => $this->wrapRoundedCornersBig($image),
      '#credit' => $credit ? '© ' . $credit : NULL,
    ];
  }

  /**
   * Build Media Video.
   *
   * @param string $url
   *   Video url.
   * @param int $width
   *   Iframe width.
   * @param int $height
   *   Iframe height.
   * @param bool $iframe_full_width
   *   Defines if iframe has 100% width/height.
   * @param string|null $credit
   *   Optional; The credit.
   * @param string|null $caption
   *   Optional; The caption.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementVideo(string $url, int $width, int $height, bool $iframe_full_width = FALSE, ?string $credit = NULL, ?string $caption = NULL): array {
    $elements = [];

    $url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => $url,
        'max_width' => $width,
        'max_height' => $height,
        'hash' => $this->iFrameUrlHelper->getHash($url, $width, $height),
      ],
    ]);

    $elements[] = [
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

    // Photo credit and caption.
    $elements[] = $this->buildCreditAndCaption($credit, $caption);

    return $this->wrapContainerVerticalSpacing($elements);
  }

  /**
   * Build the optional credit and caption.
   *
   * @param string|null $credit
   *   Optional; The credit.
   * @param string|null $caption
   *   Optional; The caption.
   *
   * @return array
   *   The render array.
   */
  protected function buildCreditAndCaption(?string $credit = NULL, ?string $caption = NULL): array {
    $elements = [];

    if (!empty($credit)) {
      $element = $this->wrapTextResponsiveFontSize('© ' . $credit, FontSizeEnum::Sm);
      $element = $this->wrapTextItalic($element);
      $elements[] = $element;
    }
    if (!empty($caption)) {
      $element = $this->wrapTextResponsiveFontSize($caption);
      $elements[] = $element;
    }

    return $this->wrapContainerVerticalSpacingTiny($elements);
  }

}
