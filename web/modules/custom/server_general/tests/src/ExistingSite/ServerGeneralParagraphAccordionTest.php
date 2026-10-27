<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Accordion' paragraph type.
 */
class ServerGeneralParagraphAccordionTest extends ServerGeneralParagraphTestBase {

  use ParagraphCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'accordion';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_accordion_items',
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
      $title = 'This is the Accordion item title ' . $key;
      $body = 'This is the Accordion item description ' . $key;

      $paragraph = $this->createParagraph([
        'type' => 'accordion_item',
        'field_title' => $title,
        'field_body' => $body,
      ]);

      $paragraphs[] = $paragraph;
    }

    // Create accordion.
    $title = 'This is the Accordion title';
    $body = 'This is the Accordion description';

    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_title' => $title,
      'field_body' => $body,
      'field_accordion_items' => $paragraphs,
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

    $this->assertSession()->elementTextContains('css', '.paragraph--type--accordion', $title);
    $this->assertSession()->elementTextContains('css', '.paragraph--type--accordion', $body);

    // Assert all accordion items are there and hidden.
    $this->assertSession()->elementsCount('css', '.paragraph--type--accordion-item .accordion-description.hidden', 5);

    // Assert all accordion items' titles and body are there.
    foreach (range(1, 5) as $key) {
      $title = 'This is the Accordion item title ' . $key;
      $body = 'This is the Accordion item description ' . $key;
      $this->assertSession()->elementTextContains('css', '.paragraph--type--accordion', $title);
      $this->assertSession()->elementTextContains('css', '.paragraph--type--accordion', $body);
    }
  }

}
