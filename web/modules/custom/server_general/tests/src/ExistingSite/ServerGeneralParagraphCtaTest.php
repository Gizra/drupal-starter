<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'cta' paragraph type.
 */
class ServerGeneralParagraphCtaTest extends ServerGeneralParagraphTestBase {

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
    $cta = Paragraph::create(['type' => 'cta']);
    $cta->set('field_title', 'Lorem ipsum dolor sit amet');
    $cta->set('field_body', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
    $cta->set('field_link', [
      'uri' => 'https://example.com',
      'title' => 'Button text',
    ]);
    $cta->save();
    $this->markEntityForCleanup($cta);

    $user = $this->createUser();
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
      'uid' => $user->id(),
      'field_paragraphs' => [
        $this->getParagraphReferenceValues($cta),
      ],
    ]);
    $node->setPublished()->save();
    $this->assertEquals($user->id(), $node->getOwnerId());

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->assertSession()->elementTextContains('css', '.cta', 'Lorem ipsum dolor sit amet');
    $this->assertSession()->elementTextContains('css', '.cta', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
    $this->assertSession()->elementTextContains('css', '.cta', 'Button text');
    $this->assertSession()->linkExists('Button text');
    $this->assertSession()->linkByHrefExists('https://example.com');
  }

}
