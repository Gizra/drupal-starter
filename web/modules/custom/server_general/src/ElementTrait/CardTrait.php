<?php

declare(strict_types=1);

namespace Drupal\server_general\ElementTrait;

use Drupal\server_general\ElementLayoutTrait;

/**
 * Helper methods for rendering Card elements.
 */
trait CardTrait {

  use ElementLayoutTrait;

  /**
   * Wrap multiple cards with a grid.
   *
   * @param array $items
   *   The elements as render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildCards(array $items): array {
    return [
      '#theme' => 'server_theme_cards',
      '#items' => $items,
    ];
  }

}
