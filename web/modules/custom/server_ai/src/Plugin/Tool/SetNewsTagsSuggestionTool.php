<?php

declare(strict_types=1);

namespace Drupal\server_ai\Plugin\Tool;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\mcp_server\Attribute\Tool;
use Drupal\mcp_server\Plugin\ToolPluginBase;
use Drupal\node\NodeInterface;
use Mcp\Server\ClientGateway;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Writes AI-suggested tags to field_tags_ai_suggestion on a news node.
 */
#[Tool(
  id: 'set_news_tags_suggestion',
  label: new TranslatableMarkup('Set News Tags AI Suggestion'),
  description: new TranslatableMarkup('Writes term_ids to field_tags_ai_suggestion on a news node. Touches no other field.'),
  inputSchema: [
    'type' => 'object',
    'properties' => [
      'nid' => ['type' => 'integer'],
      'term_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
      'replace' => ['type' => 'boolean', 'default' => TRUE],
    ],
    'required' => ['nid', 'term_ids'],
  ],
  outputSchema: [
    'type' => 'object',
    'properties' => [
      'nid' => ['type' => 'integer'],
      'written' => ['type' => 'array'],
      'revision_id' => ['type' => 'integer'],
    ],
  ],
  readOnly: FALSE,
  destructive: FALSE,
  idempotent: FALSE,
  openWorld: FALSE,
)]
final class SetNewsTagsSuggestionTool extends ToolPluginBase {

  /**
   * Constructs a SetNewsTagsSuggestionTool object.
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
    $nid = (int) $arguments['nid'];
    $term_ids = array_values(array_unique(array_map('intval', $arguments['term_ids'] ?? [])));
    $replace = (bool) ($arguments['replace'] ?? TRUE);

    $node_storage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\node\NodeInterface|null $node */
    $node = $node_storage->load($nid);
    if (!$node instanceof NodeInterface || $node->bundle() !== 'news') {
      return [
        'success' => FALSE,
        'message' => sprintf('No news node with nid %d.', $nid),
      ];
    }

    if (!empty($term_ids)) {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $valid_ids = $term_storage->getQuery()
        ->condition('tid', $term_ids, 'IN')
        ->condition('vid', 'tags')
        ->accessCheck(FALSE)
        ->execute();
      $invalid = array_diff($term_ids, array_map('intval', $valid_ids));
      if (!empty($invalid)) {
        return [
          'success' => FALSE,
          'message' => sprintf('Term ids not in tags: %s', implode(', ', $invalid)),
        ];
      }
    }

    $existing = $replace
      ? []
      : array_map(
        static fn ($i) => (int) $i->target_id,
        iterator_to_array($node->get('field_tags_ai_suggestion')),
      );
    $final_ids = array_values(array_unique(array_merge($existing, $term_ids)));

    $node->set('field_tags_ai_suggestion', array_map(
      static fn (int $tid) => ['target_id' => $tid],
      $final_ids,
    ));
    $node->setNewRevision(TRUE);
    $node->save();

    $written = [];
    foreach ($node->get('field_tags_ai_suggestion')->referencedEntities() as $term) {
      $written[] = ['tid' => (int) $term->id(), 'name' => (string) $term->label()];
    }

    return [
      'success' => TRUE,
      'data' => [
        'nid' => $nid,
        'written' => $written,
        'revision_id' => (int) $node->getRevisionId(),
      ],
    ];
  }

}
