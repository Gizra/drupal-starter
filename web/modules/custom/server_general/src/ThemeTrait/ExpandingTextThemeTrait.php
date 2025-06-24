<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Component\Render\MarkupInterface;

/**
 * ThemeTrait for building Expanding text element.
 */
trait ExpandingTextThemeTrait {

  /**
   * Build the Expanding text element.
   *
   * @param string|array|\Drupal\Component\Render\MarkupInterface $text
   *   The text.
   * @param int|null $lines_to_clamp
   *   Number of lines to show initially.
   * @param string|array|\Drupal\Component\Render\MarkupInterface|null $button_label_more
   *   Button text to expand.
   * @param string|array|\Drupal\Component\Render\MarkupInterface|null $button_label_less
   *   Button text to collapse.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementExpandingText(string|array|MarkupInterface $text, ?int $lines_to_clamp = NULL, string|array|MarkupInterface|null $button_label_more = NULL, string|array|MarkupInterface|null $button_label_less = NULL): array {
    return [
      '#theme' => 'server_theme_element__expanding_text',
      '#text' => $text,
      '#lines_to_clamp' => $lines_to_clamp,
      '#button_label_more' => $button_label_more,
      '#button_label_less' => $button_label_less,
    ];
  }

}
