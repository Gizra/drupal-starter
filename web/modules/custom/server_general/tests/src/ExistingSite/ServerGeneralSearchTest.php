<?php

namespace Drupal\Tests\server_general\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * A test case to test search integration.
 */
class ServerGeneralSearchTest extends ExistingSiteBase {

  const ES_WAIT_MICRO_SECONDS = 200;

  const ES_RETRY_LIMIT = 20;

  /**
   * Test basic indexing.
   */
  public function testBasicIndexing() {
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);
    $this->drupalGet('/admin/config/search/elasticsearch-connector/cluster/server');

    // The server is available.
    $this->assertSession()->elementTextContains('css', '.admin-elasticsearch-statistics tr td', '1 Nodes');
    $this->assertSession()->elementTextNotContains('css', '.admin-elasticsearch-statistics', 'red');

    $this->drupalGet('/admin/config/search/search-api/index/server_dev/clear');
    $this->submitForm([], 'Confirm');

    // After the purge, we should not have items.
    $this->drupalGet('/admin/config/search/search-api/index/server_dev');
    $this->assertSession()->pageTextContains('There are 0 items indexed on the server for this index.');

    $node = $this->createNode([
      'title' => 'Search API + ES test',
      'type' => 'page',
      'uid' => $admin->id(),
    ]);
    $node->setPublished()->save();
    search_api_cron();

    // ES is relatively slow compared to the execution of the test, we
    // wait until the item appears in the index.
    $attempts = 0;
    do {
      $this->drupalGet('/admin/config/search/search-api/index/server_dev');
      usleep(self::ES_WAIT_MICRO_SECONDS);
      try {
        $this->assertSession()->pageTextNotContains('There are 0 items indexed on the server for this index.');
        $this
          ->assertSession()
          ->pageTextMatches('/There are [0-9]+ items indexed on the server for this index/');
      }
      catch (\Exception $e) {
        $attempts++;
        if ($attempts > self::ES_RETRY_LIMIT) {
          throw $e;
        }
        continue;
      }
      break;

    } while (TRUE);
  }

}
