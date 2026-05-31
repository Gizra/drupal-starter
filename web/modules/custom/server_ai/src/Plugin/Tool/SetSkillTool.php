<?php

declare(strict_types=1);

namespace Drupal\server_ai\Plugin\Tool;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\mcp_server\Attribute\Tool;
use Drupal\mcp_server\Plugin\ToolPluginBase;
use Mcp\Server\ClientGateway;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overwrites the AI assistant skill (the admin system prompt).
 *
 * Backs the chat self-edit flow: when the operator approves a skill change,
 * the assistant calls this with the full revised skill text. The new prompt
 * takes effect on the next turn/session (it is reloaded from the config page).
 * Call ONLY on explicit operator approval — this rewrites the assistant's own
 * instructions.
 */
#[Tool(
  id: 'set_assistant_skill',
  label: new TranslatableMarkup('Set AI Assistant Skill'),
  description: new TranslatableMarkup('Overwrites the AI assistant skill (the system prompt on the ai_assistant config page) with the provided full text. Call only on explicit operator approval; it replaces the entire skill.'),
  inputSchema: [
    'type' => 'object',
    'properties' => [
      'skill' => [
        'type' => 'string',
        'description' => 'The full new skill / system prompt text. Replaces the existing value entirely.',
      ],
    ],
    'required' => ['skill'],
  ],
  outputSchema: [
    'type' => 'object',
    'properties' => [
      'length' => ['type' => 'integer'],
    ],
    'required' => ['length'],
  ],
  readOnly: FALSE,
  destructive: TRUE,
  idempotent: FALSE,
  openWorld: FALSE,
)]
final class SetSkillTool extends ToolPluginBase {

  /**
   * The config_pages type holding the skill.
   */
  private const CONFIG_PAGES_TYPE = 'ai_assistant';

  /**
   * The field holding the admin system prompt.
   */
  private const FIELD = 'field_ai_prompt_admin';

  /**
   * Constructs a SetSkillTool object.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    AccountProxyInterface $currentUser,
    private readonly ConfigPagesLoaderServiceInterface $configPagesLoader,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $currentUser);
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
      $container->get('config_pages.loader'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $arguments, ClientGateway $gateway): mixed {
    $skill = (string) ($arguments['skill'] ?? '');
    if (trim($skill) === '') {
      return [
        'success' => FALSE,
        'message' => (string) $this->t('Refusing to write an empty skill.'),
      ];
    }

    $config = $this->configPagesLoader->load(self::CONFIG_PAGES_TYPE);
    if (!$config) {
      return [
        'success' => FALSE,
        'message' => (string) $this->t('AI assistant config page has not been saved yet.'),
      ];
    }

    $config->set(self::FIELD, $skill);
    $config->save();

    return [
      'success' => TRUE,
      'data' => [
        'length' => strlen($skill),
      ],
    ];
  }

}
