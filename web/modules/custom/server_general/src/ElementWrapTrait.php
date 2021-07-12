<?php

namespace Drupal\server_general;

use Drupal\Component\Utility\Html;

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
      '#theme' => 'server_general_wide_container',
      '#element' => $element,
    ];
  }

  /**
   * Get the prefix to how as the component's title.
   *
   * @param string $title
   *   The component name.
   * @param string $link
   *   Optional; Link to the design.
   *
   * @return array
   *   Render array.
   */
  protected function getComponentPrefix($title, $link = NULL): array {
    $id = Html::getUniqueId($title);

    return [
      '#theme' => 'server_style_guide_header',
      '#title' => $title,
      '#unique_id' => $id,
      '#link' => $link,
    ];
  }

}
