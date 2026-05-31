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
 * Returns a single news node with text and tag context for the skill.
 */
#[Tool(
  id: 'get_news_item',
  label: new TranslatableMarkup('Get News Item'),
  description: new TranslatableMarkup('Fetches one news node with plaintext body, current curated tags, and current AI suggestion (if any).'),
  inputSchema: [
    'type' => 'object',
    'properties' => [
      'nid' => ['type' => 'integer'],
    ],
    'required' => ['nid'],
  ],
  outputSchema: [
    'type' => 'object',
    'properties' => [
      'nid' => ['type' => 'integer'],
      'title' => ['type' => 'string'],
      'body' => ['type' => 'string'],
      'current_tags' => ['type' => 'array'],
      'has_ai_suggestion' => ['type' => 'boolean'],
      'ai_suggestion_tags' => ['type' => ['array', 'null']],
    ],
    'required' => ['nid', 'title', 'body', 'current_tags', 'has_ai_suggestion'],
  ],
  readOnly: TRUE,
  destructive: FALSE,
  idempotent: TRUE,
  openWorld: FALSE,
)]
final class GetNewsItemTool extends ToolPluginBase {

  /**
   * Constructs a GetNewsItemTool object.
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
    /** @var \Drupal\node\NodeInterface|null $node */
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node instanceof NodeInterface || $node->bundle() !== 'news') {
      return [
        'success' => FALSE,
        'message' => sprintf('No news item with nid %d.', $nid),
      ];
    }

    $current_tags = $this->referencedTerms($node, 'field_tags');
    $ai_tags = $this->referencedTerms($node, 'field_tags_ai_suggestion');
    $has_ai = !empty($ai_tags);

    return [
      'success' => TRUE,
      'data' => [
        'nid' => $nid,
        'title' => (string) $node->label(),
        'body' => $this->plaintextField($node, 'field_body'),
        'current_tags' => $current_tags,
        'has_ai_suggestion' => $has_ai,
        'ai_suggestion_tags' => $has_ai ? $ai_tags : NULL,
      ],
    ];
  }

  /**
   * Returns the value of an HTML text field stripped to plaintext.
   */
  private function plaintextField(NodeInterface $node, string $field_name): string {
    if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
      return '';
    }
    $raw = (string) $node->get($field_name)->value;
    $stripped = trim(strip_tags($raw));
    return preg_replace('/\s+/u', ' ', $stripped) ?? '';
  }

  /**
   * Returns referenced taxonomy terms as a list of [tid, name] pairs.
   *
   * @return array<int, array{tid: int, name: string}>
   *   Referenced terms.
   */
  private function referencedTerms(NodeInterface $node, string $field_name): array {
    if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
      return [];
    }
    $items = [];
    foreach ($node->get($field_name)->referencedEntities() as $term) {
      $items[] = [
        'tid' => (int) $term->id(),
        'name' => (string) $term->label(),
      ];
    }
    return $items;
  }

}
