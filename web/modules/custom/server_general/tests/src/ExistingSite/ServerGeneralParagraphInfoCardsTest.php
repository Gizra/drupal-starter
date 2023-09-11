<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Info cards' paragraph type.
 */
class ServerGeneralParagraphInfoCardsTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'info_cards';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_info_cards',
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
      $header = 'This is the Info card header ' . $key;
      $title = 'This is the Info card title ' . $key;
      $subtitle = 'This is the Info card subtitle ' . $key;

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = Paragraph::create(['type' => 'info_card']);
      $paragraph->set('field_info_card_header', $header);
      $paragraph->set('field_title', $title);
      $paragraph->set('field_subtitle', $subtitle);
      $paragraph->save();
      $this->markEntityForCleanup($paragraph);
      $paragraphs[] = $paragraph;
    }

    // Create accordion.
    $title = 'This is the Info cards title';
    $body = 'This is the Info cards body';
    $paragraph = Paragraph::create(['type' => $this->getEntityBundle()]);
    $paragraph->set('field_title', $title);
    $paragraph->set('field_body', $body);
    $paragraph->set('field_info_cards', $paragraphs);
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

    $this->assertSession()->elementTextContains('css', '.paragraph--type--info-cards', $title);
    $this->assertSession()->elementTextContains('css', '.paragraph--type--info-cards', $body);

    // Assert all accordion items are there and hidden.
    $this->assertSession()->elementsCount('css', '.paragraph--type--info-card', 5);

    // Assert all accordion items' titles and body are there.
    foreach (range(1, 5) as $key) {
      $header = 'This is the Info card header ' . $key;
      $title = 'This is the Info card title ' . $key;
      $subtitle = 'This is the Info card subtitle ' . $key;
      $this->assertSession()->elementTextContains('css', '.paragraph--type--info-cards', $header);
      $this->assertSession()->elementTextContains('css', '.paragraph--type--info-cards', $title);
      $this->assertSession()->elementTextContains('css', '.paragraph--type--info-cards', $subtitle);
    }
  }

}
