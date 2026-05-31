<?php

declare(strict_types=1);

namespace Drupal\server_ai\Plugin\Tool;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\mcp_server\Attribute\Tool;
use Drupal\mcp_server\Plugin\ToolPluginBase;
use Mcp\Server\ClientGateway;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lists news nodes, optionally filtered by AI-suggestion state.
 */
#[Tool(
  id: 'list_news_items',
  label: new TranslatableMarkup('List News Items'),
  description: new TranslatableMarkup('Lists news nodes. Optionally filter by whether field_tags_ai_suggestion is populated.'),
  inputSchema: [
    'type' => 'object',
    'properties' => [
      'has_ai_suggestion' => [
        'type' => ['boolean', 'null'],
        'description' => 'true = only nodes with the AI suggestion field populated; false = only those without; null/omitted = no filter.',
      ],
      'page' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
      'page_size' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10],
    ],
  ],
  outputSchema: [
    'type' => 'object',
    'properties' => [
      'items' => [
        'type' => 'array',
        'items' => [
          'type' => 'object',
          'properties' => [
            'nid' => ['type' => 'integer'],
            'title' => ['type' => 'string'],
            'langcode' => ['type' => 'string'],
          ],
          'required' => ['nid', 'title', 'langcode'],
        ],
      ],
      'total_count' => ['type' => 'integer'],
    ],
    'required' => ['items', 'total_count'],
  ],
  readOnly: TRUE,
  destructive: FALSE,
  idempotent: TRUE,
  openWorld: FALSE,
)]
final class ListNewsItemsTool extends ToolPluginBase {

  /**
   * Constructs a ListNewsItemsTool object.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    AccountProxyInterface $current_user,
    private readonly EntityTypeManagerInterface $entityTypeManager,
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
    $has_ai_suggestion = $arguments['has_ai_suggestion'] ?? NULL;
    $page = (int) ($arguments['page'] ?? 0);
    $page_size = (int) ($arguments['page_size'] ?? 10);

    $storage = $this->entityTypeManager->getStorage('node');

    $list_query = $storage->getQuery()
      ->condition('type', 'news')
      ->accessCheck(TRUE)
      ->sort('nid', 'DESC');
    $count_query = $storage->getQuery()
      ->condition('type', 'news')
      ->accessCheck(TRUE);

    if ($has_ai_suggestion) {
      $list_query->exists('field_tags_ai_suggestion');
      $count_query->exists('field_tags_ai_suggestion');
    }
    elseif ($has_ai_suggestion === FALSE) {
      $list_query->notExists('field_tags_ai_suggestion');
      $count_query->notExists('field_tags_ai_suggestion');
    }

    $list_query->range($page * $page_size, $page_size);
    $nids = $list_query->execute();
    $nodes = $nids ? $storage->loadMultiple($nids) : [];

    $items = [];
    foreach ($nodes as $node) {
      $items[] = [
        'nid' => (int) $node->id(),
        'title' => (string) $node->label(),
        'langcode' => $node->language()->getId(),
      ];
    }

    return [
      'success' => TRUE,
      'data' => [
        'items' => $items,
        'total_count' => (int) $count_query->count()->execute(),
      ],
    ];
  }

}
