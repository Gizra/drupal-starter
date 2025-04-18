<?php

namespace Drupal\Tests\home_assignment\ExistingSite;

use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * A model test case using traits from Drupal Test Traits.
 */
class HomeAssignmentTest extends ExistingSiteBase
{

  /**
   * A test to check if user can access the node's group subscription link;
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testUserCanSeeNodeGroupSubscriptionLink()
  {
    // Creates a user. Will be automatically cleaned up at the end of the test.
    $author = $this->createUser();

    // Create a "Llama" group. Will be automatically cleaned up at end of
    // test.
    $node = $this->createNode([
      'title' => 'Llama',
      'type' => 'group',
      'uid' => $author->id(),
      'moderation_state' => 'published',
    ]);
    $this->assertEquals($author->id(), $node->getOwnerId());

    // We can browse pages.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // We can login and browse admin pages.
    $this->drupalLogin($author);
    $node_html = $this->drupalGet($node->toUrl('canonical'));

    $this->assertStringNotContainsString(
      '<a href="/group/node/' . $node->id() . '/subscribe">subscribe</a>',
      $node_html,
      'The subscribe link should not be present in the node HTML.'
    );
  }

}
