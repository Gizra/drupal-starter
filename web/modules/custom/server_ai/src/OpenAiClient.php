<?php

declare(strict_types=1);

namespace Drupal\server_ai;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Talks to the OpenAI Responses API for title + sensitivity classification.
 *
 * Uses the same `server_ai_openai_token` Key the browser app uses.
 */
final class OpenAiClient implements OpenAiClientInterface {

  private const RESPONSES_URL = 'https://api.openai.com/v1/responses';
  private const CONFIG_PAGES_TYPE = 'ai_assistant';
  private const KEY_OPENAI_TOKEN = 'server_ai_openai_token';
  private const DEFAULT_MODEL = 'gpt-4o';

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly KeyRepositoryInterface $keyRepository,
    private readonly ConfigPagesLoaderServiceInterface $configPagesLoader,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function generateTitle(string $question): string {
    $text = $this->respond(
      'Generate a short, descriptive title (max 6 words) for a chat that begins with the user message. Reply with the title only — no quotes, no punctuation at the end.',
      $question,
    );
    return trim($text);
  }

  /**
   * {@inheritdoc}
   */
  public function checkSensitivity(string $question, string $policy): SensitivityVerdict {
    if (trim($policy) === '') {
      return new SensitivityVerdict(FALSE, '');
    }

    $instructions = <<<TXT
You are a strict content-safety classifier. Decide whether the USER MESSAGE
violates the POLICY. Respond with ONLY a JSON object of the form
{"flagged": true|false, "reason": "<short explanation>"} and nothing else.
TXT;
    $input = "POLICY:\n" . $policy . "\n\nUSER MESSAGE:\n" . $question;

    $text = $this->respond($instructions, $input);
    $data = $this->decodeJsonObject($text);

    return new SensitivityVerdict(
      (bool) ($data['flagged'] ?? FALSE),
      (string) ($data['reason'] ?? ''),
    );
  }

  /**
   * Makes one non-streaming Responses API call and returns the output text.
   *
   * @param string $instructions
   *   The system instructions for the model.
   * @param string $input
   *   The user input for the model.
   *
   * @return string
   *   The output text from the model.
   */
  private function respond(string $instructions, string $input): string {
    $token = $this->openAiToken();
    if ($token === '') {
      throw new \RuntimeException('OpenAI token is not configured.');
    }

    $response = $this->httpClient->request('POST', self::RESPONSES_URL, [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
      ],
      'json' => [
        'model' => $this->model(),
        'instructions' => $instructions,
        'input' => $input,
        'stream' => FALSE,
      ],
      'http_errors' => TRUE,
    ]);

    return $this->extractOutputText((string) $response->getBody());
  }

  /**
   * Pulls the first output_text out of a Responses API JSON body.
   *
   * @param string $body
   *   The raw JSON response body.
   *
   * @return string
   *   The extracted output text, or an empty string when none is found.
   */
  private function extractOutputText(string $body): string {
    $data = json_decode($body, TRUE);
    if (!is_array($data)) {
      return '';
    }
    foreach ($data['output'] ?? [] as $item) {
      foreach ($item['content'] ?? [] as $part) {
        if (($part['type'] ?? '') === 'output_text' && isset($part['text'])) {
          return (string) $part['text'];
        }
      }
    }
    return '';
  }

  /**
   * Extracts a JSON object from model text (tolerates surrounding prose).
   *
   * @param string $text
   *   The model output text.
   *
   * @return array<string, mixed>
   *   The decoded object, or an empty array if none was found.
   */
  private function decodeJsonObject(string $text): array {
    $decoded = json_decode(trim($text), TRUE);
    if (is_array($decoded)) {
      return $decoded;
    }
    if (preg_match('/\{.*\}/s', $text, $m)) {
      $decoded = json_decode($m[0], TRUE);
      if (is_array($decoded)) {
        return $decoded;
      }
    }
    return [];
  }

  /**
   * The OpenAI bearer token from the Key module.
   *
   * @return string
   *   The token, or an empty string when not configured.
   */
  private function openAiToken(): string {
    $key = $this->keyRepository->getKey(self::KEY_OPENAI_TOKEN);
    return $key ? trim((string) $key->getKeyValue()) : '';
  }

  /**
   * The configured OpenAI model, or the default.
   *
   * @return string
   *   The model machine name.
   */
  private function model(): string {
    $config = $this->configPagesLoader->load(self::CONFIG_PAGES_TYPE);
    $model = $config ? trim((string) ($config->get('field_ai_openai_model')->value ?? '')) : '';
    return $model !== '' ? $model : self::DEFAULT_MODEL;
  }

}
