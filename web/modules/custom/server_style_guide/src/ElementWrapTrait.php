<?php

namespace Drupal\server_style_guide;

use Drupal\Component\Utility\Html;

/**
 * Helper method for wrapping an element.
 */
trait ElementWrapTrait {

  /**
   * Wrap an element, with no container.
   *
   * @return array
   *   Render array.
   */
  protected function wrapElementNoContainer(array $element, string $title, string $link = NULL): array {
    return [
      '#theme' => 'server_style_guide_no_container',
      '#title' => $this->getComponentPrefix($title, $link),
      '#element' => $element,
    ];
  }

  /**
   * Wrap an element, with wide container.
   *
   * @return array
   *   Render array.
   */
  protected function wrapElementWideContainer(array $element, string $title, string $link = NULL): array {
    return [
      '#theme' => 'server_style_guide_container_wide',
      '#title' => $this->getComponentPrefix($title, $link),
      '#element' => $element,
    ];
  }

  /**
   * Get the prefix to show as the component's title.
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
