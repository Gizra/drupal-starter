<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Link;

/**
 * Helper methods for rendering Call to Action elements.
 */
trait CtaThemeTrait {

  use ButtonThemeTrait;
  use ElementWrapThemeTrait;
  use InnerElementLayoutThemeTrait;

  /**
   * Build a CTA.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param \Drupal\Core\Link $link
   *   The button Link object.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementCta(string $title, array $body, Link $link): array {
    $elements = [];

    // Title.
    $element = $title;
    $element = $this->wrapTextResponsiveFontSize($element, FontSizeEnum::ThreeXl);
    $element = $this->wrapTextCenter($element);
    $elements[] = $this->wrapTextFontWeight($element, FontWeightEnum::Bold);

    // Text.
    $elements[] = $this->wrapProseText($body);

    // Button.
    $elements[] = $this->buildButton($link->getText(), $link->getUrl(), 'primary', NULL, $link->getUrl()->isExternal());

    $elements = $this->wrapContainerVerticalSpacingBig($elements, AlignmentEnum::Center);

    $elements = $this->buildInnerElementLayout($elements, BackgroundColorEnum::LightGray);
    return $this->wrapContainerNarrow($elements);
  }

}
