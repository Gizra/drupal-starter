<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Quick link' paragraph type.
 */
class ServerGeneralParagraphQuickLinksTest extends ServerGeneralParagraphTestBase {

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

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = Paragraph::create(['type' => 'quick_link_item']);
      $paragraph->set('field_link', [
        'uri' => 'https://example.com',
        'title' => $title,
      ]);
      $paragraph->set('field_subtitle', $body);
      $paragraph->save();
      $this->markEntityForCleanup($paragraph);
      $paragraphs[] = $paragraph;
    }

    // Create Quick links.
    $title = 'This is the Quick links title';
    $body = 'This is the Quick links description';
    $paragraph = Paragraph::create(['type' => $this->getEntityBundle()]);
    $paragraph->set('field_title', $title);
    $paragraph->set('field_body', $body);
    $paragraph->set('field_quick_link_items', $paragraphs);
    $paragraph->save();
    $this->markEntityForCleanup($paragraph);

    $user = $this->createUser();
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
      'uid' => $user->id(),
      'field_paragraphs' => [
        $this->getParagraphReferenceValues($paragraph),
      ],
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
