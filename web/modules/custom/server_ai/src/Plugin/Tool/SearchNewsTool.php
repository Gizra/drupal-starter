<?php

declare(strict_types=1);

namespace Drupal\server_ai\Plugin\Tool;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\mcp_server\Attribute\Tool;
use Drupal\mcp_server\Plugin\ToolPluginBase;
use Drupal\search_api\Item\ItemInterface;
use Drupal\server_ai\CardBuilder;
use Mcp\Server\ClientGateway;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Semantic search over news nodes via the rag_news index.
 */
#[Tool(
  id: 'search_news',
  label: new TranslatableMarkup('Semantic search of news items'),
  description: new TranslatableMarkup('Find published news nodes whose embedded content best matches the given natural-language query. Returns ranked matches with nid, uuid, title, a short snippet, and a similarity score. Use this to find background material to answer user questions about news.'),
  inputSchema: [
    'type' => 'object',
    'properties' => [
      'query' => [
        'type' => 'string',
        'description' => 'Natural-language search query. May be a question or a topic description.',
      ],
      'limit' => [
        'type' => 'integer',
        'description' => 'Max number of matches to return. Defaults to 5, capped at 20.',
        'default' => 5,
        'minimum' => 1,
        'maximum' => 20,
      ],
    ],
    'required' => ['query'],
  ],
  outputSchema: [
    'type' => 'object',
    'properties' => [
      'matches' => [
        'type' => 'array',
        'items' => [
          'type' => 'object',
          'properties' => [
            'nid' => ['type' => 'integer'],
            'uuid' => ['type' => 'string'],
            'title' => ['type' => 'string'],
            'snippet' => ['type' => 'string'],
            'score' => ['type' => 'number'],
          ],
          'required' => ['nid', 'uuid', 'title', 'snippet'],
        ],
      ],
      'total' => ['type' => 'integer'],
    ],
    'required' => ['matches', 'total'],
  ],
  readOnly: TRUE,
  destructive: FALSE,
  idempotent: TRUE,
  openWorld: FALSE,
)]
final class SearchNewsTool extends ToolPluginBase {

  /**
   * The search_api index holding the embedded news content.
   */
  private const INDEX_ID = 'rag_news';

  /**
   * The maximum length of a returned snippet.
   */
  private const SNIPPET_MAX_LEN = 300;

  /**
   * Constructs a SearchNewsTool object.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    AccountProxyInterface $current_user,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CardBuilder $cardBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $current_user);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('server_ai.card_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultConfiguration(): array {
    return ['enabled' => TRUE];
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $arguments, ClientGateway $gateway): mixed {
    $query_text = trim((string) ($arguments['query'] ?? ''));
    if (empty($query_text)) {
      return ['success' => FALSE, 'error' => 'query is required'];
    }
    $limit = (int) ($arguments['limit'] ?? 5);
    $limit = max(1, min(20, $limit));

    $index = $this->entityTypeManager->getStorage('search_api_index')->load(self::INDEX_ID);
    if (!$index) {
      return [
        'success' => FALSE,
        'error' => 'Search API index ' . self::INDEX_ID . ' is not configured.',
      ];
    }

    $query = $index->query();
    $query->keys($query_text);
    $query->range(0, $limit);
    $results = $query->execute();

    $node_storage = $this->entityTypeManager->getStorage('node');
    $matches = [];

    foreach ($results->getResultItems() as $item) {
      // Item id is "entity:node/<nid>:<lang>"; pull the nid out and load fresh
      // from storage. getOriginalObject(FALSE) returns null when the entity
      // wasn't preloaded (ai_search's addResultItem doesn't preload), which
      // would drop every match.
      $id = $item->getId();
      if (!preg_match('#^entity:node/(\d+):#', $id, $m)) {
        continue;
      }
      $node = $node_storage->load((int) $m[1]);
      if (!$node || $node->bundle() !== 'news' || !$node->access('view')) {
        continue;
      }
      $matches[] = $this->cardBuilder->build($node, $this->extractSnippet($item), (float) $item->getScore());
    }

    return [
      'success' => TRUE,
      'data' => [
        'matches' => $matches,
        'total' => count($matches),
      ],
    ];
  }

  /**
   * Pulls a short text snippet from the matched chunk's metadata.
   *
   * The ai_search backend stores the embedded chunk text under the `content`
   * key in Pinecone metadata, and surfaces it on the Search API result item
   * as extra data (not as an index field).
   */
  private function extractSnippet(ItemInterface $item): string {
    $raw = (string) ($item->getExtraData('content') ?? '');
    $raw = trim($raw);
    if (empty($raw)) {
      return '';
    }
    if (mb_strlen($raw) <= self::SNIPPET_MAX_LEN) {
      return $raw;
    }
    return rtrim(mb_substr($raw, 0, self::SNIPPET_MAX_LEN), " \t\n\r\0\x0B.,;:") . '…';
  }

}
