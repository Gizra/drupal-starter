<?php

namespace Drupal\server_general;

/**
 * Trait ComponentWrapTrait.
 *
 * Helper methods to wrap output in a tailwind container.
 */
trait ComponentWrapTrait {

  /**
   * Wrap a component with a container class, and some identifier class.
   *
   * The identifier class is mainly used to distinguish the component on the
   * phpunit tests.
   *
   * @param array $element
   *   The renderable array of the element which is the component.
   * @param string $wrapper_class_name
   *   The class name to wrap the element with.
   * @param string $container_type
   *   Optional; To allow each component to define their own container we allow
   *   defining the type of the container (i.e. a TailWind container). Defaults
   *   to `fluid-container-narrow`.
   *
   * @return array
   *   A renderable array.
   */
  protected function wrapComponentWithContainer(array $element, $wrapper_class_name, $container_type = 'fluid-container-narrow') {
    $build = [];
    $build['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          $wrapper_class_name,
        ],
      ],
    ];

    if ($container_type) {
      $build['container']['#attributes']['class'][] = $container_type;
    }

    $build['container']['element'] = $element;

    return $build;
  }

}
