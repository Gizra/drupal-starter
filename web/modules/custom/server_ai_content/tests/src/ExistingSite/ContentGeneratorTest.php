<?php

declare(strict_types=1);

namespace Drupal\Tests\server_ai_content\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the ContentGenerator service entity creation.
 */
class ContentGeneratorTest extends ExistingSiteBase {

  /**
   * Test creating a landing page from parsed AI response data.
   */
  public function testCreateFromParsedData(): void {
    /** @var \Drupal\server_ai_content\Service\ContentGenerator $generator */
    $generator = \Drupal::service('server_ai_content.content_generator');

    $data = [
      'title' => 'Test AI Generated Page',
      'paragraphs' => [
        [
          'type' => 'text',
          'fields' => [
            'field_title' => 'Test Section',
            'field_body' => '<p>Test body content.</p>',
          ],
        ],
        [
          'type' => 'cta',
          'fields' => [
            'field_title' => 'Call to Action',
            'field_body' => '<p>Act now.</p>',
            'field_link' => [
              'uri' => 'https://example.com',
              'title' => 'Click Here',
            ],
          ],
        ],
        [
          'type' => 'accordion',
          'fields' => [
            'field_title' => 'FAQ',
            'field_accordion_items' => [
              [
                'field_title' => 'Question 1',
                'field_body' => '<p>Answer 1.</p>',
              ],
              [
                'field_title' => 'Question 2',
                'field_body' => '<p>Answer 2.</p>',
              ],
            ],
          ],
        ],
      ],
    ];

    $node = $generator->createFromParsedData($data, 'landing_page');

    // Node was created and is unpublished.
    $this->assertNotNull($node->id());
    $this->assertEquals('Test AI Generated Page', $node->getTitle());
    $this->assertFalse($node->isPublished());
    $this->assertEquals('landing_page', $node->bundle());

    // Has 3 paragraphs attached.
    $paragraphs = $node->get('field_paragraphs')->referencedEntities();
    $this->assertCount(3, $paragraphs);

    // First paragraph is text type.
    $this->assertEquals('text', $paragraphs[0]->bundle());
    $this->assertEquals('Test Section', $paragraphs[0]->get('field_title')->value);
    $this->assertEquals('<p>Test body content.</p>', $paragraphs[0]->get('field_body')->value);

    // Second paragraph is cta type with link.
    $this->assertEquals('cta', $paragraphs[1]->bundle());
    $this->assertEquals('https://example.com', $paragraphs[1]->get('field_link')->uri);
    $this->assertEquals('Click Here', $paragraphs[1]->get('field_link')->title);

    // Third paragraph is accordion with 2 items.
    $this->assertEquals('accordion', $paragraphs[2]->bundle());
    $accordionItems = $paragraphs[2]->get('field_accordion_items')->referencedEntities();
    $this->assertCount(2, $accordionItems);
    $this->assertEquals('Question 1', $accordionItems[0]->get('field_title')->value);
    $this->assertEquals('Question 2', $accordionItems[1]->get('field_title')->value);

    // Clean up.
    $node->delete();
  }

}
