<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Cache-related assertions.
 */
class ServerGeneralCacheTest extends ServerGeneralTestBase {

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

  /**
   * Test the homepage menu cache invalidation.
   */
  public function testHomepageMenuCache() {
    $this->drupalGet('<front>');
    $this->assertMenuState('What We Do');
    $this->addMenuItem('New Menu Item', 'node/1');
    $this->drupalGet('<front>');
    $this->assertMenuState('New Menu Item');
  }

  /**
   * Assert the presence of a menu link.
   *
   * @param string $link_title
   *   The menu link title to check for.
   */
  protected function assertMenuState($link_title) {
    $link = $this->getSession()->getPage()->findLink($link_title);
    $this->assertNotNull($link, sprintf('The link "%s" should be present in the menu.', $link_title));
  }

  /**
   * Add a menu item programmatically.
   *
   * @param string $title
   *   The menu link title.
   * @param string $path
   *   The path for the menu link.
   */
  protected function addMenuItem($title, $path) {
    $menu_link = MenuLinkContent::create([
      'title' => $title,
      'link' => ['uri' => 'internal:/' . $path],
      'menu_name' => 'main',
    ]);
    $menu_link->save();
    $this->markEntityForCleanup($menu_link);
  }

}
