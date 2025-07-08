<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum;
use Drupal\server_general\ThemeTrait\Enum\TextColorEnum;
use Drupal\server_general\ThemeTrait\Enum\WidthEnum;

/**
 * Helper methods to build Page layouts.
 *
 * A regular single column page layout doesn't need any method here, as one can
 * use inside PEVB something like`$this->wrapContainerWide($elements)`.
 * So it's likely this trait will hold only the Main and sidebar helper method,
 * unless there's a need for a more complex layout.
 *
 * ThemeTrait provides helper methods for each layout. One method equals one
 * theme file.
 */
trait ElementLayoutThemeTrait {

  use ButtonThemeTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * Build Main and sidebar layout.
   *
   * @param array $main
   *   The main render array.
   * @param array $sidebar
   *   The sidebar render array.
   * @param bool $is_sidebar_first
   *   Determine if sidebar should appear first on mobile/tablet layout.
   *   Defaults to FALSE.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementLayoutMainAndSidebar(array $main, array $sidebar = [], bool $is_sidebar_first = FALSE) {
    return [
      '#theme' => 'server_theme_element_layout__main_and_sidebar',
      '#main' => $main,
      '#sidebar' => $sidebar,
      '#is_sidebar_first' => $is_sidebar_first,
    ];
  }

  /**
   * Build a Paragraph title and content element layout.
   *
   * @param string $title
   *   The title.
   * @param array $content
   *   The content render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementLayoutTitleAndContent(string $title, array $content): array {
    $elements[] = $this->buildParagraphTitle($title);
    $elements[] = $content;

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    return $this->wrapContainerWide($elements);
  }

  /**
   * Helper; Build the paragraph title and description and the items.
   *
   * @param string $title
   *   The title. Maybe empty.
   * @param array $body
   *   The body render array. Maybe empty.
   * @param array $items
   *   The items render array.
   * @param \Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum $bg_color
   *   The background color. See
   *   ElementWrapThemeTrait::wrapContainerWide for the allowed values.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementLayoutTitleBodyAndItems(string $title, array $body, array $items, BackgroundColorEnum $bg_color = BackgroundColorEnum::Transparent): array {
    $top_elements = [];
    $elements = [];
    $top_elements[] = $this->buildParagraphTitle($title);

    $body = $this->wrapProseText($body);
    $body = $this->wrapTextColor($body, TextColorEnum::DarkGray);
    $top_elements[] = $body;

    $top_elements = $this->wrapContainerVerticalSpacingTiny($top_elements);
    $top_elements = $this->wrapContainerMaxWidth($top_elements, WidthEnum::ThreeXl);

    $elements[] = $top_elements;
    $elements[] = $items;

    $elements = $this->wrapContainerVerticalSpacing($elements);
    return $this->wrapContainerWide($elements, $bg_color);
  }

  /**
   * Render items with a View more button, that will reveal more items.
   *
   * @param array $items
   *   The items to render.
   * @param int $limit_count
   *   Determine how many items to show initially.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementLayoutItemsWithViewMore(array $items, int $limit_count): array {
    if (count($items) <= $limit_count) {
      // We don't need to hide any item.
      return $items;
    }

    $wrapped_items = [];
    foreach (array_values($items) as $key => $item) {
      if ($key + 1 > $limit_count) {
        // Hide the items that are over the limit count.
        $item = $this->wrapHidden($item);
      }
      $wrapped_items[] = $item;
    }

    $elements = [];
    $elements[] = $wrapped_items;

    $link = Link::fromTextAndUrl($this->t('View more'), Url::fromUserInput('#'));
    $elements[] = $this->buildButtonSecondary($link);
    $elements = $this->wrapContainerVerticalSpacing($elements);

    return [
      '#theme' => 'server_theme_element_items_with_view_more',
      '#items' => $elements,
    ];
  }

}
