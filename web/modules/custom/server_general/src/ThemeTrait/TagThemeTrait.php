<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Url;

/**
 * ThemeTrait TagBuilderThemeTrait.
 *
 * Helper method for theming a tag.
 */
trait TagThemeTrait {

  /**
   * Build a tag.
   *
   * @param string $title
   *   The title.
   * @param \Drupal\Core\Url $url
   *   The Url object.
   *
   * @return array
   *   Render array.
   */
  protected function buildTag(string $title, Url $url): array {
    return [
      '#theme' => 'server_theme_tag',
      '#title' => $title,
      '#url' => $url,
    ];
  }

  /**
   * Build the Tags element.
   *
   * @param string $title
   *   The title.
   * @param array $items
   *   The render array built with `::buildTag`.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementTags(string $title, array $items): array {
    return [
      '#theme' => 'server_theme_tags',
      '#title' => $title,
      '#items' => $items,
    ];
  }

}
