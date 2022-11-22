<?php

namespace Drupal\pluggable_entity_view_builder_example;

/**
 * Helper method for wrapping an element with container classes.
 */
trait ElementContainerTrait {

  /**
   * Wrap an element with a container class.
   *
   * @param array $element
   *   The render array of the element which is the component.
   *
   * @return array
   *   Render array.
   */
  protected function wrapElementWithContainer(array $element): array {
    return [
      '#theme' => 'pluggable_entity_view_builder_example_container',
      '#content' => $element,
    ];
  }

}
