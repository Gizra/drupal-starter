<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\Core\Render\Element;

/**
 * Helper method for wrapping an element.
 */
trait ElementWrapTrait {

  /**
   * Wrap an element with a wide container.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerWide(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_wide',
      '#element' => $element,
    ];
  }

  /**
   * Wrap an element with a narrow container.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerNarrow(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_narrow',
      '#element' => $element,
    ];
  }

  /**
   * Wrap an element with a regular vertical spacing.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacing(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing',
      '#items' => $element,
    ];
  }

  /**
   * Wrap an element with a tiny vertical spacing (8px).
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacingTiny(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing_tiny',
      '#items' => $element,
    ];
  }

  /**
   * Wrap an element with a big vertical spacing.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacingBig(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing_big',
      '#items' => $element,
    ];
  }

  /**
   * Wrap an element with a huge vertical spacing.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacingHuge(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing_huge',
      '#items' => $element,
    ];
  }

  /**
   * Wrap an element with a bottom padding.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerBottomPadding(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_bottom_padding',
      '#items' => $element,
    ];
  }

  /**
   * Wrap an element, with Prose text.
   *
   * @return array
   *   Render array.
   */
  protected function wrapProseText(array $element): array {

    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_prose_text',
      '#text' => $element,
    ];
  }

  /**
   * Wrap an element with text decorations.
   *
   * @param array|string $element
   *   The render array, string or a TranslatableMarkup object.
   * @param string $font_weight
   *   Font weight of the text. Can be 'normal', 'medium', 'semibold', 'bold'.
   * @param bool $is_underline
   *   TRUE to make it text underlined.
   * @param bool $is_italic
   *   TRUE to make the text italic.
   * @param string|null $mobile_font_size
   *   The font size for mobile. Can be 'xs', 'sm' or 'lg'. On
   *   Twig, we'll take care of making the font size responsive.
   *   Defaults to NULL, which will not change the font size.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextDecorations(array|string $element, string $font_weight = NULL, bool $is_underline = FALSE, bool $is_italic = FALSE, string $mobile_font_size = NULL): array {
    if (is_array($element)) {
      $element = $this->filterEmptyElements($element);
    }
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decorations',
      '#element' => $element,
      '#font_weight' => $font_weight,
      '#is_underline' => $is_underline,
      '#is_italic' => $is_italic,
      '#font_size' => $mobile_font_size,
    ];
  }

  /**
   * Wrap an element with `lg` rounded corners.
   *
   * @param array $element
   *   The render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapRoundedCornersBig(array $element): array {
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_rounded_corners_big',
      '#items' => $element,
    ];
  }

  /**
   * Remove nested empty arrays.
   *
   * If the element is an array of arrays, we'd like to remove empty ones.
   * However, if the element is a one dimension array, we'll skip it.
   *
   * @param array $element
   *   The render array.
   *
   * @return array
   *   The filtered render array.
   */
  protected function filterEmptyElements(array $element): array {
    if (count(Element::properties($element))) {
      // Element has top level properties beginning with #.
      // Do not filter.
      return $element;
    }

    return array_filter($element);
  }

}
