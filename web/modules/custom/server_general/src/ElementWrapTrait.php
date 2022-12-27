<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
   * Wrap an element with `lg` rounded corners.
   *
   * @param array $element
   *   The render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapRoundedCornersBig(array $element): array {
    $element = $this->filterEmptyElements($element);
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
   * Wrap an element, with Prose text.
   *
   * @return array
   *   Render array.
   */
  protected function wrapProseText(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_prose_text',
      '#text' => $element,
    ];
  }

  /**
   * Wrap a text element with font weight.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param string $weight
   *   Font weight of the text. Allowed values are `normal`, `medium`, and
   *   `bold`. Defaults to `normal`.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextFontWeight(array|string|TranslatableMarkup $element, string $weight = 'normal'): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__font_weight',
      '#weight' => $weight,
      '#element' => $element,
    ];
  }

  /**
   * Wrap a text element with font weight.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param string $size
   *   Font size of the text. Allowed values are `xs`, `sm`, `base` and `lg`,
   *   and they refer to the size on desktop. While Tailwind works as mobile
   *   first, when we implement the design that in reality we start from the
   *   desktop, and work our way down to the mobile. Furthermore, on mobile the
   *   font size  may remain bigger, and won't become smaller - to keep things
   *   readable. Defaults to `base`.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextResponsiveFontSize(array|string|TranslatableMarkup $element, string $size = 'base'): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__responsive_font_size',
      '#size' => $size,
      '#element' => $element,
    ];
  }

  /**
   * Wrap a text element with italic style.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextItalic(array|string|TranslatableMarkup $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__italic',
      '#element' => $element,
    ];
  }

  /**
   * Wrap a text element with underline.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextUnderline(array|string|TranslatableMarkup $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__underline',
      '#element' => $element,
    ];
  }

  /**
   * Remove nested empty arrays.
   *
   * If the element is an array of arrays, we'd like to remove empty ones.
   * However, if the element is a one dimension array, we'll skip it.
   *
   * @param array|string|TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   *
   * @return array|string
   *   The filtered render array or the original string.
   */
  protected function filterEmptyElements(array|string|TranslatableMarkup $element): array|string|TranslatableMarkup {
    if (!is_array($element)) {
      // Nothing to do here.
      return $element;
    }
    if (count(Element::properties($element))) {
      // Element has top level properties beginning with #.
      // Do not filter.
      return $element;
    }

    return array_filter($element);
  }

}
