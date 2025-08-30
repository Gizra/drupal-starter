<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'group' content type & OG subscribe message.
 */
class ServerGeneralNodeGroupTest extends ServerGeneralTestBase {

  /**
   * {@inheritdoc}
   */
  public function testSubscribeMessage() {
    $user = $this->createUser();

    // Create Group node.
    $node = $this->createNode([
      'title' => 'Drupal WOWs',
      'type' => 'group',
      'uid' => 1,
      'body' => 'Brief, Concise and short but practical demos of some of the powerful no-code features of Drupal.',
      'moderation_state' => 'published',
    ]);
    $node->save();

    // Visit as anonymous.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementNotExists('css', 'em.subscribe-msg');

    // Visit as authenticated.
    $this->drupalLogin($user);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->elementExists('css', 'em.subscribe-msg');
  }

}
