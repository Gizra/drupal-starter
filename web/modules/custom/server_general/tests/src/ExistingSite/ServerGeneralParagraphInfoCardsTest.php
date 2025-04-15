<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Info cards' paragraph type.
 */
class ServerGeneralParagraphInfoCardsTest extends ServerGeneralParagraphTestBase {

  use ParagraphCreationTrait;

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

      $paragraph = $this->createParagraph([
        'type' => 'info_card',
        'field_info_card_header' => $header,
        'field_title' => $title,
        'field_subtitle' => $subtitle,
      ]);

      $paragraphs[] = $paragraph;
    }

    // Create accordion.
    $title = 'This is the Info cards title';
    $body = 'This is the Info cards body';

    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_title' => $title,
      'field_body' => $body,
      'field_info_cards' => $paragraphs,
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
