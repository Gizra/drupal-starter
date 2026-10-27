<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Cta' paragraph type.
 */
class ServerGeneralParagraphCtaTest extends ServerGeneralParagraphTestBase {

  use ParagraphCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'cta';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_link',
      'field_title',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_body',
    ];
  }

  /**
   * Test render of the paragraph.
   */
  public function testRender() {
    $title = 'Lorem ipsum dolor sit amet';
    $body = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

    $cta = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_title' => $title,
      'field_body' => $body,
      'field_link' => [
        'uri' => 'https://example.com',
        'title' => 'Button text',
      ],
    ]);

    $user = $this->createUser();
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
      'uid' => $user->id(),
      'field_paragraphs' => [
        $this->getParagraphReferenceValues($cta),
      ],
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();
    $this->assertEquals($user->id(), $node->getOwnerId());

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->assertSession()->elementTextContains('css', '.paragraph--type--cta', 'Lorem ipsum dolor sit amet');
    $this->assertSession()->elementTextContains('css', '.paragraph--type--cta', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
    $this->assertSession()->elementTextContains('css', '.paragraph--type--cta', 'Button text');
    $this->assertSession()->linkExists('Button text');
    $this->assertSession()->linkByHrefExists('https://example.com');
  }

}
