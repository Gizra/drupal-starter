<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Accordion' paragraph type.
 */
class ServerGeneralParagraphAccordionTest extends ServerGeneralParagraphTestBase {

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

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = Paragraph::create(['type' => 'accordion_item']);
      $paragraph->set('field_title', $title);
      $paragraph->set('field_body', $body);
      $paragraph->save();
      $this->markEntityForCleanup($paragraph);
      $paragraphs[] = $paragraph;
    }

    // Create accordion.
    $title = 'This is the Accordion title';
    $body = 'This is the Accordion description';
    $paragraph = Paragraph::create(['type' => $this->getEntityBundle()]);
    $paragraph->set('field_title', $title);
    $paragraph->set('field_body', $body);
    $paragraph->set('field_accordion_items', $paragraphs);
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
