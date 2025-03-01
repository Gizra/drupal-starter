<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Link;

/**
 * Helper methods for rendering Hero elements.
 */
trait HeroThemeTrait {

  use ButtonThemeTrait;
  use ElementWrapThemeTrait;

  /**
   * Build a Hero image.
   *
   * @param array $image
   *   The render array of the image.
   * @param string $title
   *   The title.
   * @param string $subtitle
   *   The subtitle.
   * @param \Drupal\Core\Link|null $link
   *   The button Link object.
   *   If NULL, no button is rendered. Defaults to NULL.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementHeroImage(array $image, string $title, string $subtitle, ?Link $link = NULL): array {
    $elements = [];

    // Title.
    $element = $this->wrapHtmlTag($title, 'h1');
    $elements[] = $this->wrapTextFontWeight($element, 'bold');

    // Subtitle.
    $element = $this->wrapTextResponsiveFontSize($subtitle, 'xl');
    $elements[] = $this->wrapTextFontWeight($element, 'medium');

    // Button.
    if ($link) {
      $elements[] = $this->buildButton($link->getText(), $link->getUrl(), 'primary', NULL, $link->getUrl()->isExternal());
    }

    $elements = $this->wrapContainerVerticalSpacingBig($elements);

    return [
      '#theme' => 'server_theme_element__hero_image',
      '#image' => $image,
      '#items' => $elements,
    ];
  }

}
