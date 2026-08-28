<?php

declare(strict_types=1);

namespace Drupal\server_general\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Publishes an agent-queryable search endpoint and its discovery catalogs.
 *
 * Three things travel together here:
 *   - GET /api/search — a plain-JSON, anonymous fulltext search over the
 *     `server_dev` Search API index. This is the one operation the OpenAPI spec
 *     describes; an AI agent (or any HTTP client) can call it directly instead
 *     of scraping the HTML search page at /search.
 *   - GET /api/search/openapi.yaml — the static OpenAPI 3.1 description of that
 *     endpoint, with its `servers[0].url` filled in with the live origin.
 *   - The two discovery catalogs at /.well-known/api-catalog (RFC 9727) and
 *     /.well-known/ai-catalog.json (Google's Agentic Resource Discovery), which
 *     point an agent at the spec.
 *
 * The catalogs' human-facing text (description, representative queries) lives
 * in the `server_general.agent_discovery` config so each project can tailor it
 * without touching code. The search itself is deliberately thin: it owns no
 * ranking or access logic of its own, delegating both to the Search API index
 * (the `content_access` processor restricts results to what the caller may
 * view, so an anonymous request only ever sees published, public nodes).
 */
final class AgentSearchController implements ContainerInjectionInterface {

  /**
   * The machine name of the Search API index backing the endpoint.
   */
  private const INDEX_ID = 'server_dev';

  /**
   * Fallback cap on results, also the maximum a caller may request.
   */
  private const MAX_LIMIT = 20;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * Runs one fulltext search and returns a flat JSON envelope.
   *
   * Query params: `key` (the fulltext term, required), `type` (an optional
   * content-type machine name to filter by), and `limit` (1..20, default 10).
   * The response is `{count, results:[{title, url, snippet, type}]}`; a blank
   * `key` short-circuits to an empty, well-formed envelope rather than an
   * error, matching the OpenAPI contract that only promises a 200.
   */
  public function search(Request $request): JsonResponse {
    $key = trim((string) $request->query->get('key', ''));
    $type = trim((string) $request->query->get('type', ''));
    $limit = (int) $request->query->get('limit', 10);
    $limit = max(1, min(self::MAX_LIMIT, $limit));

    $cacheability = (new CacheableMetadata())
      // Results vary by the query args and by content changes.
      ->addCacheContexts(['url.query_args:key', 'url.query_args:type', 'url.query_args:limit'])
      ->addCacheTags(['node_list']);

    $results = $key === '' ? [] : $this->runSearch($key, $type, $limit, $cacheability);

    $response = new CacheableJsonResponse([
      'count' => count($results),
      'results' => $results,
    ]);
    $response->addCacheableDependency($cacheability);
    return $response;
  }

  /**
   * Serves the OpenAPI description with the live origin filled in.
   */
  public function openApiSpec(Request $request): Response {
    $path = $this->moduleExtensionList->getPath('server_general') . '/search.openapi.yaml';
    $yaml = (string) file_get_contents($path);
    $yaml = str_replace('__SITE_ORIGIN__', $request->getSchemeAndHttpHost(), $yaml);

    $response = new CacheableResponse($yaml);
    $response->headers->set('Content-Type', 'application/yaml; charset=UTF-8');
    $response->getCacheableMetadata()->addCacheContexts(['url.site']);
    return $response;
  }

  /**
   * Publishes the RFC 9727 API catalog (a linkset pointing at the spec).
   */
  public function apiCatalog(Request $request): JsonResponse {
    $origin = $request->getSchemeAndHttpHost();
    $body = [
      'linkset' => [
        [
          'anchor' => $origin . '/api/search',
          'service-desc' => [
            [
              'href' => $origin . '/api/search/openapi.yaml',
              'type' => 'application/yaml',
            ],
          ],
        ],
      ],
    ];

    $response = new CacheableJsonResponse($body);
    $response->headers->set('Content-Type', 'application/linkset+json');
    $response->getCacheableMetadata()->addCacheContexts(['url.site']);
    return $response;
  }

  /**
   * Publishes the agent-discovery catalog (description + queries).
   *
   * Each project overrides the text via the `server_general.agent_discovery`
   * config; the shipped defaults are generic starter copy.
   */
  public function aiCatalog(Request $request): JsonResponse {
    $config = $this->configFactory->get('server_general.agent_discovery');
    $body = [
      'description' => (string) $config->get('description'),
      'openapi' => $request->getSchemeAndHttpHost() . '/api/search/openapi.yaml',
      'representativeQueries' => array_values($config->get('representative_queries') ?? []),
    ];

    $response = new CacheableJsonResponse($body);
    $response->getCacheableMetadata()
      ->addCacheContexts(['url.site'])
      ->addCacheableDependency($config);
    return $response;
  }

  /**
   * Executes the index query and maps result items to the JSON result shape.
   *
   * @param string $key
   *   The fulltext search term.
   * @param string $type
   *   Optional content-type machine name to filter by; '' for no filter.
   * @param int $limit
   *   Maximum number of results.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Collects cacheability from the node URLs generated per result.
   *
   * @return array<int, array{title: string, url: string, snippet: string, type: string}>
   *   The mapped results, in relevance order.
   */
  private function runSearch(string $key, string $type, int $limit, CacheableMetadata $cacheability): array {
    $index = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->load(self::INDEX_ID);
    if (!$index instanceof IndexInterface) {
      return [];
    }

    $query = $index->query(['limit' => $limit]);
    $query->keys($key);
    if ($type !== '') {
      $query->addCondition('type', $type);
    }
    $query->range(0, $limit);

    $results = [];
    foreach ($query->execute()->getResultItems() as $item) {
      try {
        $node = $item->getOriginalObject()->getValue();
      }
      catch (\Exception $e) {
        // A stale index row whose node no longer loads: skip it.
        continue;
      }
      if (!$node instanceof NodeInterface) {
        continue;
      }

      $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString(TRUE);
      $cacheability->addCacheableDependency($url);

      $results[] = [
        'title' => $node->label(),
        'url' => $url->getGeneratedUrl(),
        'snippet' => $this->snippet($node),
        'type' => $node->bundle(),
      ];
    }

    return $results;
  }

  /**
   * Builds a short, plain-text snippet from the node body.
   */
  private function snippet(NodeInterface $node): string {
    if (!$node->hasField('field_body')) {
      return '';
    }
    $body = (string) $node->get('field_body')->value;
    // Collapse the stripped markup to a single run of whitespace before
    // cutting, so the snippet never carries layout whitespace.
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');
    return Unicode::truncate($text, 200, TRUE, TRUE);
  }

}
