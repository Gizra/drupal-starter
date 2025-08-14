<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\server_general\ThemeTrait\Enum\AlignmentEnum;
use Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\Enum\FontWeightEnum;
use Drupal\server_general\ThemeTrait\Enum\HtmlTagEnum;
use Drupal\server_general\ThemeTrait\Enum\LineClampEnum;
use Drupal\server_general\ThemeTrait\Enum\TextColorEnum;
use Drupal\server_general\ThemeTrait\Enum\WidthEnum;

/**
 * Helper method for wrapping an element.
 */
trait ElementWrapThemeTrait {

  /**
   * Wrap an element with a wide container, and optional background color.
   *
   * @param array $element
   *   The render array.
   * @param \Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum $bg_color
   *   The background color.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerWide(array $element, BackgroundColorEnum $bg_color = BackgroundColorEnum::Transparent): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_wide',
      '#element' => $element,
      '#bg_color' => $bg_color->value,
    ];
  }

  /**
   * Wrap an element with a narrow container, and optional background color.
   *
   * @param array $element
   *   The render array.
   * @param \Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum $bg_color
   *   Optional; The background color.
   *   If NULL, a transparent background will be added.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerNarrow(array $element, BackgroundColorEnum $bg_color = BackgroundColorEnum::Transparent): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_narrow',
      '#element' => $element,
      '#bg_color' => $bg_color->value,
    ];
  }

  /**
   * Wrap an element with a regular vertical spacing.
   *
   * @param array $element
   *   Render array.
   * @param \Drupal\server_general\ThemeTrait\Enum\AlignmentEnum $align
   *   Determine the alignment of flex.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacing(array $element, AlignmentEnum $align = AlignmentEnum::Default): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing',
      '#items' => $element,
      '#align' => $align->value,
    ];
  }

  /**
   * Wrap an element with a tiny vertical spacing (8px).
   *
   * @param array $element
   *   Render array.
   * @param \Drupal\server_general\ThemeTrait\Enum\AlignmentEnum $align
   *   Determine the alignment of flex.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacingTiny(array $element, AlignmentEnum $align = AlignmentEnum::Default): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing_tiny',
      '#items' => $element,
      '#align' => $align->value,
    ];
  }

  /**
   * Wrap an element with a big vertical spacing.
   *
   * @param array $element
   *   Render array.
   * @param \Drupal\server_general\ThemeTrait\Enum\AlignmentEnum $align
   *   Determine the alignment of flex.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacingBig(array $element, AlignmentEnum $align = AlignmentEnum::Default): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing_big',
      '#items' => $element,
      '#align' => $align->value,
    ];
  }

  /**
   * Wrap an element with a huge vertical spacing.
   *
   * @param array $element
   *   Render array.
   * @param \Drupal\server_general\ThemeTrait\Enum\AlignmentEnum $align
   *   Determine the alignment of flex.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacingHuge(array $element, AlignmentEnum $align = AlignmentEnum::Default): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing_huge',
      '#items' => $element,
      '#align' => $align->value,
    ];
  }

  /**
   * Conditionally wrap an element with bottom padding.
   *
   * @param array $element
   *   Render array.
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_item_list
   *   The field object where the referenced items are stored.
   *
   * @return array
   *   Render array.
   */
  public function wrapConditionalContainerBottomPadding(array $element, EntityReferenceFieldItemListInterface $field_item_list) {
    if ($field_item_list->isEmpty()) {
      return $element;
    }

    $paragraphs = $field_item_list->referencedEntities();
    if (empty($paragraphs)) {
      return $element;
    }
    $paragraph = $paragraphs[count($paragraphs) - 1];

    if (!($paragraph instanceof ParagraphInterface)) {
      return $element;
    }

    // The paragraph types that don't require a bottom padding, if they are
    // the last paragraph on the page.
    $paragraph_types_with_no_bottom_padding = [
      'documents',
      'related_content',
      'quote',
    ];

    return in_array($paragraph->bundle(), $paragraph_types_with_no_bottom_padding) ? $element : $this->wrapContainerBottomPadding($element);
  }

  /**
   * Wrap an element with bottom padding.
   *
   * You will likely want to use `wrapConditionalContainerBottomPadding`.
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
   * Wrap an element with a max width container.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param \Drupal\server_general\ThemeTrait\Enum\WidthEnum $width
   *   Max width.
   * @param bool $is_center
   *   Defines if content is centered.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerMaxWidth(array|string|TranslatableMarkup $element, WidthEnum $width, bool $is_center = FALSE): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_max_width',
      '#element' => $element,
      '#width' => $width->value,
      '#is_center' => $is_center,
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
   * Wrap an element with a tag, e.g. `<h1></h1>` or `<p></p>`.
   *
   * If the tag is h1 to h5, the element will be wrapped with `::wrapProseText`.
   * This ensures that the heading is styled the same for prose and non-prose.
   * The non-prose version should not have a margin applied to it.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param \Drupal\server_general\ThemeTrait\Enum\HtmlTagEnum $tag
   *   The HTML tag to wrap the element with.
   *
   * @return array
   *   Render array.
   */
  protected function wrapHtmlTag(array|string|TranslatableMarkup $element, HtmlTagEnum $tag): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    $element = [
      '#theme' => 'server_theme_wrap_html_tag',
      '#tag' => $tag->value,
      '#element' => $element,
    ];

    $element = $this->wrapProseText($element);

    return $element;
  }

  /**
   * Wrap an element with a div with `hidden` cless.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   *
   * @return array
   *   Render array.
   */
  protected function wrapHidden(array|string|TranslatableMarkup $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_wrap_hidden',
      '#element' => $element,
    ];
  }

  /**
   * Wrap an image with the `figure` tag.
   *
   * @param array $element
   *   The image render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapImageWithFigureTag(array $element): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_wrap_image_with_figure',
      '#element' => $element,
    ];
  }

  /**
   * Wrap a text element with font weight.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param \Drupal\server_general\ThemeTrait\Enum\FontWeightEnum $weight
   *   Font weight of the text.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextFontWeight(array|string|TranslatableMarkup $element, FontWeightEnum $weight = FontWeightEnum::Normal): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__font_weight',
      '#font_weight' => $weight->value,
      '#element' => $element,
    ];
  }

  /**
   * Wrap a text element with font size.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $element
   *   The render array, string or a TranslatableMarkup object.
   * @param \Drupal\server_general\ThemeTrait\Enum\FontSizeEnum $size
   *   Font size of the text.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextResponsiveFontSize(array|string|TranslatableMarkup $element, FontSizeEnum $size = FontSizeEnum::Base): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__responsive_font_size',
      '#size' => $size->value,
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
   * @param \Drupal\server_general\ThemeTrait\Enum\LineClampEnum $lines
   *   The lines to clamp.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextLineClamp(array|string|TranslatableMarkup $element, LineClampEnum $lines): array {
    $element = $this->filterEmptyElements($element);
    if (empty($element)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__line_clamp',
      '#lines' => $lines->value,
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
   * @param \Drupal\server_general\ThemeTrait\Enum\TextColorEnum $color
   *   The font color.
   *
   * @return array
   *   Render array.
   */
  protected function wrapTextColor(array|string|TranslatableMarkup $element, TextColorEnum $color): array {
    if (is_array($element)) {
      $element = $this->filterEmptyElements($element);
    }
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_text_decoration__font_color',
      '#color' => $color->value,
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
