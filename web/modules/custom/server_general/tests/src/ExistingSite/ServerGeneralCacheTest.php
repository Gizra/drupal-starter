<?php

use Drupal\views\Entity\View;
use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\views\Views;

/**
 * Cache-related assertions.
 */
class ServerGeneralCacheTest extends ExistingSiteBase {

  /**
   * Test Search API-based views, they should use Search API tag-based caching.
   */
  public function testSearchApiBasedViewsCache() {
    foreach (Views::getAllViews() as $view) {
      $this->checkViewForSearchApiTagBasedCaching($view);
    }
  }

  /**
   * Checks if the given view uses the Search API tag-based caching.
   *
   * @param \Drupal\views\Entity\View $view
   *   The view to check.
   */
  protected function checkViewForSearchApiTagBasedCaching(View $view) {
    if ($view->get('base_field') != 'search_api_id') {
      return;
    }
    $displays = $view->get('display');
    foreach ($displays as $display) {
      if (!isset($display['display_options']['cache'])) {
        continue;
      }
      $this->assertEquals('search_api_tag', $display['display_options']['cache']['type']);
    }
  }

}
