<?php

declare(strict_types=1);

namespace Drupal\server_ai;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Persists AI assistant chat turns as `session` nodes and flags violations.
 */
final class SessionWriter {

  private const CONFIG_PAGES_TYPE = 'ai_assistant';
  private const TITLE_MAX = 80;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigPagesLoaderServiceInterface $configPagesLoader,
    private readonly OpenAiClientInterface $openAiClient,
  ) {}

  /**
   * Saves one completed turn (question + answer) onto a session.
   *
   * @param int|null $nid
   *   Existing session node id, or NULL to create a new session.
   * @param int $uid
   *   The owner user id.
   * @param string $question
   *   The user's question.
   * @param string $answer
   *   The assistant's final answer (markdown).
   * @param string $responseId
   *   OpenAI's previous_response_id for the next turn.
   * @param bool $isAdmin
   *   TRUE for the content-improvement assistant (/ai-content-assistant),
   *   FALSE for the visitor search assistant (/ai-search). Set on creation.
   * @param array<int, int> $resourceNids
   *   Resource_item node ids the assistant surfaced for this answer (the search
   *   result cards), stored on the answer paragraph so they can be re-rendered
   *   when the session is reloaded.
   *
   * @return \Drupal\node\NodeInterface
   *   The saved session node.
   */
  public function saveTurn(?int $nid, int $uid, string $question, string $answer, string $responseId, bool $isAdmin = TRUE, array $resourceNids = []): NodeInterface {
    $node = $nid !== NULL ? $this->loadOwned($nid, $uid) : NULL;

    if (!$node) {
      $node = $this->entityTypeManager->getStorage('node')->create([
        'type' => 'ai_chat_session',
        'uid' => $uid,
        'title' => $this->buildTitle($question),
        'field_session_is_admin' => $isAdmin,
      ]);
    }

    $question_paragraph = $this->createParagraph('ai_user_question', 'field_chat_question', $question);
    $answer_paragraph = $this->createParagraph('ai_assistant_response', 'field_chat_answer', $answer);

    if ($resourceNids !== []) {
      $answer_paragraph->set('field_chat_resources', array_values($resourceNids));
      $answer_paragraph->save();
    }

    // Screen the question only while the session isn't flagged yet: once a
    // session has been flagged we stop running (and paying for) the classifier
    // for the rest of the conversation.
    if (!(bool) $node->get('field_session_flagged')->value) {
      $this->applySensitivity($node, $question, $question_paragraph);
    }

    $node->get('field_session_rows')->appendItem($question_paragraph);
    $node->get('field_session_rows')->appendItem($answer_paragraph);
    $node->set('field_session_response_id', $responseId);

    $node->save();
    return $node;
  }

  /**
   * Loads a session node only if it is owned by the given user.
   */
  private function loadOwned(int $nid, int $uid): ?NodeInterface {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if ($node instanceof NodeInterface && $node->bundle() === 'ai_chat_session' && (int) $node->getOwnerId() === $uid) {
      return $node;
    }
    return NULL;
  }

  /**
   * Builds (and saves) a paragraph of the given bundle with one text field.
   */
  private function createParagraph(string $bundle, string $field, string $value): ParagraphInterface {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
      'type' => $bundle,
      $field => $value,
    ]);
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Runs the sensitivity check on the question and flags the node if needed.
   *
   * Flags both the session (reason + policy snapshot + offending row) and the
   * offending question paragraph itself (field_question_flagged), so the
   * specific message that tripped the policy is marked.
   */
  private function applySensitivity(NodeInterface $node, string $question, ParagraphInterface $question_paragraph): void {
    $policy = $this->policy();
    $verdict = $this->openAiClient->checkSensitivity($question, $policy);
    if (!$verdict->flagged) {
      return;
    }
    $question_paragraph->set('field_question_flagged', TRUE);
    $question_paragraph->save();

    $node->set('field_session_flagged', TRUE);
    $node->set('field_session_flag_reason', $verdict->reason);
    $node->set('field_session_policy_snapshot', $policy);
    $node->set('field_session_flagged_row', $question_paragraph);
  }

  /**
   * The current sensitivity policy markdown, or an empty string.
   */
  private function policy(): string {
    $config = $this->configPagesLoader->load(self::CONFIG_PAGES_TYPE);
    return $config ? trim((string) ($config->get('field_ai_sensitivity_policy')->value ?? '')) : '';
  }

  /**
   * An AI-generated title, falling back to the truncated question.
   */
  private function buildTitle(string $question): string {
    $title = '';
    try {
      $title = $this->openAiClient->generateTitle($question);
    }
    catch (\Throwable) {
      // Fall back below.
    }
    if ($title === '') {
      $title = $question;
    }
    return mb_substr(trim($title), 0, self::TITLE_MAX);
  }

}
