<?php

declare(strict_types=1);

namespace Drupal\server_ai_content\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Discovers paragraph type schemas for AI prompt building.
 */
class ParagraphSchemaDiscovery {

  /**
   * Paragraph types the AI can fully generate from scratch.
   *
   * Types requiring existing entity references (documents, form,
   * related_content, etc.) are excluded.
   */
  private const ALLOWED_TYPES = [
    'hero_image',
    'text',
    'cta',
    'accordion',
    'info_cards',
    'quick_links',
  ];

  /**
   * Base fields to skip (internal Drupal fields, not content fields).
   */
  private const SKIP_FIELDS = [
    'id',
    'uuid',
    'revision_id',
    'langcode',
    'type',
    'status',
    'created',
    'parent_id',
    'parent_type',
    'parent_field_name',
    'behavior_settings',
    'default_langcode',
    'revision_default',
    'revision_translation_affected',
    'content_translation_source',
    'content_translation_outdated',
    'content_translation_changed',
    'revision_created',
    'revision_uid',
    'revision_log',
  ];

  /**
   * Constructs a ParagraphSchemaDiscovery service.
   */
  public function __construct(
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityTypeBundleInfoInterface $bundleInfoService,
  ) {}

  /**
   * Get schema for all allowed paragraph types on a content type.
   *
   * @param string $contentType
   *   The node bundle (e.g., 'landing_page').
   *
   * @return array
   *   Keyed by paragraph type machine name, each containing 'label' and
   *   'fields' (array of field info arrays). Compound types also have
   *   a 'sub_paragraph' key with sub-paragraph type info.
   */
  public function getSchema(string $contentType): array {
    $allowedBundles = $this->getAllowedBundles($contentType);
    $schema = [];
    $bundleInfo = $this->bundleInfoService->getBundleInfo('paragraph');

    foreach ($allowedBundles as $bundle) {
      $fields = $this->getFieldsForBundle($bundle);
      if (empty($fields)) {
        continue;
      }

      $schema[$bundle] = [
        'label' => $bundleInfo[$bundle]['label'] ?? $bundle,
        'fields' => $fields,
      ];

      // Detect sub-paragraph fields dynamically: any
      // entity_reference_revisions field targeting paragraphs.
      foreach ($fields as $field) {
        if ($field['field_type'] !== 'entity_reference_revisions') {
          continue;
        }
        if (($field['target_entity_type'] ?? '') !== 'paragraph') {
          continue;
        }
        $subBundles = array_keys($field['target_bundles'] ?? []);
        foreach ($subBundles as $subBundle) {
          $subFields = $this->getFieldsForBundle($subBundle);
          if (!empty($subFields)) {
            $schema[$bundle]['sub_paragraph'] = [
              'type' => $subBundle,
              'label' => $bundleInfo[$subBundle]['label'] ?? $subBundle,
              'field_name' => $field['field_id'],
              'fields' => $subFields,
            ];
          }
        }
      }
    }

    return $schema;
  }

  /**
   * Get the compound type mapping from the schema.
   *
   * Returns a mapping of parent paragraph type to sub-paragraph info,
   * derived dynamically from field definitions.
   *
   * @param string $contentType
   *   The node bundle.
   *
   * @return array
   *   Keyed by parent type, value is array with 'field_name' and 'sub_type'.
   *   Example: ['accordion' => ['field_name' => 'field_accordion_items',
   *   'sub_type' => 'accordion_item']].
   */
  public function getCompoundTypeMapping(string $contentType): array {
    $schema = $this->getSchema($contentType);
    $mapping = [];

    foreach ($schema as $type => $info) {
      if (!empty($info['sub_paragraph'])) {
        $mapping[$type] = [
          'field_name' => $info['sub_paragraph']['field_name'],
          'sub_type' => $info['sub_paragraph']['type'],
        ];
      }
    }

    return $mapping;
  }

  /**
   * Build a human-readable description of the schema for the AI system prompt.
   *
   * @param string $contentType
   *   The node bundle.
   *
   * @return string
   *   A text description of available paragraph types and their fields.
   */
  public function buildPromptDescription(string $contentType): string {
    $schema = $this->getSchema($contentType);
    $lines = [];

    foreach ($schema as $type => $info) {
      $lines[] = "## Paragraph type: {$type} ({$info['label']})";
      $lines[] = "Fields:";
      foreach ($info['fields'] as $field) {
        $required = $field['required'] ? ' (REQUIRED)' : '';
        $description = "- {$field['field_id']}: {$field['field_type']}{$required}";
        if (!empty($field['target_entity_type'])) {
          $description .= " -> references {$field['target_entity_type']}";
        }
        $lines[] = $description;
      }

      if (!empty($info['sub_paragraph'])) {
        $sub = $info['sub_paragraph'];
        $lines[] = "Sub-paragraph type for {$sub['field_name']}: {$sub['type']} ({$sub['label']})";
        $lines[] = "Sub-paragraph fields:";
        foreach ($sub['fields'] as $field) {
          $required = $field['required'] ? ' (REQUIRED)' : '';
          $lines[] = "- {$field['field_id']}: {$field['field_type']}{$required}";
        }
      }

      $lines[] = "";
    }

    return implode("\n", $lines);
  }

  /**
   * Get allowed paragraph bundles for a content type, filtered by allow-list.
   *
   * @param string $contentType
   *   The node bundle.
   *
   * @return array
   *   List of allowed paragraph bundle machine names.
   */
  protected function getAllowedBundles(string $contentType): array {
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions('node', $contentType);

    if (!isset($fieldDefinitions['field_paragraphs'])) {
      return [];
    }

    $handlerSettings = $fieldDefinitions['field_paragraphs']->getSetting('handler_settings');
    $targetBundles = $handlerSettings['target_bundles'] ?? [];

    return array_intersect(array_keys($targetBundles), self::ALLOWED_TYPES);
  }

  /**
   * Get content field definitions for a paragraph bundle.
   *
   * @param string $bundle
   *   The paragraph bundle machine name.
   *
   * @return array
   *   Array of field info arrays.
   */
  protected function getFieldsForBundle(string $bundle): array {
    $fields = [];
    $definitions = $this->entityFieldManager->getFieldDefinitions('paragraph', $bundle);

    foreach ($definitions as $fieldName => $definition) {
      if (in_array($fieldName, self::SKIP_FIELDS, TRUE)) {
        continue;
      }
      if ($definition->isComputed()) {
        continue;
      }

      $info = [
        'field_id' => $fieldName,
        'field_label' => (string) $definition->getLabel(),
        'field_type' => $definition->getType(),
        'required' => $definition->isRequired(),
        'cardinality' => $definition->getFieldStorageDefinition()->getCardinality(),
      ];

      if (in_array($definition->getType(), ['entity_reference', 'entity_reference_revisions'])) {
        $info['target_entity_type'] = $definition->getSetting('target_type');
        $info['target_bundles'] = $definition->getSetting('handler_settings')['target_bundles'] ?? [];
      }

      $fields[] = $info;
    }

    return $fields;
  }

}
