<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\server_general\ThemeTrait\Enum\HtmlTagEnum;

/**
 * Helper method for building Title and labels of a content.
 */
trait TitleAndLabelsThemeTrait {

  use ElementWrapThemeTrait;

  /**
   * Build the page title element.
   *
   * @param string $title
   *   The title.
   *
   * @return array
   *   The render array.
   */
  protected function buildPageTitle(string $title): array {
    return [
      '#theme' => 'server_theme_page_title',
      '#title' => $title,
    ];
  }

  /**
   * Build the labels from text.
   *
   * @param array $labels
   *   The Labels to show.
   *
   * @return array
   *   Render array.
   */
  protected function buildLabelsFromText(array $labels): array {
    // Type labels.
    $items = [];

    foreach ($labels as $label) {
      $items[] = [
        '#theme' => 'server_theme_label',
        '#label' => $label,
      ];
    }

    return [
      '#theme' => 'server_theme_labels',
      '#items' => $items,
    ];
  }

  /**
   * Build the paragraph title.
   *
   * @param string $title
   *   The title.
   *
   * @return array
   *   Render array.
   */
  protected function buildParagraphTitle(string $title): array {
    return $this->wrapHtmlTag($title, HtmlTagEnum::H2);
  }

}
