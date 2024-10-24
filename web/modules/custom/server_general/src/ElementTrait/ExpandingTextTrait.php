<?php

declare(strict_types=1);

namespace Drupal\server_general\ElementTrait;

use Drupal\Component\Render\MarkupInterface;

/**
 * Trait for building Expanding text element.
 */
trait ExpandingTextTrait {

  /**
   * Build the Expanding text element.
   *
   * @param string|array|\Drupal\Component\Render\MarkupInterface $text
   *   The text.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementExpandingText(string|array|MarkupInterface $text): array {
    return [
      '#theme' => 'server_theme_element__expanding_text',
      '#text' => $text,
    ];
  }

}
