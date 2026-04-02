<?php

declare(strict_types=1);

namespace Drupal\server_ai_content\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\TextToImage\TextToImageInput;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Generates landing page content using AI.
 */
class ContentGenerator {

  /**
   * Whether DALL-E image generation is enabled.
   *
   * @todo Set to TRUE to enable DALL-E image generation.
   */
  private const IMAGE_GENERATION_ENABLED = FALSE;

  /**
   * Constructs a ContentGenerator service.
   */
  public function __construct(
    protected AiProviderPluginManager $aiProvider,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ParagraphSchemaDiscovery $schemaDiscovery,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * Generate a landing page from a user prompt.
   *
   * @param string $prompt
   *   The user's text prompt.
   * @param string $contentType
   *   The node bundle to create.
   *
   * @return \Drupal\node\NodeInterface
   *   The created unpublished node.
   *
   * @throws \RuntimeException
   *   If the AI response cannot be parsed.
   */
  public function generate(string $prompt, string $contentType = 'landing_page'): NodeInterface {
    $schemaDescription = $this->schemaDiscovery->buildPromptDescription($contentType);
    $systemPrompt = $this->buildSystemPrompt($schemaDescription);

    $data = $this->callAi($systemPrompt, $prompt);

    return $this->createFromParsedData($data, $contentType);
  }

  /**
   * Create a node with paragraphs from parsed AI response data.
   *
   * @param array $data
   *   Parsed JSON data with 'title' and 'paragraphs' keys.
   * @param string $contentType
   *   The node bundle.
   *
   * @return \Drupal\node\NodeInterface
   *   The created unpublished node.
   */
  public function createFromParsedData(array $data, string $contentType): NodeInterface {
    $compoundMapping = $this->schemaDiscovery->getCompoundTypeMapping($contentType);
    $paragraphEntities = [];

    foreach ($data['paragraphs'] ?? [] as $paragraphData) {
      $paragraph = $this->createParagraph($paragraphData, $compoundMapping);
      if ($paragraph) {
        $paragraphEntities[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => $contentType,
      'title' => $data['title'] ?? 'AI Generated Page',
      'status' => 0,
      'field_paragraphs' => $paragraphEntities,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Create a single paragraph entity from data.
   *
   * @param array $paragraphData
   *   Paragraph data with 'type' and 'fields' keys.
   * @param array $compoundMapping
   *   Mapping of compound types from ParagraphSchemaDiscovery.
   *
   * @return \Drupal\paragraphs\ParagraphInterface|null
   *   The created paragraph, or NULL on failure.
   */
  protected function createParagraph(array $paragraphData, array $compoundMapping): ?ParagraphInterface {
    $type = $paragraphData['type'] ?? '';
    $fields = $paragraphData['fields'] ?? [];

    if (empty($type)) {
      return NULL;
    }

    $values = ['type' => $type];
    $compound = $compoundMapping[$type] ?? NULL;

    foreach ($fields as $fieldName => $fieldValue) {
      // Handle sub-paragraphs for compound types.
      if ($compound && $fieldName === $compound['field_name']) {
        $subParagraphs = [];
        foreach ($fieldValue as $subData) {
          $subParagraph = $this->createParagraph([
            'type' => $compound['sub_type'],
            'fields' => $subData,
          ], []);
          if ($subParagraph) {
            $subParagraphs[] = [
              'target_id' => $subParagraph->id(),
              'target_revision_id' => $subParagraph->getRevisionId(),
            ];
          }
        }
        $values[$fieldName] = $subParagraphs;
        continue;
      }

      $values[$fieldName] = $this->mapFieldValue($fieldName, $fieldValue);
    }

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $this->entityTypeManager->getStorage('paragraph')->create($values);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Map a single field value to a Drupal-compatible format.
   *
   * @param string $fieldName
   *   The field machine name.
   * @param mixed $fieldValue
   *   The raw field value from the AI response.
   *
   * @return mixed
   *   The mapped value ready for entity creation.
   */
  protected function mapFieldValue(string $fieldName, mixed $fieldValue): mixed {
    // Handle image fields — check for image_prompt key.
    if (is_array($fieldValue) && isset($fieldValue['image_prompt'])) {
      $media = $this->generateImage($fieldValue['image_prompt']);
      return $media ? ['target_id' => $media->id()] : NULL;
    }

    // Handle link fields.
    if (is_array($fieldValue) && isset($fieldValue['uri'])) {
      return $fieldValue;
    }

    // Handle text_long fields (body/description) — set value and format.
    if (is_string($fieldValue) && in_array($fieldName, ['field_body', 'field_description'], TRUE)) {
      return [
        'value' => $fieldValue,
        'format' => 'full_html',
      ];
    }

    // Simple text value.
    return $fieldValue;
  }

  /**
   * Generate an image via DALL-E and create a Media entity.
   *
   * @param string $imagePrompt
   *   Description of the image to generate.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The created media entity, or NULL on failure.
   */
  protected function generateImage(string $imagePrompt): ?MediaInterface {
    // @phpstan-ignore booleanNot.alwaysTrue
    if (!self::IMAGE_GENERATION_ENABLED) {
      return NULL;
    }
    // @phpstan-ignore deadCode.unreachable
    try {
      $default = $this->aiProvider->getDefaultProviderForOperationType('text_to_image');
      if (!$default) {
        $this->loggerFactory->get('server_ai_content')->warning('No default text_to_image provider configured.');
        return NULL;
      }

      /** @var \Drupal\ai_provider_openai\Plugin\AiProvider\OpenAiProvider $provider */
      $provider = $this->aiProvider->createInstance($default['provider_id']);
      $input = new TextToImageInput($imagePrompt);
      $response = $provider->textToImage($input, $default['model_id'], ['server_ai_content']);

      $images = $response->getNormalized();
      if (empty($images)) {
        return NULL;
      }

      $imageFile = $images[0];
      $filename = 'ai-generated-' . time() . '.png';
      $filePath = 'public://ai-generated/' . date('Y-m');
      $media = $imageFile->getAsMediaEntity('image', $filePath, $filename);

      // Set a meaningful alt text.
      $media->get('field_media_image')->alt = mb_substr($imagePrompt, 0, 512);
      $media->save();

      return $media;
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get('server_ai_content')->warning('DALL-E image generation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Call the AI provider for content generation.
   *
   * @param string $systemPrompt
   *   The system prompt with schema description.
   * @param string $userPrompt
   *   The user's content request.
   *
   * @return array
   *   Parsed JSON response with 'title' and 'paragraphs' keys.
   *
   * @throws \RuntimeException
   *   If the response cannot be parsed or is invalid.
   */
  protected function callAi(string $systemPrompt, string $userPrompt): array {
    $default = $this->aiProvider->getDefaultProviderForOperationType('chat');
    if (!$default) {
      throw new \RuntimeException('No default chat provider configured.');
    }

    $provider = $this->aiProvider->createInstance($default['provider_id']);

    $input = new ChatInput([
      new ChatMessage('user', $userPrompt),
    ]);
    $input->setSystemPrompt($systemPrompt);

    // @phpstan-ignore method.notFound
    $response = $provider->chat($input, $default['model_id'], ['server_ai_content']);
    $text = $response->getNormalized()->getText();

    // Strip markdown code fences if present.
    $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
    $text = preg_replace('/\s*```$/m', '', $text);
    $text = trim($text);

    $data = json_decode($text, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \RuntimeException('Failed to parse AI response as JSON: ' . json_last_error_msg());
    }

    if (empty($data['title']) || empty($data['paragraphs'])) {
      throw new \RuntimeException('AI response missing required "title" or "paragraphs" keys.');
    }

    return $data;
  }

  /**
   * Build the system prompt for the AI.
   *
   * @param string $schemaDescription
   *   Human-readable paragraph schema description.
   *
   * @return string
   *   The complete system prompt.
   */
  protected function buildSystemPrompt(string $schemaDescription): string {
    return <<<PROMPT
You are a Drupal content generator. Given a user's request, generate a landing page with appropriate paragraphs.

Available paragraph types and their fields:

{$schemaDescription}

Rules:
- Return valid JSON only, no markdown code fences
- Only use paragraph types that genuinely fit the user's request. Do NOT use all available types — pick the ones that make sense for the topic. A page about a single topic may only need 2-3 paragraph types.
- Generate rich, detailed content — body fields should contain multiple paragraphs with substantial information (at least 3-5 sentences per body field). Accordion items should have thorough answers, not single sentences.
- For image fields, provide an object with an "image_prompt" key containing a detailed description for DALL-E image generation
- For link fields, use "uri": "route:<nolink>" with a descriptive title as a placeholder. Do not invent URLs to pages that do not exist.
- For text_long (body) fields, use basic HTML tags (p, ul, li, strong, em)
- For sub-paragraph arrays (like field_accordion_items), provide an array of objects with the sub-paragraph field values

Response format:
{
  "title": "Page Title",
  "paragraphs": [
    {
      "type": "paragraph_type_machine_name",
      "fields": {
        "field_name": "value"
      }
    }
  ]
}
PROMPT;
  }

}
