<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\paragraphs\Entity\Paragraph;
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
   * Tests the sanity of facet configurations.
   */
  public function testFacetConfigSanity() {
    $facets = \Drupal::entityTypeManager()
      ->getStorage('facets_facet')
      ->loadMultiple();

    if (empty($facets)) {
      return;
    }

    /** @var \Drupal\facets\FacetInterface $facet */
    foreach ($facets as $facet) {
      $config_value = $facet->get('only_visible_when_facet_source_is_visible');

      if (!$config_value) {
        continue;
      }
      $this->fail("The facet {$facet->id()} has 'only_visible_when_facet_source_is_visible' set to true. It is not compatible with Paragraphs-based embedding and rendering.");
    }
    $this->expectNotToPerformAssertions();
  }

  /**
   * Checks if search page (LP with Search PT) isn't indexed.
   *
   * See "ExcludeNodeByPathAliasProcessor.php".
   */
  public function testSearchPageNotIndexed(): void {
    $paragraph = Paragraph::create([
      'type' => 'search',
      'field_title' => [
        'value' => 'Search Test',
      ],
    ]);
    $paragraph->save();
    $this->markEntityForCleanup($paragraph);

    $path_alias = '/search_6KmX9x99aG5o13xvqjkO868iR';
    $title = 'Search_6KmX9x99aG5o13xvqjkO868iR';

    // Create a search LP with a specific title and path.
    $node = $this->createNode([
      'title' => $title,
      'langcode' => 'en',
      'type' => 'news',
      'status' => 1,
      'field_paragraphs' => [
        $paragraph,
      ],
      'path' => [
        'pathauto' => FALSE,
        'alias' => $path_alias,
      ],
    ]);
    $node->setPublished()->save();

    // Trigger indexing.
    $this->triggerPostRequestIndexing();

    $this->waitForSearchIndex(function () use ($node): void {
      $this->drupalGet('/search', [
        'query' => [
          'key' => $node->label(),
        ],
      ]);
      $session = $this->assertSession();
      // Without search index processor this page should be in the results.
      $session->elementTextContains('css', '.view-search', $node->label());
    });

    // Get the configuration factory service.
    $config_factory = \Drupal::configFactory();

    // Load the configuration of our "exclude_nodes_by_path_alias"
    // search processor.
    $config = $config_factory->getEditable('search_api.index.server_dev');

    // Get the existing "excluded nodes" config array.
    $excluded_nodes_original = $excluded_nodes_temporary = $config->get('processor_settings.exclude_nodes_by_path_alias.excluded_nodes');

    if (!in_array($path_alias, $excluded_nodes_original)) {
      // Add a new entry to the excluded nodes array.
      $excluded_nodes_temporary[] = $path_alias;
      // Set the updated excluded nodes array back to the configuration.
      $config->set('processor_settings.exclude_nodes_by_path_alias.excluded_nodes', $excluded_nodes_temporary);

      // Save the configuration.
      $config->save();
    }

    // Save LP again so that cache gets cleared.
    $node->save();

    // Trigger indexing.
    $this->triggerPostRequestIndexing();

    // Wait for indexing to complete.
    $this->waitForSearchIndex(function () use ($node): void {
      // First search using the exact long phrase.
      $this->drupalGet('/search', [
        'query' => [
          'key' => $node->label(),
        ],
      ]);
      $session = $this->assertSession();
      $session->elementTextContains('css', '.view-empty', 'No results found');
    });

    // Restore original settings.
    $config->set('processor_settings.exclude_nodes_by_path_alias.excluded_nodes', $excluded_nodes_original);
    $config->save();
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
    $this->assertSession()->statusCodeEquals(Response::HTTP_BAD_REQUEST);
    // We have an error message that describes the problem.
    $this->assertStringContainsString("contains a non-scalar", $this->getCurrentPage()->getContent());
  }

  /**
   * Test that facets are set to be preserved when using filters in Search view.
   */
  public function testFacetsPreservedWhenUsingFilters() {
    $config = $this->container->get('config.factory')->get('views.view.search');
    $preserve_facets = $config->get('display.default.display_options.query.options.preserve_facet_query_args');
    $this->assertTrue($preserve_facets);
  }

}
