<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Quote' paragraph type.
 */
class ServerGeneralParagraphQuoteTest extends ServerGeneralParagraphTestBase {

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
    $paragraph = Paragraph::create(['type' => $this->getEntityBundle()]);
    $paragraph->set('field_body', $body);
    $paragraph->set('field_subtitle', $subtitle);
    $paragraph->set('field_image', $media);
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

    $this->assertSession()->elementTextContains('css', '.paragraph--type--quote', $body);
    $this->assertSession()->elementTextContains('css', '.paragraph--type--quote', $subtitle);
  }

}
