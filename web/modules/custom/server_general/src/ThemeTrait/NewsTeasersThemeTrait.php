<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Url;
use Drupal\intl_date\IntlDate;

/**
 * Helper methods for rendering News Teaser elements.
 */
trait NewsTeasersThemeTrait {

  use ButtonThemeTrait;
  use ElementLayoutThemeTrait;
  use InnerElementLayoutThemeTrait;
  use LinkThemeTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * Build News teasers element.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param array $items
   *   The news teasers array rendered with
   *   `ElementLayoutThemeTrait::buildElementLayoutTitleBodyAndItems`.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementNewsTeasers(string $title, array $body, array $items): array {
    return $this->buildElementLayoutTitleBodyAndItems(
      $title,
      $body,
      $items,
    );
  }

  /**
   * Build "News Teaser" element.
   *
   * @param array $image
   *   The image render array.
   * @param string $title
   *   The title.
   * @param \Drupal\Core\Url $url
   *   The URL to link to.
   * @param array $summary
   *   Summary of the search result.
   * @param int $timestamp
   *   The timestamp.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementNewsTeaser(array $image, string $title, Url $url, array $summary, int $timestamp): array {
    $elements = [];

    // Labels.
    $element = $this->buildLabelsFromText([$this->t('News')]);
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    // Date.
    $element = IntlDate::formatPattern($timestamp, 'short');
    $element = $this->wrapTextColor($element, 'gray');
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    // Title as link.
    $element = $this->buildLink($title, $url, 'dark-gray');
    $element = $this->wrapTextResponsiveFontSize($element, 'lg');
    $elements[] = $this->wrapTextFontWeight($element, 'bold');

    // Body teaser.
    $elements[] = $this->wrapTextLineClamp($summary, 4);

    return $this->buildInnerElementLayoutWithImage($url, $image, $elements);
  }

  /**
   * Build "Featured News Teaser" element with image on the side.
   *
   * @param array $image
   *   The image render array.
   * @param string $title
   *   The title.
   * @param \Drupal\Core\Url $url
   *   The URL to link to.
   * @param array $summary
   *   Summary of the search result.
   * @param int $timestamp
   *   The timestamp.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementNewsTeaserFeatured(array $image, string $title, Url $url, array $summary, int $timestamp): array {
    $elements = [];

    // Labels.
    $element = $this->buildLabelsFromText([$this->t('News')]);
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    // Date.
    $element = ['#markup' => IntlDate::formatPattern($timestamp, 'short')];
    $element = $this->wrapTextColor($element, 'gray');
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    // Title as link.
    $element = $this->buildLink($title, $url, 'dark-gray');
    $element = $this->wrapTextResponsiveFontSize($element, 'lg');
    $elements[] = $this->wrapTextFontWeight($element, 'bold');

    // Body teaser.
    $elements[] = $this->wrapTextLineClamp($summary, 4);

    // Read more button.
    $elements[] = $this->buildButton($this->t('Explore further'), $url);

    return $this->buildInnerElementLayoutWithImageHorizontal($url, $image, $elements);
  }

}
