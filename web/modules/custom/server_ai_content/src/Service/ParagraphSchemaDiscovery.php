<?php

declare(strict_types=1);

namespace Drupal\server_ai_content\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Discovers paragraph type schemas for AI prompt building.
 *
 * Dynamically inspects all paragraph types allowed on a content type's
 * paragraph reference field. For entity reference fields, queries existing
 * entities so the AI can pick from real content. Excludes paragraph types
 * whose required reference fields have no available entities.
 */
class ParagraphSchemaDiscovery {

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
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Get schema for all paragraph types on a content type.
   *
   * Inspects each paragraph type's fields and determines availability.
   * For entity reference fields, queries existing entities and includes
   * them as available options. Excludes types whose required references
   * have no available entities.
   *
   * @param string $content_type
   *   The node bundle (e.g., 'landing_page').
   *
   * @return array
   *   Keyed by paragraph type machine name, each containing 'label' and
   *   'fields' (array of field info arrays). Compound types also have
   *   a 'sub_paragraph' key with sub-paragraph type info.
   */
  public function getSchema(string $content_type): array {
    $bundles = $this->getParagraphBundles($content_type);
    $schema = [];
    $bundle_info = $this->bundleInfoService->getBundleInfo('paragraph');

    foreach ($bundles as $bundle) {
      $fields = $this->getFieldsForBundle($bundle);

      // Check if this type is usable (all required references have entities).
      if (!$this->isBundleUsable($fields)) {
        continue;
      }

      $schema[$bundle] = [
        'label' => $bundle_info[$bundle]['label'] ?? $bundle,
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
        $sub_bundles = array_keys($field['target_bundles'] ?? []);
        foreach ($sub_bundles as $sub_bundle) {
          $sub_fields = $this->getFieldsForBundle($sub_bundle);
          if (!$this->isBundleUsable($sub_fields)) {
            continue;
          }
          $schema[$bundle]['sub_paragraph'] = [
            'type' => $sub_bundle,
            'label' => $bundle_info[$sub_bundle]['label'] ?? $sub_bundle,
            'field_name' => $field['field_id'],
            'fields' => $sub_fields,
          ];
        }
      }
    }

    return $schema;
  }

  /**
   * Get the compound type mapping from the schema.
   *
   * @param string $content_type
   *   The node bundle.
   *
   * @return array
   *   Keyed by parent type, value is array with 'field_name' and 'sub_type'.
   */
  public function getCompoundTypeMapping(string $content_type): array {
    $schema = $this->getSchema($content_type);
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
   * @param string $content_type
   *   The node bundle.
   *
   * @return string
   *   A text description of available paragraph types and their fields.
   */
  public function buildPromptDescription(string $content_type): string {
    $schema = $this->getSchema($content_type);
    $lines = [];

    foreach ($schema as $type => $info) {
      $lines[] = "## Paragraph type: {$type} ({$info['label']})";
      $lines[] = "Fields:";
      foreach ($info['fields'] as $field) {
        $required = $field['required'] ? ' (REQUIRED)' : '';
        $description = "- {$field['field_id']}: {$field['field_type']}{$required}";
        if (!empty($field['available_entities'])) {
          $description .= " — choose from existing entities by ID:";
          $lines[] = $description;
          foreach ($field['available_entities'] as $entity) {
            $lines[] = "    - id: {$entity['id']}, label: \"{$entity['label']}\"";
          }
          continue;
        }
        if (!empty($field['target_entity_type']) && $field['field_type'] !== 'entity_reference_revisions') {
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
          $field_desc = "- {$field['field_id']}: {$field['field_type']}{$required}";
          if (!empty($field['available_entities'])) {
            $field_desc .= " — choose from existing entities by ID:";
            $lines[] = $field_desc;
            foreach ($field['available_entities'] as $entity) {
              $lines[] = "    - id: {$entity['id']}, label: \"{$entity['label']}\"";
            }
            continue;
          }
          $lines[] = $field_desc;
        }
      }

      $lines[] = "";
    }

    return implode("\n", $lines);
  }

  /**
   * Get all paragraph bundles configured on a content type's paragraph field.
   *
   * @param string $content_type
   *   The node bundle.
   *
   * @return array
   *   List of paragraph bundle machine names.
   */
  protected function getParagraphBundles(string $content_type): array {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $content_type);

    if (!isset($field_definitions['field_paragraphs'])) {
      return [];
    }

    $handler_settings = $field_definitions['field_paragraphs']->getSetting('handler_settings');
    $target_bundles = $handler_settings['target_bundles'] ?? [];

    return array_keys($target_bundles);
  }

