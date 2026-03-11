<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\Enum\TextColorEnum;

/**
 * Helper methods for rendering Quote elements.
 */
trait QuoteThemeTrait {

  use ElementWrapThemeTrait;

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
  protected function buildElementQuote(array $image, array $quote, ?string $subtitle = NULL, ?string $image_credit = NULL): array {
    $items = [];

    // Quotation sign.
    $items[] = ['#theme' => 'server_theme_quotation_sign'];

    // Quote.
    $element = $this->wrapTextResponsiveFontSize($quote, FontSizeEnum::TwoXl);
    $items[] = $this->wrapTextColor($element, TextColorEnum::Gray);

    // Quote by.
    $element = $this->wrapTextResponsiveFontSize($subtitle, FontSizeEnum::Sm);
    $items[] = $this->wrapTextItalic($element);

    // The photo credit on top of the image.
    $credit = [];
    if (!empty($image_credit)) {
      $credit[] = ['#markup' => '© ' . $image_credit];
    }

    return [
      '#theme' => 'server_theme_element_layout__split_image_and_content',
      '#items' => $this->wrapContainerVerticalSpacing($items),
      '#image' => $image,
      '#credit' => $credit,
    ];
  }

}
