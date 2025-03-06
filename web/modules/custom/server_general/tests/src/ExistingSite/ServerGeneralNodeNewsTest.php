<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'news' content type.
 */
class ServerGeneralNodeNewsTest extends ServerGeneralNodeTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'news';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_body',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_featured_image',
      'field_tags',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testOgMetatags() {
    $author = $this->createUser();

    // Create News node with image.
    $node = $this->createNode([
      'title' => 'Test News',
      'type' => 'news',
      'uid' => $author->id(),
      'field_body' => 'This is the text of the body field.',
      'field_featured_image' => ['target_id' => 1],
      'moderation_state' => 'published',
    ]);
    $node->save();

    // We can browse pages.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->elementExists('css', 'meta[property="og:title"]');
    $this->assertSession()->elementExists('css', 'meta[property="og:image"]');

    $metaTag = $this->assertSession()->elementExists('css', 'meta[property="og:description"]');
    $this->assertEquals(
      'This is the text of the body field.',
      $metaTag->getAttribute('content'),
      'The og:description meta tag contains the exact expected string.'
    );

  }

}
