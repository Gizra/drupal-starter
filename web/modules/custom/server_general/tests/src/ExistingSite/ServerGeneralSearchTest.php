<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * A test case to test search integration.
 */
class ServerGeneralSearchTest extends ServerGeneralSearchTestBase {

  const ES_WAIT_MICRO_SECONDS = 200;

  const ES_RETRY_LIMIT = 20;

  /**
   * Test freetext search.
   */
  public function testFreetextSearch() {
    $english_node_title = 'This is a node that should be indexed';
    $this->createNode([
      'title' => $english_node_title,
      'type' => 'news',
      'langcode' => 'en',
      'moderation_state' => 'published',
    ]);
    $this->triggerPostRequestIndexing();
    $this->waitForSearchIndex(function () use ($english_node_title) {
      $this->drupalGet('/search', [
        'query' => [
          'key' => 'indexed',
        ],
      ]);
      $session = $this->assertSession();
      $session->elementTextContains('css', '.view-search', $english_node_title);
    });
  }

  /**
   * Test synonyms.
   */
  public function testSynonyms() {
    $english_node_title = 'Dress';
    $this->createNode([
      'title' => $english_node_title,
      'type' => 'news',
      'langcode' => 'en',
      'moderation_state' => 'published',
    ]);
    $this->triggerPostRequestIndexing();
    $this->waitForSearchIndex(function () use ($english_node_title) {
      $this->drupalGet('/search', [
        'query' => [
          'key' => 'clothing',
        ],
      ]);
      $session = $this->assertSession();
      $session->elementTextContains('css', '.view-search', $english_node_title);
    });
  }

  /**
   * Test the relevance sort, boosting of title should be the highest.
   */
  public function testRelevanceSort() {
    $node = $this->createNode([
      'type' => 'news',
      'title' => 'aspecialword in the title',
      'body' => 'something else in the body',
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();
    $node = $this->createNode([
      'type' => 'news',
      'title' => 'something else in the title',
      'field_body' => 'aspecialword in the body',
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();
    $this->triggerPostRequestIndexing();
    $this->waitForSearchIndex(function () {
      $assert = $this->assertSession();
      $this->drupalGet('/search', [
        'query' => [
          'key' => 'aspecialword',
        ],
      ]);
      // The first result should be the one with the word in the title.
      $assert->elementTextEquals('xpath', "(//div[contains(@class, 'views-row')])[1]//a", 'aspecialword in the title');
      // The second result should be the one with the word in the body.
      $assert->elementTextEquals('xpath', "(//div[contains(@class, 'views-row')])[2]//a", 'something else in the title');
    });
  }

  /**
   * Test the content type facet.
   */
  public function testContentTypeFacet() {
    $title = 'A facetedword news node';
    $this->createNode([
      'title' => $title,
      'type' => 'news',
      'moderation_state' => 'published',
    ]);
    $this->triggerPostRequestIndexing();
    $this->waitForSearchIndex(function () use ($title) {
      $assert = $this->assertSession();
      $this->drupalGet('/search', [
        'query' => [
          'key' => 'facetedword',
        ],
      ]);
      $assert->elementTextContains('css', '.view-search', 'Filter by Content type');

      // The facet link keeps the search term.
      $link = $assert->elementExists('css', '.view-search a.bef-link[data-bef-value="news"]');
      $href = urldecode($link->getAttribute('href'));
      $this->assertStringContainsString('key=facetedword', $href);
      $this->assertStringContainsString('type[news]=news', $href);

      $this->drupalGet('/search', [
        'query' => [
          'key' => 'facetedword',
          'type' => ['news' => 'news'],
        ],
      ]);
      $assert->elementTextContains('css', '.view-search', $title);
      $assert->elementExists('css', '.view-search a.bef-link--selected[data-bef-value="news"]');

      // A content type without results filters everything out.
      $this->drupalGet('/search', [
        'query' => [
          'key' => 'facetedword',
          'type' => ['landing_page' => 'landing_page'],
        ],
      ]);
      $assert->elementTextNotContains('css', '.view-search', $title);
    });
  }

  /**
   * Tests that special query parameters don't crash the search.
   *
   * @see https://stackoverflow.com/questions/77230889/how-do-i-fix-symfony-6-error-input-value-contains-a-non-scalar-value
   */
  public function testSpecialQueryParameter() {
    $this->drupalGet('/search', [
      'query' => [
        'key[$testing]' => '1',
      ],
    ]);
    // The invalid search term is ignored.
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}
