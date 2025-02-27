<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Helper methods for rendering People/Person Teaser elements.
 */
trait PeopleTeasersThemeTrait {

  use ElementLayoutThemeTrait;
  use ElementWrapThemeTrait;
  use InnerElementLayoutThemeTrait;
  use CardThemeTrait;

  /**
   * Build People cards element.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param array $items
   *   The render array built with
   *   `ElementLayoutThemeTrait::buildElementLayoutTitleBodyAndItems`.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementPeopleTeasers(string $title, array $body, array $items): array {
    return $this->buildElementLayoutTitleBodyAndItems(
      $title,
      $body,
      $this->buildCards($items),
    );
  }

  /**
   * Build a Person teaser.
   *
   * @param string $image_url
   *   The image Url.
   * @param string $alt
   *   The image alt.
   * @param string $name
   *   The name.
   * @param string|null $subtitle
   *   Optional; The subtitle (e.g. work title).
   *
   * @return array
   *   The render array.
   */
  protected function buildElementPersonTeaser(string $image_url, string $alt, string $name, ?string $subtitle = NULL): array {
    $elements = [];
    $element = [
      '#theme' => 'image',
      '#uri' => $image_url,
      '#alt' => $alt,
      '#width' => 100,
    ];

    $elements[] = $this->wrapRoundedCornersFull($element);

    $inner_elements = [];

    $element = $this->wrapTextFontWeight($name, 'bold');
    $inner_elements[] = $this->wrapTextCenter($element);

    if ($subtitle) {
      $element = $this->wrapTextResponsiveFontSize($subtitle, 'sm');
      $element = $this->wrapTextCenter($element);
      $inner_elements[] = $this->wrapTextColor($element, 'gray');
    }

    $elements[] = $this->wrapContainerVerticalSpacingTiny($inner_elements, 'center');

    return $this->buildInnerElementLayoutCentered($elements);
  }

}
