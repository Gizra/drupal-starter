<?php

declare(strict_types=1);

namespace Drupal\server_ai\Controller;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\server_ai\CardBuilder;
use Drupal\server_ai\SessionWriter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shared behaviour for the two AI chat pages.
 *
 * Both the admin content assistant (/ai-content-assistant) and the visitor
 * search (/ai-search) serve the same app bundle and persist + list chat
 * sessions identically. The only differences are page-specific values, exposed
 * through the abstract hooks below.
 *
 * The config endpoint ships no secrets to the browser. The OpenAI token and the
 * MCP token are handled by an external proxy, whose URL is exposed via the
 * non-secret `proxyUrl` value. Both pages read their prompt from the single
 * `ai_assistant` config page (different fields).
 */
abstract class AiChatControllerBase extends ControllerBase {

  /**
   * The config_pages type that stores the prompts + shared settings.
   */
  private const CONFIG_PAGES_TYPE = 'ai_assistant';

  /**
   * Constructs the controller.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   The config pages loader service.
   * @param \Drupal\server_ai\SessionWriter $sessionWriter
   *   The chat session writer.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\server_ai\CardBuilder $cardBuilder
   *   The resource card builder.
   */
  public function __construct(
    private readonly ConfigPagesLoaderServiceInterface $configPagesLoader,
    private readonly SessionWriter $sessionWriter,
    private readonly AccountInterface $account,
    private readonly CardBuilder $cardBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config_pages.loader'),
      $container->get('server_ai.session_writer'),
      $container->get('current_user'),
      $container->get('server_ai.card_builder'),
    );
  }

  /**
   * Page key passed to the app as the `page` flag ('admin' or 'search').
   */
  abstract protected function pageKey(): string;

  /**
   * TRUE for the admin assistant, FALSE for visitor search.
   *
   * Drives both the session sidebar filter and the flag stored on new sessions.
   */
  abstract protected function isAdmin(): bool;

  /**
   * The base path of this chat page (e.g. '/ai-content-assistant').
   */
  abstract protected function appUrl(): string;

  /**
   * The config endpoint path (e.g. '/ai-content-assistant/config').
   */
  abstract protected function configUrl(): string;

  /**
   * The session-save endpoint path for this page.
   */
  abstract protected function sessionSaveUrl(): string;

  /**
   * The config_pages field holding this page's system prompt.
   */
  abstract protected function promptField(): string;

  /**
   * Message shown when the config page has not been saved yet.
   */
  abstract protected function missingConfigError(): string;

  /**
   * Page chrome passed to the app: pageTitle, pageSubhead, inputPlaceholder.
   *
   * @return array{pageTitle: string, pageSubhead: string, inputPlaceholder: string}
   *   The page-specific UI copy.
   */
  abstract protected function pageChrome(): array;

  /**
   * Renders the host page; the app boots into the container div.
   *
   * When `?session=ID` names a session owned by the current user, its
   * transcript + response id are passed to the app so the conversation resumes.
   * The current user's recent sessions for this page are passed to the app,
   * which renders them as a sidebar.
   */
  public function app(Request $request): array {
    $settings = [
      'page' => $this->pageKey(),
      'appUrl' => $this->appUrl(),
      'sessionSaveUrl' => $this->sessionSaveUrl(),
      'sessions' => $this->recentSessions(),
    ];

    $session_id = $request->query->get('session');
    if (is_numeric($session_id)) {
      $hydrated = $this->hydrateSession((int) $session_id);
      if ($hydrated !== NULL) {
        $settings['session'] = $hydrated;
      }
    }

    return [
      '#markup' => '<div data-server-ai-app data-config-url="' . $this->configUrl() . '"></div>',
      '#attached' => [
        'library' => ['server_ai/app'],
        'drupalSettings' => [
          'serverAi' => $settings,
        ],
      ],
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Returns the non-secret prompt + runtime settings the app needs, as JSON.
   *
   * No secrets are returned: the OpenAI token and the MCP token are handled by
   * an external proxy, whose URL is exposed here as the non-secret `proxyUrl`.
   */
  public function appConfig(): JsonResponse {
    $config = $this->configPagesLoader->load(self::CONFIG_PAGES_TYPE);
    if (!$config) {
      return new JsonResponse([
        'error' => $this->missingConfigError(),
      ], 500);
    }

    $get = static fn (string $field): string => trim((string) ($config->get($field)->value ?? ''));

    return new JsonResponse($this->pageChrome() + [
      'systemPrompt' => $get($this->promptField()),
      'openaiModel' => $get('field_ai_openai_model') ?: 'gpt-4o',
      'mcpUrl' => $get('field_ai_mcp_url'),
      'proxyUrl' => $get('field_ai_proxy_url'),
    ]);
  }

  /**
   * Persists one completed chat turn and returns the session node id.
   *
   * Expects a JSON body: {nid?: int, question: string, answer: string,
   * responseId: string, resources?: int[]}. Used by the app's auto-save.
   * `resources` are the surfaced News nids. The admin/visitor flag is set
   * server-side from the route, never trusted from the payload.
   */
  public function session(Request $request): JsonResponse {
    $payload = json_decode((string) $request->getContent(), TRUE);
    if (!is_array($payload)) {
      return new JsonResponse(['error' => 'Invalid JSON body.'], 400);
    }

    $question = trim((string) ($payload['question'] ?? ''));
    $answer = trim((string) ($payload['answer'] ?? ''));
    if ($question === '' || $answer === '') {
      return new JsonResponse(['error' => 'question and answer are required.'], 400);
    }

    $nid = isset($payload['nid']) && is_numeric($payload['nid']) ? (int) $payload['nid'] : NULL;
    $response_id = trim((string) ($payload['responseId'] ?? ''));

    $resource_nids = [];
    if (isset($payload['resources']) && is_array($payload['resources'])) {
      foreach ($payload['resources'] as $resource_nid) {
        if (is_numeric($resource_nid)) {
          $resource_nids[] = (int) $resource_nid;
        }
      }
    }

    $node = $this->sessionWriter->saveTurn(
      $nid,
      (int) $this->account->id(),
      $question,
      $answer,
      $response_id,
      $this->isAdmin(),
      $resource_nids,
    );

    return new JsonResponse([
      'nid' => (int) $node->id(),
      'title' => (string) $node->label(),
    ]);
  }

  /**
   * The current user's most recent chat sessions for this page (max 50).
   *
   * @return array<int, array{nid: int, title: string}>
   *   A list of session summaries for the sidebar.
   */
  private function recentSessions(): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'ai_chat_session')
      ->condition('uid', (int) $this->account->id())
      ->condition('field_session_is_admin', $this->isAdmin())
      ->sort('created', 'DESC')
      ->range(0, 50)
      ->execute();

    $sessions = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      $sessions[] = [
        'nid' => (int) $node->id(),
        'title' => (string) $node->label(),
      ];
    }
    return $sessions;
  }

  /**
   * Builds the app hydration payload for an owned session, or NULL.
   *
   * @return array{nid: int, responseId: string, messages: array<int, array{role: string, text: string, resources: array<int, array<string, mixed>>}>}|null
   *   The payload, or NULL if the session is missing or not owned.
   */
  private function hydrateSession(int $nid): ?array {
    $node = $this->entityTypeManager()->getStorage('node')->load($nid);
    if (!$node instanceof NodeInterface || $node->bundle() !== 'ai_chat_session') {
      return NULL;
    }
    if ((int) $node->getOwnerId() !== (int) $this->account->id()) {
      return NULL;
    }

    $messages = [];
    foreach ($node->get('field_session_rows')->referencedEntities() as $paragraph) {
      if ($paragraph->bundle() === 'ai_user_question') {
        $messages[] = [
          'role' => 'user',
          'text' => (string) $paragraph->get('field_chat_question')->value,
          'resources' => [],
        ];
      }
      elseif ($paragraph->bundle() === 'ai_assistant_response') {
        $messages[] = [
          'role' => 'assistant',
          'text' => (string) $paragraph->get('field_chat_answer')->value,
          'resources' => $this->resourceCards($paragraph),
        ];
      }
    }

    return [
      'nid' => (int) $node->id(),
      'responseId' => (string) $node->get('field_session_response_id')->value,
      'messages' => $messages,
    ];
  }

  /**
   * Builds the resource cards saved on one answer paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   An ai_assistant_response paragraph.
   *
   * @return array<int, array{nid: int, uuid: string, title: string, snippet: string, score: float, imageUrl: string}>
   *   Cards for the referenced News nodes the visitor can still view.
   */
  private function resourceCards(ParagraphInterface $paragraph): array {
    if (!$paragraph->hasField('field_chat_resources')) {
      return [];
    }
    $cards = [];
    foreach ($paragraph->get('field_chat_resources')->referencedEntities() as $resource) {
      if ($resource instanceof NodeInterface && $resource->access('view')) {
        $cards[] = $this->cardBuilder->build($resource);
      }
    }
    return $cards;
  }

}
