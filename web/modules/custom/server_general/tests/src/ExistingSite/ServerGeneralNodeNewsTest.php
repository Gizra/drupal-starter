<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
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

  /**
   * Test the label above the title is taken from the content type.
   */
  public function testContentTypeLabel() {
    $node = $this->createNode([
      'title' => 'A node to check the label of',
      'type' => 'news',
      'field_body' => 'This is the text of the body field.',
      'moderation_state' => 'published',
    ]);

    $node_type = NodeType::load('news');
    $original_label = $node_type->label();
    $label = 'Bulletin ' . $this->randomMachineName();

    $node_type->set('name', $label)->save();

    try {
      foreach (['full', 'teaser', 'featured', 'search_index'] as $view_mode) {
        $this->assertStringContainsString(
          $label,
          $this->renderNode($node, $view_mode),
          "The $view_mode view mode shows the content type label."
        );
      }
    }
    finally {
      $node_type->set('name', $original_label)->save();
    }
  }

  /**
   * Render a node in a given view mode.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to render.
   * @param string $view_mode
   *   The view mode.
   *
   * @return string
   *   The rendered markup.
   */
  protected function renderNode(NodeInterface $node, string $view_mode): string {
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('node')
      ->view($node, $view_mode);

    return (string) \Drupal::service('renderer')->renderInIsolation($build);
  }

}
