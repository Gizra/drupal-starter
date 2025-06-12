<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'text' paragraph type.
 */
class ServerGeneralParagraphTextTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'text';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_body',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_title',
    ];
  }

  /**
   * Tests paragraph creation without required fields.
   *
   * This test verifies that paragraphs can be created without required fields
   * but validates they have empty values.
   */
  public function testParagraphCreationWithoutRequiredFields(): void {
    // Create a paragraph without the required field_body.
    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_title' => 'Test Title',
      // Intentionally omitting field_body which is required.
    ]);

    // Verify the paragraph was created but has empty required field.
    $this->assertInstanceOf('Drupal\paragraphs\ParagraphInterface', $paragraph);
    $this->assertTrue($paragraph->get('field_body')->isEmpty());
    $this->assertEquals('Test Title', $paragraph->get('field_title')->value);
  }

  /**
   * Tests paragraph with extremely long text content.
   *
   * This test verifies handling of edge case with very long text content.
   */
  public function testParagraphWithLongTextContent(): void {
    // Create a very long text string (10,000 characters).
    $long_text = str_repeat('This is a very long text content. ', 250);

    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_body' => [
        'value' => $long_text,
        'format' => 'basic_html',
      ],
      'field_title' => 'Long Content Test',
    ]);

    // Verify the paragraph was created successfully.
    $this->assertInstanceOf('Drupal\paragraphs\ParagraphInterface', $paragraph);
    $this->assertEquals($long_text, $paragraph->get('field_body')->value);
  }

  /**
   * Tests paragraph with various HTML content formats.
   *
   * This test ensures the paragraph handles different text formats correctly.
   */
  public function testParagraphWithDifferentTextFormats(): void {
    $test_cases = [
      'plain_text' => 'Simple plain text content',
      'basic_html' => '<p>Basic HTML content with <strong>bold</strong> text</p>',
      'full_html' => '<div class="custom"><h2>Full HTML content</h2><p>With more formatting</p></div>',
    ];

    foreach ($test_cases as $format => $content) {
      $paragraph = $this->createParagraph([
        'type' => $this->getEntityBundle(),
        'field_body' => [
          'value' => $content,
          'format' => $format,
        ],
        'field_title' => "Test {$format}",
      ]);

      $this->assertInstanceOf('Drupal\paragraphs\ParagraphInterface', $paragraph);
      $this->assertEquals($content, $paragraph->get('field_body')->value);
      $this->assertEquals($format, $paragraph->get('field_body')->first()->get('format')->getValue());
    }
  }

}
