<?php

declare(strict_types=1);

namespace Drupal\server_general;

/**
 * Helper methods to build Page layouts.
 *
 * A regular single column page layout doesn't need any method here, as one can
 * use inside PEVB something like`$this->wrapContainerWide($elements)`.
 * So it's likely this trait will hold only the Main and sidebar helper method,
 * unless there's a need for a more complex layout.
 *
 * Trait provides helper methods for each layout. One method equals one theme
 * file.
 */
trait ElementLayoutTrait {

  /**
   * Build Main and sidebar layout.
   *
   * @param array $main
   *   The main render array.
   * @param array $sidebar
   *   The sidebar render array.
   * @param bool $is_sidebar_first
   *   Determine if sidebar should appear first on mobile/tablet layout.
   *   Defaults to FALSE.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementLayoutMainAndSidebar(array $main, array $sidebar = [], bool $is_sidebar_first = FALSE) {
    return [
      '#theme' => 'server_theme_element_layout__main_and_sidebar',
      '#main' => $main,
      '#sidebar' => $sidebar,
      '#is_sidebar_first' => $is_sidebar_first,
    ];
  }

}
