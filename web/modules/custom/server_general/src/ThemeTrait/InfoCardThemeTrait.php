<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\server_general\ThemeTrait\Enum\AlignmentEnum;
use Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\Enum\FontWeightEnum;
use Drupal\server_general\ThemeTrait\Enum\TextColorEnum;

/**
 * Helper methods for rendering Info Card elements.
 */
trait InfoCardThemeTrait {

  use ElementLayoutThemeTrait;
  use ElementWrapThemeTrait;
  use CardThemeTrait;
  use InnerElementLayoutThemeTrait;

  /**
   * Build an Info cards element.
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
  protected function buildElementInfoCards(string $title, array $body, array $items): array {
    return $this->buildElementLayoutTitleBodyAndItems(
      $title,
      $body,
      $this->buildCards($items),
    );
  }

  /**
   * Build a single Info card element.
   *
   * @param string $header
   *   The header. Usually used for the number (e.g. "100%").
   * @param string $title
   *   The title.
   * @param string|null $subtitle
   *   Optional; The subtitle.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementInfoCard(string $header, string $title, ?string $subtitle = NULL): array {
    $elements = [];

    $element = $this->wrapTextFontWeight($header, FontWeightEnum::Bold);
    $element = $this->wrapTextResponsiveFontSize($element, FontSizeEnum::ThreeXl);
    $element = $this->wrapTextCenter($element);
    $elements[] = $element;

    $bottom_elements = [];
    $element = $this->wrapTextResponsiveFontSize($title, FontSizeEnum::TwoXl);
    $element = $this->wrapTextCenter($element);
    $bottom_elements[] = $element;

    if ($subtitle) {
      $element = $this->wrapTextResponsiveFontSize($subtitle, FontSizeEnum::LG);
      $element = $this->wrapTextCenter($element);
      $bottom_elements[] = $this->wrapTextColor($element, TextColorEnum::Gray);
    }

    $bottom_elements = $this->wrapContainerVerticalSpacingTiny($bottom_elements, AlignmentEnum::Center);
    $elements[] = $bottom_elements;

    $elements = $this->wrapContainerVerticalSpacing($elements, AlignmentEnum::Center);

    return $this->buildInnerElementLayout($elements, BackgroundColorEnum::LightGray);
  }

}
