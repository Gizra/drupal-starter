<?php

namespace Drupal\Tests\server_general\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test 'group' content type.
 */
class ServerNodeGroupTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'og'];

  /**
   * The test group node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a group with an author.
    $author = $this->createUser();
    $this->group = $this->createNode([
      'type' => 'group',
      'title' => 'Test Group ' . $this->randomMachineName(),
      'uid' => $author->id(),
    ]);
    $this->group->save();

    $this->user1 = $this->createUser();
  }

  /**
   * Test that a user can subscribe to a group.
   */
  public function testNodeGroupSubscribe(): void {
    // Visit group page as anonymous user.
    $this->drupalGet($this->group->toUrl());
    $this->assertSession()->linkExists('Login to subscribe');
    $this->clickLink('Login to subscribe');
    $this->assertSession()->addressEquals('user/login');

    // Log in as another user.
    $this->drupalLogin($this->user1);

    // Visit the group page again.
    $this->drupalGet($this->group->toUrl());

    $expected_link_text = sprintf(
      'Hi %s, click here if you would like to subscribe to this group called %s',
      $this->user1->getDisplayName(),
      $this->group->label()
    );

    // Assert subscribe link exists.
    $this->assertSession()->linkExists($expected_link_text);

    // Click the subscribe link.
    $this->clickLink($expected_link_text);
    $this->click('#edit-submit');

    // Visit again and confirm unsubscribe is visible.
    $this->drupalGet($this->group->toUrl());
    $this->assertSession()->linkExists('Unsubscribe from group');
  }

}
