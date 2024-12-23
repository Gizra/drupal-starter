<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Quick link' paragraph type.
 */
class ServerGeneralParagraphQuickLinksTest extends ServerGeneralParagraphTestBase {

  use ParagraphCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'quick_links';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_quick_link_items',
    ];
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
    // Create referenced paragraphs.
    $paragraphs = [];

    foreach (range(1, 5) as $key) {
      $title = 'This is the Quick link item title ' . $key;
      $body = 'This is the Quick link subtitle ' . $key;

      $paragraph = $this->createParagraph([
        'type' => 'quick_link_item',
        'field_link' => [
          'uri' => 'https://example.com',
          'title' => $title,
        ],
        'field_subtitle' => $body,
      ]);

      $paragraphs[] = $paragraph;
    }

    // Create Quick links.
    $title = 'This is the Quick links title';
    $body = 'This is the Quick links description';

    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_title' => $title,
      'field_body' => $body,
      'field_quick_link_items' => $paragraphs,
    ]);

    $user = $this->createUser();
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
      'uid' => $user->id(),
      'field_paragraphs' => [
        $this->getParagraphReferenceValues($paragraph),
      ],
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->assertSession()->elementTextContains('css', '.paragraph--type--quick-links', $title);
    $this->assertSession()->elementTextContains('css', '.paragraph--type--quick-links', $body);

    // Assert all quick link items are there.
    $this->assertSession()->elementsCount('css', '.paragraph--type--quick-link-item', 5);

    // Assert all accordion items' titles and body are there.
    foreach (range(1, 5) as $key) {
      $title = 'This is the Quick link item title ' . $key;
      $body = 'This is the Quick link subtitle ' . $key;
      $this->assertSession()->elementTextContains('css', '.paragraph--type--quick-links', $title);
      $this->assertSession()->elementTextContains('css', '.paragraph--type--quick-links', $body);
    }
  }

}
