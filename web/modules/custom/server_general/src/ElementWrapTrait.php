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
   * @param string $align
   *   Determine if flex should also have an alignment. Possible values are
   *   `start`, `center`, `end` or NULL to have no change.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacing(array $element, string $align = NULL): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing',
      '#items' => $element,
      '#align' => $align,
    ];
  }

  /**
   * Wrap an element with a tiny vertical spacing (8px).
   *
   * @param array $element
   *   Render array.
   * @param string $align
   *   Determine if flex should also have an alignment. Possible values are
   *   `start`, `center`, `end` or NULL to have no change.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacingTiny(array $element, string $align = NULL): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing_tiny',
      '#items' => $element,
      '#align' => $align,
    ];
  }

  /**
   * Wrap an element with a big vertical spacing.
   *
   * @param array $element
   *   Render array.
   * @param string $align
   *   Determine if flex should also have an alignment. Possible values are
   *   `start`, `center`, `end` or NULL to have no change.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacingBig(array $element, string $align = NULL): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing_big',
      '#items' => $element,
      '#align' => $align,
    ];
  }

  /**
   * Wrap an element with a huge vertical spacing.
   *
   * @param array $element
   *   Render array.
   * @param string $align
   *   Determine if flex should also have an alignment. Possible values are
   *   `start`, `center`, `end` or NULL to have no change.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacingHuge(array $element, string $align = NULL): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing_huge',
      '#items' => $element,
      '#align' => $align,
    ];
  }

  /**
   * Wrap an element with bottom padding.
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
   * Wrap an element with `full` rounded corners.
   *
   * This can be used for example to make a profile picture circular.
   *
   * @param array $element
   *   The render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapRoundedCornersFull(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_rounded_corners_full',
      '#items' => $element,
    ];
  }

  /**
   * Wrap an element with a background color.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param string $color
   *   The background color. Possible values are:
   *   - `light-gray`.
   *
   * @return array
   *   Render array.
   */
  protected function wrapBackgroundColor(array|string|TranslatableMarkup $element, string $color): array {
    if (is_array($element)) {
      $element = $this->filterEmptyElements($element);
    }
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_background_color',
      '#color' => $color,
      '#items' => $element,
    ];
  }

  /**
   * Wrap an element with Prose text.
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
   * Wrap an element with a tag.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param string $tag
   *   The number of the heading. For example `h1` would result with a
   *   `<h1></h1>` tag.
   *
   * @return array
   *   Render array.
   */
  protected function wrapHtmlTag(array|string|TranslatableMarkup $element, string $tag): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_wrap_html_tag',
      '#tag' => $tag,
      '#element' => $element,
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
      '#font_weight' => $weight,
      '#element' => $element,
    ];
  }

  /**
   * Wrap a text element with font weight.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param string $size
   *   Font size of the text. Allowed values are `xs`, `sm`, `base`, `lg`, `xl`
   *   and `3xl`. Those sizes refer to the size on desktop. While Tailwind works
   *   as  mobile first, when we implement the design that in reality we start
   *   from the desktop, and work our way down to the mobile. Furthermore, on
   *   mobile the font size  may remain bigger, and won't become smaller - to
   *   keep things readable.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextResponsiveFontSize(array|string|TranslatableMarkup $element, string $size): array {
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
   * Wrap a text element with line clamp.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param int $lines
   *   The lines to clamp. Values are 1 to 4.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextLineClamp(array|string|TranslatableMarkup $element, int $lines): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__line_clamp',
      '#lines' => $lines,
      '#element' => $element,
    ];
  }

  /**
   * Wrap a text with center alignment.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextCenter(array|string|TranslatableMarkup $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__center',
      '#element' => $element,
    ];
  }

  /**
   * Wrap an element with text color.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param string $color
   *   The font color. Possible values are: `light-gray`, `gray` and
   *   `dark-gray`.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextColor(array|string|TranslatableMarkup $element, string $color): array {
    if (is_array($element)) {
      $element = $this->filterEmptyElements($element);
    }
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__font_color',
      '#color' => $color,
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

    // Filter out the empty keys in the element.
    $element_with_keys = array_filter(
      $element, fn ($key) => isset($key[0]), ARRAY_FILTER_USE_KEY
    );

    if (count(Element::properties($element_with_keys))) {
      // Element has top level properties beginning with #.
      // Do not filter.
      return $element;
    }

    return array_filter($element);
  }

}
