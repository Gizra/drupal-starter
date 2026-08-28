<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the agent-discovery search endpoint and its catalogs.
 *
 * Covers GET /api/search (the JSON search an AI agent calls), the OpenAPI
 * description it is documented by, and the two discovery catalogs that point an
 * agent at that description.
 */
class ServerGeneralAgentDiscoveryTest extends ServerGeneralSearchTestBase {

  /**
   * The API catalog is a linkset pointing at the OpenAPI description.
   */
  public function testApiCatalog(): void {
    $this->drupalGet('/.well-known/api-catalog');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(Response::HTTP_OK);
    $assert->responseHeaderContains('Content-Type', 'application/linkset+json');

    $data = $this->decodeJson();
    $href = $data['linkset'][0]['service-desc'][0]['href'] ?? '';
    $this->assertStringEndsWith('/api/search/openapi.yaml', $href);
  }

  /**
   * The AI catalog exposes the configured description and queries.
   */
  public function testAiCatalog(): void {
    $this->drupalGet('/.well-known/ai-catalog.json');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(Response::HTTP_OK);
    $assert->responseHeaderContains('Content-Type', 'application/json');

    $data = $this->decodeJson();
    $this->assertArrayHasKey('description', $data);
    $this->assertNotEmpty($data['description']);
    $this->assertArrayHasKey('representativeQueries', $data);
    $this->assertIsArray($data['representativeQueries']);
    $this->assertStringEndsWith('/api/search/openapi.yaml', $data['openapi'] ?? '');
  }

  /**
   * The OpenAPI route serves the spec with the live origin substituted in.
   */
  public function testOpenApiSpec(): void {
    $this->drupalGet('/api/search/openapi.yaml');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(Response::HTTP_OK);
    $assert->responseHeaderContains('Content-Type', 'application/yaml');

    $body = $this->getCurrentPage()->getContent();
    $this->assertStringContainsString('openapi: 3.1.0', $body);
    // The placeholder must have been replaced with the request origin.
    $this->assertStringNotContainsString('__SITE_ORIGIN__', $body);
  }

  /**
   * A fulltext query returns the well-formed envelope with the matching node.
   */
  public function testSearchReturnsEnvelope(): void {
    $title = 'Zorptastic agent discovery fixture';
    $this->createNode([
      'title' => $title,
      'type' => 'news',
      'body' => 'A body mentioning zorptastic content for indexing.',
      'moderation_state' => 'published',
    ]);
    $this->triggerPostRequestIndexing();

    $this->waitForSearchIndex(function () use ($title) {
      $this->drupalGet('/api/search', ['query' => ['key' => 'zorptastic']]);
      $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
      $data = $this->decodeJson();

      $this->assertArrayHasKey('count', $data);
      $this->assertArrayHasKey('results', $data);
      $this->assertGreaterThan(0, $data['count']);
      $this->assertSame($data['count'], count($data['results']));

      $titles = array_column($data['results'], 'title');
      $this->assertContains($title, $titles);

      foreach ($data['results'] as $result) {
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('snippet', $result);
        $this->assertArrayHasKey('type', $result);
      }
    });
  }

  /**
   * A blank query short-circuits to an empty, well-formed envelope.
   */
  public function testBlankQueryReturnsEmpty(): void {
    $this->drupalGet('/api/search', ['query' => ['key' => '   ']]);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $data = $this->decodeJson();
    $this->assertSame(0, $data['count']);
    $this->assertSame([], $data['results']);
  }

  /**
   * Decodes the current page body as JSON.
   *
   * @return array
   *   The decoded payload.
   */
  protected function decodeJson(): array {
    $data = json_decode($this->getCurrentPage()->getContent(), TRUE);
    $this->assertIsArray($data);
    return $data;
  }

}
