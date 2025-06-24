<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Quote' paragraph type.
 */
class ServerGeneralParagraphQuoteTest extends ServerGeneralParagraphTestBase {

  use ParagraphCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'quote';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_body',
      'field_image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_subtitle',
    ];
  }

  /**
   * Test render of the paragraph.
   */
  public function testRender() {
    // Create Media image.
    $media = $this->createMediaImage();

    // Create Quote.
    $body = 'This is the body';
    $subtitle = 'This is the subtitle';

    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_body' => $body,
      'field_subtitle' => $subtitle,
      'field_image' => [
        'target_id' => $media->id(),
      ],
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

    $this->assertSession()->elementTextContains('css', '.paragraph--type--quote', $body);
    $this->assertSession()->elementTextContains('css', '.paragraph--type--quote', $subtitle);
  }

}