  /**
   * Check if a paragraph bundle is usable for AI generation.
   *
   * A bundle is usable if all its required entity reference fields
   * (except sub-paragraph references) have at least one existing entity.
   *
   * @param array $fields
   *   Field definitions from getFieldsForBundle().
   *
   * @return bool
   *   TRUE if the bundle can be used for generation.
   */
  protected function isBundleUsable(array $fields): bool {
    foreach ($fields as $field) {
      if (!$field['required']) {
        continue;
      }
      if (!$this->isReferenceField($field)) {
        continue;
      }
      // Sub-paragraph references are always usable.
      if ($field['field_type'] === 'entity_reference_revisions'
        && ($field['target_entity_type'] ?? '') === 'paragraph') {
        continue;
      }
      // Required reference with no available entities — not usable.
      if (empty($field['available_entities'])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Check if a field is an entity reference (not sub-paragraph).
   *
   * @param array $field
   *   Field info array.
   *
   * @return bool
   *   TRUE if the field references entities.
   */
  protected function isReferenceField(array $field): bool {
    return in_array($field['field_type'], [
      'entity_reference',
      'entity_reference_revisions',
      'webform',
    ], TRUE);
  }

  /**
   * Get content field definitions for a paragraph bundle.
   *
   * For entity reference fields, queries existing entities and attaches
   * them as 'available_entities' on the field info.
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

    foreach ($definitions as $field_name => $definition) {
      if (in_array($field_name, self::SKIP_FIELDS, TRUE)) {
        continue;
      }
      if ($definition->isComputed()) {
        continue;
      }

      $field_type = $definition->getType();
      $info = [
        'field_id' => $field_name,
        'field_label' => (string) $definition->getLabel(),
        'field_type' => $field_type,
        'required' => $definition->isRequired(),
        'cardinality' => $definition->getFieldStorageDefinition()->getCardinality(),
      ];

      if (in_array($field_type, ['entity_reference', 'entity_reference_revisions'])) {
        $target_type = $definition->getSetting('target_type');
        $target_bundles = $definition->getSetting('handler_settings')['target_bundles'] ?? [];
        $info['target_entity_type'] = $target_type;
        $info['target_bundles'] = $target_bundles;

        // For non-paragraph references, query existing entities.
        if ($target_type !== 'paragraph') {
          $info['available_entities'] = $this->queryAvailableEntities($target_type, array_keys($target_bundles));
        }
      }
      elseif ($field_type === 'webform') {
        $info['available_entities'] = $this->queryAvailableWebforms();
      }

      $fields[] = $info;
    }

    return $fields;
  }

  /**
   * Query existing entities for a reference field.
   *
   * @param string $entity_type
   *   The entity type ID (e.g., 'node', 'media').
   * @param array $bundles
   *   The allowed bundles.
   *
   * @return array
   *   Array of ['id' => ..., 'label' => ...] for existing entities.
   *   Limited to 50 most recent entities.
   */
  protected function queryAvailableEntities(string $entity_type, array $bundles): array {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
    }
    catch (\Exception) {
      return [];
    }

    $query = $storage->getQuery()->accessCheck(TRUE)->range(0, 50);

    $bundle_key = $entity_type_definition->getKey('bundle');
    if ($bundle_key && !empty($bundles)) {
      $query->condition($bundle_key, $bundles, 'IN');
    }

    // Sort by most recent if the entity type supports it.
    $id_key = $entity_type_definition->getKey('id');
    if ($id_key) {
      $query->sort($id_key, 'DESC');
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $result = [];
    foreach ($entities as $entity) {
      $result[] = [
        'id' => $entity->id(),
        'label' => (string) $entity->label(),
      ];
    }

    return $result;
  }

  /**
   * Query available webforms.
   *
   * @return array
   *   Array of ['id' => ..., 'label' => ...] for existing webforms.
   */
  protected function queryAvailableWebforms(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('webform');
    }
    catch (\Exception) {
      return [];
    }

    $ids = $storage->getQuery()->accessCheck(TRUE)->range(0, 50)->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $result = [];
    foreach ($entities as $entity) {
      $result[] = [
        'id' => $entity->id(),
        'label' => (string) $entity->label(),
      ];
    }

    return $result;
  }

}
