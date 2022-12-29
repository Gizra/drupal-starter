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
  use ElementWrapTrait;
  use LinkTrait;
  use TitleAndLabelsTrait;

  /**
   * Build a CTA.
   *
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
  public function buildElementCta(string $title, string $subtitle, string $button_text, Url $url): array {
    $elements = [];

    // Title.
    $element = ['#markup' => $title];
    $element = $this->wrapTextResponsiveFontSize($element, '3xl');
    $elements[] = $this->wrapTextFontWeight($element, 'bold');

    // Subtitle.
    if (!empty($subtitle)) {
      $element = ['#markup' => $subtitle];
      $element = $this->wrapTextResponsiveFontSize($element, 'xl');
      $elements[] = $this->wrapTextFontWeight($element, 'medium');
    }

    // Button.
    $elements[] = $this->buildButton($button_text, $url);

    $elements = $this->wrapContainerVerticalSpacingBig($elements, 'center');
    $elements = $this->wrapTextCenter($elements);
    $elements = $this->wrapContainerNarrow($elements);
    $elements = $this->wrapContainerVerticalPadding($elements);

    return $this->wrapBackgroundColor($elements, 'light-gray');

  }

}
