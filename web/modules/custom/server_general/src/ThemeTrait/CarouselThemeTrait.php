<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum;

/**
 * Helper methods for rendering Carousel elements.
 */
trait CarouselThemeTrait {

  use ElementLayoutThemeTrait;
  use ElementWrapThemeTrait;

  /**
   * Build a Carousel.
   *
   * @param string $title
   *   Optional; The title.
   * @param array $body
   *   The body render array.
   * @param array $items
   *   The items to render inside the carousel.
   * @param bool $is_featured
   *   Determine if items inside the carousel are "featured". Usually a featured
   *   item means that only a single card should appear at a time.
   * @param array|null $button
   *   Optional; The render array of the button, likely created with
   *   ButtonThemeTrait::buildButton.
   * @param bool $is_infinite
   *   Optional; Indicate whether the carousel should be infinite or not.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementCarousel(string $title, array $body, array $items, bool $is_featured = FALSE, ?array $button = NULL, bool $is_infinite = FALSE): array {
    if (empty($items)) {
      return [];
    }

    $current_lang_code = \Drupal::languageManager()->getCurrentLanguage();

    $elements = [];
    $elements[] = [
      '#theme' => 'server_theme_carousel',
      '#items' => $items,
      '#is_featured' => $is_featured,
      '#is_infinite' => $is_infinite,
      '#direction' => $current_lang_code->getDirection(),
    ];

    if ($button) {
      $elements[] = $this->wrapTextCenter($button);
    }

    $elements = $this->wrapContainerVerticalSpacing($elements);

    return $this->buildElementLayoutTitleBodyAndItems(
      $title,
      $body,
      $elements,
      BackgroundColorEnum::LightGray,
    );
  }

}
