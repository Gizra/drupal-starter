<?php

namespace Drupal\server_general;

/**
 * Trait ButtonBuilderTrait.
 *
 * Helper method for building a button.
 */
trait ButtonBuilderTrait {

  /**
   * Build a single button.
   *
   * @param string $label
   *   The label for the button.
   * @param string $url
   *   The url for the button.
   * @param string $color
   *   The color of the button. Default: purple-primary.
   * @param string $icon
   *   The icon of the button.
   * @param string $onclick
   *   The onclick attribute of the button.
   *
   * @return array
   *   A render array.
   */
  protected function buildButton($label, $url, $color = 'purple-primary', $icon = '', $onclick = '') {
    return [
      '#theme' => 'server_theme_button',
      '#label' => $label,
      '#url' => $url,
      '#color' => $color,
      '#icon' => $icon,
      '#onclick' => $onclick,
    ];
  }

}
