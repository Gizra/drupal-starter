<?php

declare(strict_types=1);

namespace Drupal\server_general\ElementTrait;

use Drupal\Core\Url;
use Drupal\server_general\ElementLayoutTrait;
use Drupal\server_general\ElementWrapTrait;

/**
 * Helper methods for rendering Quick Links elements.
 */
trait QuickLinksTrait {

  use ElementLayoutTrait;
  use ElementWrapTrait;
  use CardTrait;

  /**
   * Build quick links cards.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param array $items
   *   The render array built with
   *   `ElementLayoutTrait::buildElementLayoutTitleBodyAndItems`.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementQuickLinks(string $title, array $body, array $items): array {
    return $this->buildElementLayoutTitleBodyAndItems(
      $title,
      $body,
      $this->buildCards($items),
    );
  }

  /**
   * Builds a Quick Link element.
   *
   * @param string $title
   *   The title.
   * @param \Drupal\Core\Url $url
   *   The Url object.
   * @param string|null $subtitle
   *   Optional; The subtitle.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementQuickLinkItem(string $title, Url $url, string $subtitle = NULL): array {
    $items = [];
    $items[] = $this->wrapTextResponsiveFontSize($title, 'xl');

    if (!empty($subtitle)) {
      $items[] = $this->wrapTextResponsiveFontSize($subtitle, 'sm');
    }

    return [
      '#theme' => 'server_theme_element__quick_link_item',
      '#items' => $this->wrapContainerVerticalSpacingTiny($items),
      '#url' => $url,
    ];
  }

}
