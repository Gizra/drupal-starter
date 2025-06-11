<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;

/**
 * Test 'views' paragraph type.
 */
class ServerGeneralParagraphNewsTeasersTest extends ServerGeneralParagraphTestBase {

  use ParagraphCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'news_teasers';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_title',
      'field_body',
    ];
  }

  /**
   * Test render of the paragraph.
   */
  public function testRender() {
    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
    ]);

    // Add a Landing page, and reference the Paragraph.
    $landing_page_node = $this->createNode([
      'type' => 'landing_page',
      'moderation_state' => 'published',
      'field_paragraphs' => [
        $paragraph,
      ],
    ]);

    // Create node.news with allowed and disallowed html tags in field_body.
    // @see Drupal\server_general\ProcessedTextBuilderTrait
    $body = 'The wrapping h3 tag should be stripped but <strong>this strong one not.</strong>';

    $this->createNode([
      'title' => 'Test News',
      'type' => 'news',
      'field_body' => [
        'value' => '<h3>' . $body . '</h3>',
        'format' => 'full_html',
      ],
      'moderation_state' => 'published',
    ]);

    $this->drupalGet($landing_page_node->toUrl());
    $this->assertSession()->elementNotExists('css', '.node--type-news.node--view-mode-teaser .field--name-field-body h3');
    $this->assertSession()->elementExists('css', '.node--type-news.node--view-mode-teaser .field--name-field-body strong');
    $this->assertStringContainsString($body, $this->getCurrentPage()->getOuterHtml());
  }

}
