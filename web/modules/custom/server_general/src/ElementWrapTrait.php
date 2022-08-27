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
    if (!$element) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_wide',
      '#element' => $element,
    ];
  }

}
