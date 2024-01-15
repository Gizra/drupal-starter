<?php

declare(strict_types=1);


namespace Drupal\server_general;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Helper methods for rendering different elements.
 *
 * In this trait an "element" signifies a section or a strip on the page. That
 * element can be for example a Related content carousel.
 */
trait ElementTrait {

  use ButtonTrait;
  use ElementLayoutTrait;
  use ElementWrapTrait;
  use InnerElementTrait;
  use LineSeparatorTrait;
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
   * Build a Search term, facets and results element.
   *
   * This is used by the Search paragraph type.
   *
   * @param array $facets_items
   *   The facets render array.
   * @param bool $has_filters
   *   Indicate if there are facet filters. That is, if a user has selected some
   *   values in one or more of the facets.
   * @param array $result_items
   *   The render array of the results.
   * @param string|null $search_term
   *   The search term if exists. Defaults to NULL.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementSearchTermFacetsAndResults(array $facets_items, bool $has_filters, array $result_items, string $search_term = NULL): array {
    $elements = [];

    // Show the search term and facets if they exist.
    $element = [];
    if ($search_term) {
      $element[] = [
        '#theme' => 'server_theme_search_term',
        '#search_term' => $search_term,
      ];
    }

    if ($facets_items) {
      $element[] = [
        '#theme' => 'server_theme_facets__search',
        '#items' => $facets_items,
        '#has_filters' => $has_filters,
      ];
    }

    $elements[] = $this->wrapContainerVerticalSpacing($element);

    // Add the results.
    $elements[] = $result_items;

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    return $this->wrapContainerWide($elements);
  }

}
