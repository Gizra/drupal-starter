<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Link;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\Enum\FontWeightEnum;
use Drupal\server_general\ThemeTrait\Enum\HtmlTagEnum;

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
    $element = $this->wrapHtmlTag($title, HtmlTagEnum::H1);
    $elements[] = $this->wrapTextFontWeight($element, FontWeightEnum::Bold);

    // Subtitle.
    $element = $this->wrapTextResponsiveFontSize($subtitle, FontSizeEnum::Xl);
    $elements[] = $this->wrapTextFontWeight($element, FontWeightEnum::Medium);

    // Button.
    if ($link) {
      $elements[] = $this->buildButtonPrimary($link);
    }

    $elements = $this->wrapContainerVerticalSpacingBig($elements);

    return [
      '#theme' => 'server_theme_element__hero_image',
      '#image' => $image,
      '#items' => $elements,
    ];
  }

}
