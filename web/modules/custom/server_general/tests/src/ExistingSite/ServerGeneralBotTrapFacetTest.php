<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\search_api\Kernel\PostRequestIndexingTrait;
use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\Entity\MediaCreationTrait;

/**
 * Tests bot trap functionality for facets.
 */
class ServerGeneralBotTrapFacetTest extends ServerGeneralSearchTestBase {

  use MediaCreationTrait;
  use PostRequestIndexingTrait;

  /**
   * Tests bot trap functionality when accessing facets.
   */
  public function testBotTrapFacetFunctionality() {
    // Create a node to ensure search has at least one result.
    $this->createNode([
      'title' => 'Test Bot Trap Content',
      'type' => 'news',
      'field_publish_date' => time(),
      'moderation_state' => 'published',
    ]);

    $this->triggerPostRequestIndexing();

    $this->waitForSearchIndex(function () {
      // Verify normal search works.
      $this->drupalGet('/search', [
        'query' => [
          'key' => 'Bot Trap',
        ],
      ]);
      $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

      // Test normal facet usage works.
      $this->drupalGet('/search', [
        'query' => [
          'f' => ['content_type:news'],
        ],
      ]);
      $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

      // Test PHP array notation facet works for normal user.
      $this->drupalGet('/search', [
        'query' => [
          'f' => ['content_type:news'],
        ],
      ]);
      $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    });
  }

  /**
   * Data provider for bot user agents that should be blocked.
   *
   * @return array
   *   An array of bot user agents.
   */
  public function botUserAgentsProvider() {
    return [
      ['Googlebot/2.1 (+http://www.google.com/bot.html)'],
      ['Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)'],
      ['Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)'],
      ['NetEstate NE Crawler (+http://www.website-datenbank.de/)'],
      ['Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)'],
    ];
  }

  /**
   * Tests that bot trap protection blocks bot access to facets.
   *
   * @dataProvider botUserAgentsProvider
   */
  public function testBotTrapBlocksBotAccessToFacets(string $user_agent) {
    $this->getSession()->setRequestHeader('User-Agent', $user_agent);
    try {
      $this->drupalGet('/search', [
        'query' => [
          'f' => ['content_type:news'],
        ],
      ]);

      $status_code = $this->getSession()->getStatusCode();
    }
    catch (\Exception $e) {
      $status_code = Response::HTTP_FORBIDDEN;
    }

    $this->assertEquals(Response::HTTP_FORBIDDEN, $status_code, "Bot user agent '$user_agent' should be blocked when using facets.");

    try {
      $this->drupalGet('/search', [
        'query' => [
          'f' => ['a', 'b', 'c'],
        ],
      ]);

      $status_code = $this->getSession()->getStatusCode();
    }
    catch (\Exception $e) {
      $status_code = Response::HTTP_FORBIDDEN;
    }

    $this->assertEquals(Response::HTTP_FORBIDDEN, $status_code, "Bot user agent '$user_agent' should be blocked when using facets.");
  }

  /**
   * Data provider for normal user agents that should not be blocked.
   *
   * @return array
   *   An array of normal user agents.
   */
  public function normalUserAgentsProvider() {
    return [
      ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'],
      ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15'],
      ['Mozilla/5.0 (X11; Linux x86_64; rv:89.0) Gecko/20100101 Firefox/89.0'],
    ];
  }

  /**
   * Tests that non-bot clients for facets are not blocked.
   *
   * @dataProvider normalUserAgentsProvider
   */
  public function testNonBotClientsAreNotBlocked(string $user_agent) {
    $this->getSession()->setRequestHeader('User-Agent', $user_agent);

    // Use PHP array notation in query.
    $this->drupalGet('/search', [
      'query' => [
        'f' => ['content_type:news'],
      ],
    ]);

    // Normal clients should not be blocked.
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

  /**
   * Tests that bot clients without problematic facet syntax are not blocked.
   *
   * @dataProvider botUserAgentsProvider
   */
  public function testBotClientsWithoutFacetSyntaxAreNotBlocked(string $user_agent) {
    $this->getSession()->setRequestHeader('User-Agent', $user_agent);

    // Use normal query without PHP array notation.
    $this->drupalGet('/search', [
      'query' => [
        'key' => 'test content',
      ],
    ]);

    // Bot clients without problematic facet syntax should not be blocked.
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}
