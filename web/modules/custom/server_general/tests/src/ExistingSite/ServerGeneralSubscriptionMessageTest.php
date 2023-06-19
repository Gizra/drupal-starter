<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\og\Entity\OgMembership;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * A model test case using traits from Drupal Test Traits.
 */
class ServerGeneralSubscriptionMessageTest extends ExistingSiteBase {

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $groupNode;

  /**
   * Test normal user with no connection to the organic group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * Test member of the organic group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $member;

  /**
   * An example test method; note that Drupal API's and Mink are available.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testGroupSubscriptionAuthenticated() {
    // Test if authenticated user can see the message.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($this->groupNode->toUrl());
    $this->assertSession()->pageTextContains('click here if you would like to subscribe to this group called "Test Group"');
  }

  public function testGroupSubscriptionMember() {
    // Test if authenticated user can't see the message.
    $this->drupalLogin($this->member);
    $this->drupalGet($this->groupNode->toUrl());
    $this->assertSession()->pageTextNotContains('click here if you would like to subscribe to this group called "Test Group"');
  }

  public function testGroupSubscriptionAnonymous() {
    // Test if anonymous user can not see the message.
    $this->drupalGet($this->groupNode->toUrl());
    $this->assertSession()->pageTextNotContains('click here if you would like to subscribe to this group called "Test Group"');
  }

  protected function setUp() {
    parent::setUp();
    // Creates a user. Will be automatically cleaned up at the end of the test.
    $author = $this->createUser();

    // Create a Group. Will be automatically cleaned up at end of test.
    $this->groupNode = $this->createNode([
      'title' => 'Test Group',
      'type' => 'group',
      'field_body' => [
        'value' => "Ex laoreet pala pneum saluto vicis.",
      ],
      'uid' => $author->id(),
    ]);

    // Create an authenticated user.
    $this->normalUser = $this->createUser([], 'NormalUser');

    // Create a member of the group.
    $this->member = $this->createUser();
    $membership = OgMembership::create();
    $membership
      ->setOwner($this->member)
      ->setGroup($this->groupNode)
      ->save();
  }
}
