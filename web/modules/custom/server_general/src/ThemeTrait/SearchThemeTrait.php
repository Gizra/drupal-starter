<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Url;
use Drupal\intl_date\IntlDate;
use Drupal\server_general\ThemeTrait\Enum\ColorEnum;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\Enum\FontWeightEnum;
use Drupal\server_general\ThemeTrait\Enum\LineClampEnum;
use Drupal\server_general\ThemeTrait\Enum\TextColorEnum;

/**
 * Helper methods for rendering Search/Search results related elements.
 *
 * @property \Drupal\Core\Render\RendererInterface $renderer
 */
trait SearchThemeTrait {

  use ElementWrapThemeTrait;
  use InnerElementLayoutThemeTrait;
  use LinkThemeTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * Build a Search term and results element.
   *
   * This is used by the Search paragraph type. The facets are part of the
   * results, as exposed filters of the search view.
   *
   * @param array $result_items
   *   The render array of the results.
   * @param string|null $search_term
   *   The search term if exists. Defaults to NULL.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementSearchTermAndResults(array $result_items, ?string $search_term = NULL): array {
    $elements = [];

    if ($search_term) {
      $elements[] = $this->buildElementSearchTermSummary($search_term);
    }

    $elements[] = $result_items;

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    return $this->wrapContainerWide($elements);
  }

  /**
   * Build the search summary element showing the searched term.
   *
   * @param string $search_term
   *   The searched term.
   *
   * @return array
   *   The render array for the element.
   */
  protected function buildElementSearchTermSummary(string $search_term): array {
    return [
      '#theme' => 'server_theme_search_term',
      '#search_term' => $search_term,
    ];
  }

  /**
   * Build a single "Search result" item.
   *
   * @param string $label
   *   The label of the content type.
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
  protected function buildElementSearchResult(string $label, string $title, Url $url, array $summary, int $timestamp): array {
    $elements = [];
    // Labels.
    $elements[] = $this->buildLabelsFromText([$label]);

    // Title as link, wrapped in h3 tag.
    $element = $this->buildLink($title, $url, ColorEnum::DarkGray);
    $element = $this->wrapTextResponsiveFontSize($element, FontSizeEnum::ThreeXl);
    $element = $this->wrapTextFontWeight($element, FontWeightEnum::Bold);
    $elements[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->renderer->render($element),
    ];

    // Summary.
    $elements[] = $this->wrapTextLineClamp($summary, LineClampEnum::Four);

    // Date.
    $element = IntlDate::formatPattern($timestamp, 'short');
    $element = $this->wrapTextColor($element, TextColorEnum::LightGray);
    $elements[] = $this->wrapTextResponsiveFontSize($element, FontSizeEnum::Sm);

    return $this->buildInnerElementLayout($elements);
  }

}
