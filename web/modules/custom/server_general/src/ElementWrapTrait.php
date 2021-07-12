<?php

namespace Drupal\server_general;

/**
 * Helper method for wrapping an element.
 */
trait ElementWrapTrait {

  /**
   * Wrap an element, with wide container.
   *
   * @return array
   *   Render array.
   */
  protected function wrapElementWideContainer(array $element): array {
    return [
      '#theme' => 'server_theme_container_wide',
      '#element' => $element,
    ];
  }

}
