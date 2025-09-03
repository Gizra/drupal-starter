<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'group' content type.
 */
class ServerGeneralNodeGroupTest extends ServerGeneralTestBase {

  /**
   * A test method for subscription text.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSubscriptionText() {
    $manager = $this->createUser();

    $group_title = 'Lorem Ipsum';
    // Create a group node.
    $node = $this->createNode([
      'title' => $group_title,
      'type' => 'group',
      'uid' => $manager->id(),
    ]);
    $this->assertEquals($manager->id(), $node->getOwnerId());

    // Visit the group.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    // Assertion for anonymous user.
    $expected = 'Hi, log in to subscribe to this group.';
    $this->assertSession()->pageTextContains($expected);

    $this->drupalLogin($manager);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    // Assertion for manager.
    $expected = 'You are the group manager.';
    $this->assertSession()->pageTextContains($expected);

    $this->drupalLogout();

    $visitor = $this->createUser();
    $this->drupalLogin($visitor);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    // Assertion for registered user.
    $expected = 'Hi ' . $visitor->getDisplayName() . ', click here if you would like to subscribe to this group called ' . $group_title . '.';
    $this->assertSession()->pageTextContains($expected);
  }

}
