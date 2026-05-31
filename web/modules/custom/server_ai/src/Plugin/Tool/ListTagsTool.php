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
 * Lists every tags term with plaintext descriptions.
 */
#[Tool(
  id: 'list_tags',
  label: new TranslatableMarkup('List Tags Terms'),
  description: new TranslatableMarkup('Returns every term in the tags vocabulary so the skill can pick from existing terms only.'),
  inputSchema: [
    'type' => 'object',
    'properties' => new \stdClass(),
  ],
  outputSchema: [
    'type' => 'object',
    'properties' => [
      'data' => [
        'type' => 'array',
        'items' => [
          'type' => 'object',
          'properties' => [
            'tid' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'description' => ['type' => 'string'],
          ],
          'required' => ['tid', 'name', 'description'],
        ],
      ],
    ],
    'required' => ['data'],
  ],
  readOnly: TRUE,
  destructive: FALSE,
  idempotent: TRUE,
  openWorld: FALSE,
)]
final class ListTagsTool extends ToolPluginBase {

  /**
   * Constructs a ListTagsTool object.
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
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $tids = $storage->getQuery()
      ->condition('vid', 'tags')
      ->accessCheck(TRUE)
      ->sort('name', 'ASC')
      ->execute();
    $terms = $tids ? $storage->loadMultiple($tids) : [];

    $rows = [];
    foreach ($terms as $term) {
      $description_raw = (string) $term->getDescription();
      $stripped = trim(strip_tags($description_raw));
      $rows[] = [
        'tid' => (int) $term->id(),
        'name' => (string) $term->label(),
        'description' => preg_replace('/\s+/u', ' ', $stripped) ?? '',
      ];
    }

    return [
      'success' => TRUE,
      'data' => $rows,
    ];
  }

}
