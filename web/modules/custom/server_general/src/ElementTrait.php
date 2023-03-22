<?php

declare(strict_types=1);


namespace Drupal\server_general;

use Drupal\Core\Url;

/**
 * Helper methods for rendering different elements.
 *
 * In this trait an "element" signifies a section or a strip on the page. That
 * element can be for example a Related content carousel or a CTA.
 * element that.
 */
trait ElementTrait {

  use ButtonTrait;
  use CardTrait;
  use ElementLayoutTrait;
  use ElementWrapTrait;
  use LineSeparatorTrait;
  use LinkTrait;
  use TitleAndLabelsTrait;

  /**
   * Build a CTA.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param string $button_text
   *   The button text.
   * @param \Drupal\Core\Url $url
   *   The URL to link the button to.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementCta(string $title, array $body, string $button_text, Url $url): array {
    $elements = [];

    // Title.
    $element = $title;
    $element = $this->wrapTextResponsiveFontSize($element, '3xl');
    $elements[] = $this->wrapTextFontWeight($element, 'bold');

    // Text.
    $elements[] = $this->wrapProseText($body);

    // Button.
    $elements[] = $this->buildButton($button_text, $url);

    $elements = $this->wrapContainerVerticalSpacingBig($elements, 'center');

    return [
      '#theme' => 'server_theme_element__cta',
      '#items' => $elements,
    ];
  }

  /**
   * Build a Hero image.
   *
   * @param array $image
   *   The render array of the image.
   * @param string $title
   *   The title.
   * @param string $subtitle
   *   The subtitle.
   * @param string $button_text
   *   The button text.
   * @param \Drupal\Core\Url $url
   *   The URL to link the button to.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementHeroImage(array $image, string $title, string $subtitle, string $button_text, Url $url): array {
    $elements = [];

    // Title.
    $element = ['#markup' => $title];
    $element = $this->wrapHtmlTag($element, 'h1');
    $elements[] = $this->wrapTextFontWeight($element, 'bold');

    // Subtitle.
    $element = ['#markup' => $subtitle];
    $element = $this->wrapTextResponsiveFontSize($element, 'xl');
    $elements[] = $this->wrapTextFontWeight($element, 'medium');

    // Button.
    $elements[] = $this->buildButton($button_text, $url);

    $elements = $this->wrapContainerVerticalSpacingBig($elements);

    return [
      '#theme' => 'server_theme_element__hero_image',
      '#image' => $image,
      '#items' => $elements,
    ];
  }

  /**
   * Builds a "Document" list.
   *
   * @param string $title
   *   The title.
   * @param array $subtitle
   *   The subtitle render array.
   * @param array $items
   *   Render array of documents.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementDocuments(string $title, array $subtitle, array $items): array {
    $elements = [];

    // Title.
    $elements[] = $this->buildParagraphTitle($title);

    // Subtitle.
    $elements[] = $this->wrapTextColor($subtitle, 'dark-gray');

    // Items and "View more" button.
    $elements[] = $this->buildElementItemsWithViewMore($items, 2);

    $elements = $this->wrapContainerVerticalSpacing($elements);

    return $this->wrapContainerWide($elements, 'light-gray');
  }

  /**
   * Build a Carousel.
   *
   * @param array $items
   *   The items to render inside the carousel.
   * @param bool $is_featured
   *   Determine if items inside the carousel are "featured". Usually a featured
   *   item means that only a single card should appear at a time.
   * @param string|null $title
   *   Optional; The title.
   * @param array|null $button
   *   Optional; The render array of the button, likely created with
   *   ButtonTrait::buildButton.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementCarousel(array $items, bool $is_featured = FALSE, string $title = NULL, array $button = NULL): array {
    if (empty($items)) {
      return [];
    }

    $header_items = [];
    $header_items[] = $this->buildParagraphTitle($title);

    return [
      '#theme' => 'server_theme_carousel',
      '#header_items' => $header_items,
      '#items' => $items,
      '#button' => $button,
      '#is_featured' => $is_featured,
    ];
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
  protected function buildElementItemsWithViewMore(array $items, int $limit_count): array {
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
    $elements[] = $this->buildButton($this->t('View more'), Url::fromUserInput('#'));
    ;
    $elements = $this->wrapContainerVerticalSpacing($elements);

    return [
      '#theme' => 'server_theme_element_items_with_view_more',
      '#items' => $elements,
    ];
  }

  /**
   * Build an Accordion.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param array $items
   *   Items rendered with `CardTrait::buildElementAccordionItem`.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementAccordion(string $title, array $body, array $items): array {
    $elements = [];

    // Title and description.
    $elements[] = $this->buildParagraphTitleAndDescription($title, $body);

    // Add line separators to items.
    $items_wrapped = [];
    foreach ($items as $key => $item) {
      if ($key == array_key_first($items)) {
        $items_wrapped[] = $this->buildLineSeparator();
      }
      $items_wrapped[] = $item;
      $items_wrapped[] = $this->buildLineSeparator();
    }

    // Accordion.
    $elements[] = [
      '#theme' => 'server_theme_element__accordion',
      '#items' => $items_wrapped,
    ];

    $element = $this->wrapContainerVerticalSpacingBig($elements);

    return $this->wrapContainerWide($element);
  }

  /**
   * Build People teasers element.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param array $items
   *   Items rendered with `CardTrait::buildElementAccordionItem`.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementPeopleTeasers(string $title, array $body, array $items): array {
    $elements = [];

    // Title and description.
    $elements[] = $this->buildParagraphTitleAndDescription($title, $body);
    $elements[] = $this->buildCards($items);

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    return $this->wrapContainerWide($elements);
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

  /**
   * Build a Quote.
   *
   * @param array $image
   *   The image render array.
   * @param array $quote
   *   The quote render array.
   * @param string|null $subtitle
   *   Optional; The subtitle could be for example the author name. Defaults to
   *   NULL.
   * @param string|null $image_credit
   *   Optional; The image credit. Defaults to NULL.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementQuote(array $image, array $quote, string $subtitle = NULL, string $image_credit = NULL): array {
    $items = [];

    // Quotation sign.
    $items[] = ['#theme' => 'server_theme_quotation_sign'];

    // Quote.
    $element = $this->wrapTextResponsiveFontSize($quote, '2xl');
    $items[] = $this->wrapTextColor($element, 'gray');

    // Quote by.
    $element = $this->wrapTextResponsiveFontSize($subtitle, 'sm');
    $items[] = $this->wrapTextItalic($element);

    // The photo credit on top of the image.
    $image_items = [];
    if (!empty($image_credit)) {
      $image_items[] = ['#markup' => '© ' . $image_credit];
    }

    return [
      '#theme' => 'server_theme_element_layout__split_image_and_content',
      '#items' => $this->wrapContainerVerticalSpacing($items),
      '#image' => $image,
      '#image_items' => $image_items,
    ];
  }

  /**
   * Build quick links as carousels.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The description render array.
   * @param array $items
   *   The quick links array rendered with `CardTrait::buildCardQuickLink`.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementQuickLinks(string $title, array $body, array $items): array {
    $elements = [];

    // Title and description.
    $elements[] = $this->buildParagraphTitleAndDescription($title, $body);

    $elements[] = $this->buildCards($items);
    $elements = $this->wrapContainerVerticalSpacing($elements);
    return $this->wrapContainerWide($elements);
  }

  /**
   * Build a Paragraph title and text element.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementParagraphTitleAndText(string $title, array $body): array {
    return $this->buildElementLayoutParagraphTitleAndContent(
      $title,
      $this->wrapProseText($body),
    );
  }

  /**
   * Build a Paragraph title and content element.
   *
   * This is a more generic instance of `buildElementParagraphTitleAndText`.
   *
   * @param string $title
   *   The title.
   * @param array $items
   *   The items render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementParagraphTitleAndContent(string $title, array $items): array {
    return $this->buildElementLayoutParagraphTitleAndContent(
      $title,
      $items,
    );
  }

}
