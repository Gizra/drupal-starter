<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * A test case to test search integration.
 */
class ServerGeneralSearchTest extends ServerGeneralSearchTestBase {

  const ES_WAIT_MICRO_SECONDS = 200;

  const ES_RETRY_LIMIT = 20;

  /**
   * Test basic indexing.
   */
  public function testBasicIndexing() {
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);
    $this->drupalGet('/admin/config/search/elasticsearch-connector/cluster/server');
    $empty_text = 'There are 0 items indexed on the server for this index.';

    // The server is available.
    $this->assertSession()->elementTextContains('css', '.admin-elasticsearch-statistics tr td', '1 Nodes');
    $this->assertSession()->elementTextNotContains('css', '.admin-elasticsearch-statistics', 'red');

    $this->drupalGet('/admin/config/search/search-api/index/server_dev/clear');
    $this->submitForm([], 'Confirm');

    // After the purge, we should not have items.
    $this->drupalGet('/admin/config/search/search-api/index/server_dev');
    $this->assertSession()->pageTextContains($empty_text);

    $node = $this->createNode([
      'title' => 'Search API + ES test',
      'type' => 'news',
      'uid' => $admin->id(),
    ]);
    $node->setPublished()->save();
    $this->triggerPostRequestIndexing();

    $this->waitForElasticSearchIndex(function () use ($empty_text) {
      $this->drupalGet('/admin/config/search/search-api/index/server_dev');
      $this->assertSession()->pageTextNotContains($empty_text);
      $this
        ->assertSession()
        ->pageTextMatches('/There (are|is) [0-9]+ item(s)* indexed on the server for this index/');
    });
  }

}
