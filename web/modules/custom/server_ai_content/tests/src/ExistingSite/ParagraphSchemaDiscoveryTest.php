<?php

declare(strict_types=1);

namespace Drupal\Tests\server_ai_content\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the ParagraphSchemaDiscovery service.
 */
class ParagraphSchemaDiscoveryTest extends ExistingSiteBase {

  /**
   * Test that schema discovery returns allowed paragraph types with fields.
   */
  public function testGetSchemaReturnsAllowedTypes(): void {
    /** @var \Drupal\server_ai_content\Service\ParagraphSchemaDiscovery $discovery */
    $discovery = \Drupal::service('server_ai_content.paragraph_schema_discovery');
    $schema = $discovery->getSchema('landing_page');

    // Should be an array keyed by paragraph type machine name.
    $this->assertIsArray($schema);

    // Should include allowed types.
    $this->assertArrayHasKey('text', $schema);
    $this->assertArrayHasKey('hero_image', $schema);
    $this->assertArrayHasKey('cta', $schema);
    $this->assertArrayHasKey('accordion', $schema);
    $this->assertArrayHasKey('info_cards', $schema);
    $this->assertArrayHasKey('quick_links', $schema);

    // Should exclude types that need existing entities.
    $this->assertArrayNotHasKey('documents', $schema);
    $this->assertArrayNotHasKey('form', $schema);
    $this->assertArrayNotHasKey('related_content', $schema);
    $this->assertArrayNotHasKey('search', $schema);
    $this->assertArrayNotHasKey('news_teasers', $schema);
    $this->assertArrayNotHasKey('people_teasers', $schema);
    $this->assertArrayNotHasKey('quote', $schema);

    // Text type should have field_body and field_title.
    $textFields = array_column($schema['text']['fields'], 'field_id');
    $this->assertContains('field_body', $textFields);
    $this->assertContains('field_title', $textFields);
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
    $this->assertStringContainsString('hero_image', $description);
    $this->assertStringContainsString('field_title', $description);
  }

}
