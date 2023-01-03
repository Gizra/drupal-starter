<?php

declare(strict_types=1);


namespace Drupal\server_general;

use Drupal\Core\Url;
use Drupal\intl_date\IntlDate;

/**
 * Helper methods for rendering different Card types.
 *
 * A card is used as a teaser to a piece of content. It can be an Event card,
 * a Featured content card, etc.
 *
 * Trait is providing helper methods for each card. One method equals one theme
 * file.
 */
trait CardTrait {

  use ButtonTrait;
  use ElementWrapTrait;
  use LinkTrait;
  use TitleAndLabelsTrait;

  /**
   * Build "Card" - the simplest one.
   *
   * @param array $items
   *   The elements as render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildCard(array $items): array {
    return [
      '#theme' => 'server_theme_card',
      '#items' => $this->wrapContainerVerticalSpacing($items),
    ];
  }

  /**
   * Build "Centered card".
   *
   * @param array $items
   *   The elements as render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildCardCentered(array $items): array {
    return [
      '#theme' => 'server_theme_card__centered',
      '#items' => $this->wrapContainerVerticalSpacing($items, 'center'),
    ];
  }

  /**
   * Build "Card with image".
   *
   * This is the "base" helper method for rendering a card with image. Specific
   * cards may implement own helper methods, that will use this one.
   *
   * @param \Drupal\Core\Url $url
   *   The URL to link to.
   * @param array $image
   *   The image render array.
   * @param array $items
   *   The rest of the items' render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildCardWithImage(Url $url, array $image, array $items): array {
    return [
      '#theme' => 'server_theme_card__with_image',
      '#image' => $image,
      '#url' => $url,
      '#items' => $this->wrapContainerVerticalSpacing($items),
    ];
  }

  /**
   * Build "Card with image horizontal" base.
   *
   * @param \Drupal\Core\Url $url
   *   The URL to link to.
   * @param array $image
   *   The image render array.
   * @param array $items
   *   The rest of the items' render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildCardWithImageHorizontal(Url $url, array $image, array $items): array {
    return [
      '#theme' => 'server_theme_card__with_image_horizontal',
      '#image' => $image,
      '#url' => $url,
      '#items' => $this->wrapContainerVerticalSpacing($items),
    ];
  }

  /**
   * Build "Card with image" for News content type.
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
  protected function buildCardWithImageForNews(array $image, string $title, Url $url, array $summary, int $timestamp): array {
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

    return $this->buildCardWithImage($url, $image, $elements);
  }

  /**
   * Build "Card with image horizontal" for News content type.
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
  protected function buildCardWithImageHorizontalForNews(array $image, string $title, Url $url, array $summary, int $timestamp): array {
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
    $elements[] = $this->buildButton($this->t('Read more'), $url);

    return $this->buildCardWithImageHorizontal($url, $image, $elements);
  }

  /**
   * Build "Search result".
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
  protected function buildCardSearchResult(string $label, string $title, Url $url, array $summary, int $timestamp): array {
    $elements = [];
    // Labels.
    $elements[] = $this->buildLabelsFromText([$label]);

    // Title as link, wrapped in h3 tag.
    $element = $this->buildLink($title, $url, 'dark-gray');
    $element = $this->wrapTextResponsiveFontSize($element, '3xl');
    $element = $this->wrapTextFontWeight($element, 'bold');
    $elements[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => render($element),
    ];

    // Summary.
    $elements[] = $this->wrapTextLineClamp($summary, 4);

    // Date.
    $element = IntlDate::formatPattern($timestamp, 'short');
    $element = $this->wrapTextColor($element, 'light-gray');
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    return $this->buildCard($elements);
  }

  /**
   * Wrap multiple cards with a grid.
   *
   * @param array $items
   *   The elements as render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildCards(array $items): array {
    return [
      '#theme' => 'server_theme_cards',
      '#items' => $items,
    ];
  }

}
