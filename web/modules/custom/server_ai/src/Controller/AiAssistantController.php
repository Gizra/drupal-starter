<?php

declare(strict_types=1);

namespace Drupal\server_ai\Controller;

/**
 * The admin content assistant chat (/ai-content-assistant).
 *
 * Drives the News tagging tools. Staff-only, so tool calls render with their
 * raw args/result JSON in the UI; sessions are flagged as admin.
 */
final class AiAssistantController extends AiChatControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function pageKey(): string {
    return 'admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function isAdmin(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function appUrl(): string {
    return '/ai-content-assistant';
  }

  /**
   * {@inheritdoc}
   */
  protected function configUrl(): string {
    return '/ai-content-assistant/config';
  }

  /**
   * {@inheritdoc}
   */
  protected function sessionSaveUrl(): string {
    return '/ai-content-assistant/session';
  }

  /**
   * {@inheritdoc}
   */
  protected function promptField(): string {
    return 'field_ai_prompt_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function missingConfigError(): string {
    return 'AI Content Assistant config page has not been saved yet. Visit /admin/config/system/ai-content-assistant.';
  }

  /**
   * {@inheritdoc}
   */
  protected function pageChrome(): array {
    return [
      'pageTitle' => 'AI content assistant',
      'pageSubhead' => 'Suggest tags for News content through chat. Each turn runs the MCP tools on this site.',
      'inputPlaceholder' => 'e.g. Suggest tags for the next untagged News item (Enter to send, Shift+Enter for a new line)',
    ];
  }

}
