<?php

declare(strict_types=1);

namespace Drupal\server_ai\Controller;

/**
 * The visitor search chat (/ai-search).
 *
 * Runs RAG over News content. Visitor-facing, so tool calls render as a
 * friendly "searching…" pill (no raw JSON) and the results arrive as cards;
 * sessions are flagged as non-admin and run through sensitivity checks.
 *
 * Shares the single `ai_assistant` config page with the admin assistant, but
 * reads its own prompt field.
 */
final class AiSearchController extends AiChatControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function pageKey(): string {
    return 'search';
  }

  /**
   * {@inheritdoc}
   */
  protected function isAdmin(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function appUrl(): string {
    return '/ai-search';
  }

  /**
   * {@inheritdoc}
   */
  protected function configUrl(): string {
    return '/ai-search/config';
  }

  /**
   * {@inheritdoc}
   */
  protected function sessionSaveUrl(): string {
    return '/ai-search/session';
  }

  /**
   * {@inheritdoc}
   */
  protected function promptField(): string {
    return 'field_ai_prompt_search';
  }

  /**
   * {@inheritdoc}
   */
  protected function missingConfigError(): string {
    return 'AI Search prompt has not been saved yet. Visit /admin/config/system/ai-content-assistant.';
  }

  /**
   * {@inheritdoc}
   */
  protected function pageChrome(): array {
    return [
      'pageTitle' => 'AI search',
      'pageSubhead' => "Ask a question. We'll find relevant News for you.",
      'inputPlaceholder' => 'e.g. What News do we have on climate policy? (Enter to send, Shift+Enter for a new line)',
    ];
  }

}
