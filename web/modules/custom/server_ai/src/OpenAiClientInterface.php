<?php

declare(strict_types=1);

namespace Drupal\server_ai;

/**
 * Server-side OpenAI calls for the AI content assistant.
 */
interface OpenAiClientInterface {

  /**
   * Generates a short chat title from the first user question.
   *
   * @param string $question
   *   The first user question.
   *
   * @return string
   *   A short title (already trimmed; caller may truncate further).
   */
  public function generateTitle(string $question): string;

  /**
   * Classifies a user question against the sensitivity policy.
   *
   * @param string $question
   *   The user question.
   * @param string $policy
   *   The sensitivity policy markdown.
   *
   * @return \Drupal\server_ai\SensitivityVerdict
   *   The verdict. An empty policy always yields not-flagged.
   */
  public function checkSensitivity(string $question, string $policy): SensitivityVerdict;

}
