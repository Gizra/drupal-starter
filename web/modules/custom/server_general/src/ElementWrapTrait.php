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
  protected function wrapContainerWide(array $element): array {
    if (!$element) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_wide',
      '#element' => $element,
    ];
  }

  /**
   * Wrap an element with a regular vertical spacing.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  protected function wrapContainerVerticalSpacing(array $element): array {
    if (empty($element)) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_container_vertical_spacing',
      '#items' => $element,
    ];
  }

  /**
   * Wrap an element, with Prose text.
   *
   * @return array
   *   Render array.
   */
  protected function wrapElementProseText(array $element): array {
    if (!$element) {
      // Element is empty, so no need to wrap it.
      return [];
    }

    return [
      '#theme' => 'server_theme_prose_text',
      '#text' => $element,
    ];
  }

}
