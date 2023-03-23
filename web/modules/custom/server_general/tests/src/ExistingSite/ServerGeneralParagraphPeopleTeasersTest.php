<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'People teasers' paragraph type.
 */
class ServerGeneralParagraphPeopleTeasersTest extends ServerGeneralParagraphTestBase {

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

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = Paragraph::create(['type' => 'person_teaser']);
      $paragraph->set('field_title', $title);
      $paragraph->set('field_subtitle', $subtitle);
      $paragraph->set('field_image', $media);
      $paragraph->save();
      $this->markEntityForCleanup($paragraph);
      $person_teasers[] = $paragraph;
    }

    // Create accordion.
    $title = 'This is the People teasers title';
    $subtitle = 'This is the People teasers description';
    $paragraph = Paragraph::create(['type' => $this->getEntityBundle()]);
    $paragraph->set('field_title', $title);
    $paragraph->set('field_body', $subtitle);
    $paragraph->set('field_person_teasers', $person_teasers);
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
