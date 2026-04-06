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
   * @param string $content_type
   *   The node bundle to create.
   *
   * @return \Drupal\node\NodeInterface
   *   The created unpublished node.
   *
   * @throws \RuntimeException
   *   If the AI response cannot be parsed.
   */
  public function generate(string $prompt, string $content_type = 'landing_page'): NodeInterface {
    $schema_description = $this->schemaDiscovery->buildPromptDescription($content_type);
    $system_prompt = $this->buildSystemPrompt($schema_description);

    $data = $this->callAi($system_prompt, $prompt);

    return $this->createFromParsedData($data, $content_type);
  }

  /**
   * Create a node with paragraphs from parsed AI response data.
   *
   * @param array $data
   *   Parsed JSON data with 'title' and 'paragraphs' keys.
   * @param string $content_type
   *   The node bundle.
   *
   * @return \Drupal\node\NodeInterface
   *   The created unpublished node.
   */
  public function createFromParsedData(array $data, string $content_type): NodeInterface {
    $compound_mapping = $this->schemaDiscovery->getCompoundTypeMapping($content_type);
    $paragraph_entities = [];

    foreach ($data['paragraphs'] ?? [] as $paragraph_data) {
      $paragraph = $this->createParagraph($paragraph_data, $compound_mapping);
      if ($paragraph) {
        $paragraph_entities[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => $content_type,
      'title' => $data['title'],
      'status' => 0,
      'field_paragraphs' => $paragraph_entities,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Create a single paragraph entity from data.
   *
   * @param array $paragraph_data
   *   Paragraph data with 'type' and 'fields' keys.
   * @param array $compound_mapping
   *   Mapping of compound types from ParagraphSchemaDiscovery.
   *
   * @return \Drupal\paragraphs\ParagraphInterface|null
   *   The created paragraph, or NULL on failure.
   */
  protected function createParagraph(array $paragraph_data, array $compound_mapping): ?ParagraphInterface {
    $type = $paragraph_data['type'] ?? '';
    $fields = $paragraph_data['fields'] ?? [];

    if (empty($type)) {
      return NULL;
    }

    $values = ['type' => $type];
    $compound = $compound_mapping[$type] ?? NULL;

    foreach ($fields as $field_name => $field_value) {
      // Handle sub-paragraphs for compound types.
      if ($compound && $field_name === $compound['field_name']) {
        $sub_paragraphs = [];
        foreach ($field_value as $sub_data) {
          $sub_paragraph = $this->createParagraph([
            'type' => $compound['sub_type'],
            'fields' => $sub_data,
          ], []);
          if ($sub_paragraph) {
            $sub_paragraphs[] = [
              'target_id' => $sub_paragraph->id(),
              'target_revision_id' => $sub_paragraph->getRevisionId(),
            ];
          }
        }
        $values[$field_name] = $sub_paragraphs;
        continue;
      }

      $values[$field_name] = $this->mapFieldValue($type, $field_name, $field_value);
    }

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $this->entityTypeManager->getStorage('paragraph')->create($values);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Map a single field value to a Drupal-compatible format.
   *
   * @param string $paragraph_type
   *   The paragraph type machine name.
   * @param string $field_name
   *   The field machine name.
   * @param mixed $field_value
   *   The raw field value from the AI response.
   *
   * @return mixed
   *   The mapped value ready for entity creation.
   */
  protected function mapFieldValue(string $paragraph_type, string $field_name, mixed $field_value): mixed {
    // Handle image fields — check for image_prompt key.
    if (is_array($field_value) && isset($field_value['image_prompt'])) {
      $media = $this->generateImage($field_value['image_prompt']);
      return $media ? ['target_id' => $media->id()] : NULL;
    }

    // Handle entity reference fields — AI provides {"target_id": 123}.
    if (is_array($field_value) && isset($field_value['target_id'])) {
      return ['target_id' => (int) $field_value['target_id']];
    }

    // Handle link fields.
    if (is_array($field_value) && isset($field_value['uri'])) {
      return $field_value;
    }

    // Handle text_long/text_with_summary fields — set value and format.
    if (is_string($field_value) && $this->isFormattedTextField($paragraph_type, $field_name)) {
      return [
        'value' => $field_value,
        'format' => 'full_html',
      ];
    }

    // Simple text value.
    return $field_value;
  }

  /**
   * Check if a field is a formatted text field (text_long, text_with_summary).
   *
   * @param string $paragraph_type
   *   The paragraph type machine name.
   * @param string $field_name
   *   The field machine name.
   *
   * @return bool
   *   TRUE if the field requires a text format.
   */
  protected function isFormattedTextField(string $paragraph_type, string $field_name): bool {
    $definitions = $this->entityTypeManager
      ->getStorage('field_config')
      ->loadByProperties([
        'entity_type' => 'paragraph',
        'bundle' => $paragraph_type,
        'field_name' => $field_name,
      ]);

    if (empty($definitions)) {
      return FALSE;
    }

    $definition = reset($definitions);
    return in_array($definition->getType(), ['text_long', 'text_with_summary', 'text'], TRUE);
  }

  /**
   * Generate an image via DALL-E and create a Media entity.
   *
   * @param string $image_prompt
   *   Description of the image to generate.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The created media entity, or NULL on failure.
   */
  protected function generateImage(string $image_prompt): ?MediaInterface {
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
      $input = new TextToImageInput($image_prompt);
      $response = $provider->textToImage($input, $default['model_id'], ['server_ai_content']);

      $images = $response->getNormalized();
      if (empty($images)) {
        return NULL;
      }

      $image_file = $images[0];
      $filename = 'ai-generated-' . time() . '.png';
      $file_path = 'public://ai-generated/' . date('Y-m');
      $media = $image_file->getAsMediaEntity('image', $file_path, $filename);

      // Set a meaningful alt text.
      $media->get('field_media_image')->alt = mb_substr($image_prompt, 0, 512);
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
   * @param string $system_prompt
   *   The system prompt with schema description.
   * @param string $user_prompt
   *   The user's content request.
   *
   * @return array
   *   Parsed JSON response with 'title' and 'paragraphs' keys.
   *
   * @throws \RuntimeException
   *   If the response cannot be parsed or is invalid.
   */
  protected function callAi(string $system_prompt, string $user_prompt): array {
    $default = $this->aiProvider->getDefaultProviderForOperationType('chat');
    if (!$default) {
      throw new \RuntimeException('No default chat provider configured.');
    }

    $provider = $this->aiProvider->createInstance($default['provider_id']);

    $input = new ChatInput([
      new ChatMessage('user', $user_prompt),
    ]);
    $input->setSystemPrompt($system_prompt);

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
   * @param string $schema_description
   *   Human-readable paragraph schema description.
   *
   * @return string
   *   The complete system prompt.
   */
  protected function buildSystemPrompt(string $schema_description): string {
    return <<<PROMPT
You are a Drupal content generator. Given a user's request, generate a landing page with appropriate paragraphs.

Available paragraph types and their fields:

{$schema_description}

Rules:
- Return valid JSON only, no markdown code fences
- Only use paragraph types that genuinely fit the user's request. Do NOT use all available types — pick the ones that make sense for the topic. A page about a single topic may only need 2-3 paragraph types.
- Generate rich, detailed content — body fields should contain multiple paragraphs with substantial information (at least 3-5 sentences per body field). Accordion items should have thorough answers, not single sentences.
- For image fields, provide an object with an "image_prompt" key containing a detailed description for DALL-E image generation
- For entity reference fields with available entities listed, provide {"target_id": ID} using an ID from the available list. Choose the most relevant entity for the page topic.
- For link fields, use "uri": "route:<nolink>" with a descriptive title as a placeholder. Do not invent URLs to pages that do not exist.
- For text_long (body) fields, use basic HTML tags (p, ul, li, strong, em)
- For sub-paragraph arrays (like field_accordion_items), provide an array of objects with the sub-paragraph field values
- For webform fields, provide the webform machine name as a string value

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
