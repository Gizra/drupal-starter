<?php

declare(strict_types=1);

namespace Drupal\server_general;

/**
 * Helper methods to build Page layouts.
 *
 * A "regular" single column page layout doesn't need any method here, as one
 * can use inside PEVB something like
 * `$build[] = $this->wrapContainerWide($elements);`.
 * So it's likely this trait will hold only the Main and sidebar helper method,
 * unless there's a need for a more complex layout.
 *
 * Trait is providing helper methods for each card. One method equals one theme
 * file.
 */
trait PageLayoutTrait {

  /**
   * Build main and sidebar.
   *
   * @param array $main
   *   The main render array.
   * @param array $sidebar
   *   The sidebar render array.
   * @param bool $is_sidebar_first
   *   Whether to place the sidebar first on mobile/tablet layout.
   *   Default: FALSE.
   *
   * @return array
   *   The main and sidebar render array.
   */
  protected function buildPageLayoutMainAndSidebar(array $main, array $sidebar = [], bool $is_sidebar_first = FALSE) {
    return [
      '#theme' => 'server_theme_page_layout__main_and_sidebar',
      '#main' => $main,
      '#sidebar' => $sidebar,
      '#is_sidebar_first' => $is_sidebar_first,
    ];
  }

}
