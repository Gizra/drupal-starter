<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Helper methods for a line separator.
 */
trait LineSeparatorThemeTrait {

  /**
   * Build a line separator.
   *
   * @return array
   *   Render array.
   */
  protected function buildLineSeparator(): array {
    return [
      '#type' => 'component',
      '#component' => 'server_general:line-separator',
    ];
  }

}
