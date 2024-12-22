<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;

/**
 * Test Paragraph "Form".
 */
class ServerGeneralParagraphFormTest extends ServerGeneralFieldableEntityTestBase {

  use ParagraphCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'paragraph';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'form';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_webform',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_title',
      'field_description',
    ];
  }

  /**
   * Tests the rendering of the Paragraph type.
   */
  public function testRender() {
    $title = 'The Form paragraph title';

    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_title' => $title,
      'field_webform' => 'contact',
    ]);

    // Add a Landing page, and reference the Paragraph.
    $landing_page_node = $this->createNode([
      'type' => 'landing_page',
      'moderation_state' => 'published',
      'field_paragraphs' => [
        $paragraph,
      ],
    ]);

    $this->drupalGet($landing_page_node->toUrl());
    $assert_session = $this->assertSession();

    $assert_session->elementTextContains('css', '.paragraph--type--form', $title);
    $assert_session->pageTextContains('Your name');
    $assert_session->pageTextContains('Your Email Address');
    $assert_session->pageTextContains('Subject');
    $assert_session->pageTextContains('Message');
  }

}
