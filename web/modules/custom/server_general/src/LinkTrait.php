<?php

namespace Drupal\server_general;

use Drupal\Core\Url;

/**
 * Helper method for building a link, and decorating it.
 */
trait LinkTrait {

  /**
   * Build a link.
   *
   * @param string $title
   *   The title of the link.
   * @param \Drupal\Core\Url $url
   *   The URL object.
   * @param string $color
   *   The color of the link. The color on hover will be calculated from it.
   *   see `server-theme-text-decoration--link.html.twig`.
   * @param int|null $lines_clamp
   *   The lines to clamp. Values are 1 to 4, or NULL for none. Defaults to 3.
   * @param string $underline
   *   Determine if an underline should appear. Possible values are:
   *   - `always`: Always show.
   *   - `hover`: Show only on hover.
   *   - NULL: No underline at all.
   *   Defaults to `hover.
   * @param bool $show_external_icon
   *   Determine if an external icon suffix should appear if the URL is
   *   external. Defaults to TRUE.
   *
   * @return array
   *   Render array.
   */
  public function buildLink(string $title, Url $url, string $color = 'dark-gray', ?int $lines_clamp = 3, string $underline = 'hover', bool $show_external_icon = TRUE): array {
    $element = [
      '#theme' => 'server_theme_link',
      '#url' => $url,
      '#title' => $title,
      '#show_external_icon' => $show_external_icon,
      '#lines_clamp' => $lines_clamp,
    ];

    return [
      '#theme' => 'server_theme_text_decoration__link',
      '#color' => $color,
      '#underline' => $underline,
      '#element' => $element,
    ];
  }

}
