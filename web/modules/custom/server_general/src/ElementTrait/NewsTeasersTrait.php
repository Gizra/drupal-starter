<?php

declare(strict_types=1);

namespace Drupal\server_general\ElementTrait;

use Drupal\Core\Url;
use Drupal\intl_date\IntlDate;
use Drupal\server_general\ButtonTrait;
use Drupal\server_general\ElementLayoutTrait;
use Drupal\server_general\InnerElementLayoutTrait;
use Drupal\server_general\LinkTrait;
use Drupal\server_general\TitleAndLabelsTrait;

/**
 * Helper methods for rendering News Teaser elements.
 */
trait NewsTeasersTrait {

  use ButtonTrait;
  use ElementLayoutTrait;
  use InnerElementLayoutTrait;
  use LinkTrait;
  use TitleAndLabelsTrait;

  /**
   * Build News teasers element.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param array $items
   *   The news teasers array rendered with
   *   `ElementLayoutTrait::buildElementLayoutTitleBodyAndItems`.
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
    // Labels.
    $labels = $this->buildLabelsFromText([$this->t('News')]);
    $labels = $this->wrapTextResponsiveFontSize($labels, 'sm');

    // Date.
    $date = IntlDate::formatPattern($timestamp, 'short');
    $date = $this->wrapTextColor($date, 'gray');
    $date = $this->wrapTextResponsiveFontSize($date, 'sm');

    // Title as link.
    $title = $this->wrapTextResponsiveFontSize($title, 'lg');
    $title = $this->wrapTextFontWeight($title, 'bold');
    $title = $this->wrapTextLineClamp($title, 3);

    // Body teaser.
    $description = $this->wrapTextLineClamp($summary, 4);

    return [
      '#theme' => 'server_theme_element__news_card',
      '#image' => $image,
      '#labels' => $labels,
      '#date' => $date,
      '#title' => $title,
      '#description' => $description,
      '#url' => $url,
    ];
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
