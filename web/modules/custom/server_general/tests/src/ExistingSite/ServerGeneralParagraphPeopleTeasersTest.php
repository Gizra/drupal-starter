<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'People teasers' paragraph type.
 */
class ServerGeneralParagraphPeopleTeasersTest extends ServerGeneralParagraphTestBase {

  use ParagraphCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'people_teasers';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_person_teasers',
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
    $media = $this->createMediaImage();

    // Create person teasers.
    $person_teasers = [];

    foreach (range(1, 5) as $key) {
      $title = 'This is the Person teaser title ' . $key;
      $subtitle = 'This is the Person teaser subtitle ' . $key;

      $paragraph = $this->createParagraph([
        'type' => 'person_teaser',
        'field_title' => $title,
        'field_subtitle' => $subtitle,
        'field_image' => [
          'target_id' => $media->id(),
        ],
      ]);

      $person_teasers[] = $paragraph;
    }

    // Create accordion.
    $title = 'This is the People teasers title';
    $subtitle = 'This is the People teasers description';

    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_title' => $title,
      'field_body' => $subtitle,
      'field_person_teasers' => $person_teasers,
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

    $this->assertSession()->elementTextContains('css', '.paragraph--type--people-teasers', $title);
    $this->assertSession()->elementTextContains('css', '.paragraph--type--people-teasers', $subtitle);

    // Assert all person teasers are there.
    $this->assertSession()->elementsCount('css', '.paragraph--type--person-teaser', 5);

    // Assert all person teasers' titles and subtitle are there.
    foreach (range(1, 5) as $key) {
      $title = 'This is the Person teaser title ' . $key;
      $subtitle = 'This is the Person teaser subtitle ' . $key;
      $this->assertSession()->elementTextContains('css', '.paragraph--type--people-teasers', $title);
      $this->assertSession()->elementTextContains('css', '.paragraph--type--people-teasers', $subtitle);
    }
  }

}
