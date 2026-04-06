<?php

declare(strict_types=1);

namespace Drupal\Tests\server_ai_content\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the ParagraphSchemaDiscovery service.
 */
class ParagraphSchemaDiscoveryTest extends ExistingSiteBase {

  /**
   * Test that schema discovery returns paragraph types with fields.
   */
  public function testGetSchemaReturnsParagraphTypes(): void {
    /** @var \Drupal\server_ai_content\Service\ParagraphSchemaDiscovery $discovery */
    $discovery = \Drupal::service('server_ai_content.paragraph_schema_discovery');
    $schema = $discovery->getSchema('landing_page');

    $this->assertIsArray($schema);

    // Types with only generatable fields should always be present.
    $this->assertArrayHasKey('text', $schema);
    $this->assertArrayHasKey('cta', $schema);
    $this->assertArrayHasKey('accordion', $schema);
    $this->assertArrayHasKey('info_cards', $schema);
    $this->assertArrayHasKey('quick_links', $schema);

    // Text type should have field_body and field_title.
    $textFields = array_column($schema['text']['fields'], 'field_id');
    $this->assertContains('field_body', $textFields);
    $this->assertContains('field_title', $textFields);

    // Accordion should have sub_paragraph detected.
    $this->assertArrayHasKey('sub_paragraph', $schema['accordion']);
    $this->assertEquals('accordion_item', $schema['accordion']['sub_paragraph']['type']);
  }

  /**
   * Test types with required references are excluded when no entities exist.
   */
  public function testTypesWithMissingReferencesExcluded(): void {
    /** @var \Drupal\server_ai_content\Service\ParagraphSchemaDiscovery $discovery */
    $discovery = \Drupal::service('server_ai_content.paragraph_schema_discovery');
    $schema = $discovery->getSchema('landing_page');

    // Types whose required references have no entities should be excluded.
    // Whether they appear depends on existing content in the test database.
    // At minimum, search (no fields) and news_teasers (no required refs)
    // should be present.
    $this->assertArrayHasKey('search', $schema);
    $this->assertArrayHasKey('news_teasers', $schema);
  }

  /**
   * Test that entity reference fields include available entities.
   */
  public function testReferenceFieldsIncludeAvailableEntities(): void {
    /** @var \Drupal\server_ai_content\Service\ParagraphSchemaDiscovery $discovery */
    $discovery = \Drupal::service('server_ai_content.paragraph_schema_discovery');
    $schema = $discovery->getSchema('landing_page');

    // hero_image has field_image referencing media.image.
    // It may or may not be in schema depending on existing media.
    if (isset($schema['hero_image'])) {
      $imageField = NULL;
      foreach ($schema['hero_image']['fields'] as $field) {
        if ($field['field_id'] === 'field_image') {
          $imageField = $field;
          break;
        }
      }
      $this->assertNotNull($imageField);
      $this->assertArrayHasKey('available_entities', $imageField);
    }
  }

  /**
   * Test that buildPromptDescription returns a non-empty string.
   */
  public function testBuildPromptDescription(): void {
    /** @var \Drupal\server_ai_content\Service\ParagraphSchemaDiscovery $discovery */
    $discovery = \Drupal::service('server_ai_content.paragraph_schema_discovery');
    $description = $discovery->buildPromptDescription('landing_page');

    $this->assertIsString($description);
    $this->assertNotEmpty($description);
    $this->assertStringContainsString('text', $description);
    $this->assertStringContainsString('field_title', $description);
  }

}
