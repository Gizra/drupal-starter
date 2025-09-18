<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum;

/**
 * Helper methods for rendering Documents media elements.
 */
trait DocumentsThemeTrait {

  use ElementLayoutThemeTrait;

  /**
   * Builds a "Document" list.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The subtitle render array.
   * @param array $items
   *   Render array of documents.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementDocuments(string $title, array $body, array $items): array {
    return $this->buildElementLayoutTitleBodyAndItems(
      $title,
      $body,
      $this->buildElementLayoutItemsWithViewMore($items, 2),
      BackgroundColorEnum::LightGray,
    );
  }

  /**
   * Builds a single document item.
   *
   * @param string $title
   *   The title.
   * @param string $url
   *   The Url string.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementDocument(string $title, string $url): array {
    return [
      '#theme' => 'server_theme_media__document',
      '#url' => $url,
      '#title' => $title,
    ];
  }

}
