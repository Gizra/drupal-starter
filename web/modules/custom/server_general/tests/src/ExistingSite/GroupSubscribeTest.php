<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test group subscription functionality.
 */
class GroupSubscribeTest extends ServerGeneralTestBase {

  /**
   * Test that a user can subscribe to a Group node.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testGroupSubscription() {
    // Create a registered user.
    $user = $this->createUser();
    
    // Create a Group node.
    $group_node = $this->createNode([
      'type' => 'group',
      'title' => 'Test Group',
      'status' => 1,
    ]);
    
    // Check group page as anonymous user.
    $this->drupalGet($group_node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextContains('Test Group');
    
    // Log in as the registered user.
    $this->drupalLogin($user);
    
    // Visit group page again.
    $this->drupalGet($group_node->toUrl());
    
    // User should see the subscription message.
    $expected_message = "Hi {$user->getDisplayName()}, click here if you would like to subscribe to this group called Test Group";
    $this->assertSession()->pageTextContains($expected_message);
    
    // Click the subscribe link.
    $this->clickLink('here');
    
    // After subscribing, we should be redirected back to group page.
    $this->assertSession()->addressEquals($group_node->toUrl()->toString());
    
    // And we should see the confirmation message.
    $this->assertSession()->pageTextContains('You have successfully subscribed to the group.');
    
    // Reload the group page → the subscription message should no longer appear.
    $this->drupalGet($group_node->toUrl());
    $this->assertSession()->pageTextNotContains('click here if you would like to subscribe');
    
    // Verify the user is actually a member now using OG API.
    $membership_manager = \Drupal::service('og.membership_manager');
    $this->assertTrue(
      $membership_manager->isMember($group_node, $user->id()),
      'User should be a member of the group after subscribing.'
    );
  }

  /**
   * Test that existing members don't see subscription message.
   */
  public function testExistingMemberNoSubscriptionMessage() {
    // Create a user.
    $user = $this->createUser();
    
    // Create a Group node.
    $group_node = $this->createNode([
      'type' => 'group',
      'title' => 'Existing Member Group',
      'status' => 1,
    ]);
    
    // Make the user a member of the group programmatically.
    $membership_manager = \Drupal::service('og.membership_manager');
    $membership = $membership_manager->createMembership($group_node, $user);
    $membership->save();
    
    // Log in as the user.
    $this->drupalLogin($user);
    
    // Visit group page.
    $this->drupalGet($group_node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    
    // User should NOT see the subscription message since they're already a member.
    $this->assertSession()->pageTextNotContains('click here if you would like to subscribe');
    
    // Visit the subscribe URL directly - should see "already a member" message.
    $this->drupalGet("/group/{$group_node->id()}/subscribe");
    $this->assertSession()->pageTextContains('You are already a member of this group.');
  }

  /**
   * Test that anonymous users cannot subscribe.
   */
  public function testAnonymousUserCannotSubscribe() {
    // Create a Group node.
    $group_node = $this->createNode([
      'type' => 'group',
      'title' => 'Anonymous Test Group',
      'status' => 1,
    ]);
    
    // Try to access subscribe URL as anonymous user.
    $this->drupalGet("/group/{$group_node->id()}/subscribe");
    $this->assertSession()->pageTextContains('You must be logged in to subscribe to a group.');
  }

  /**
   * Test subscription with insufficient permissions.
   */
  public function testSubscriptionWithInsufficientPermissions() {
    // Create a user with limited permissions.
    $user = $this->createUser(['access content']);
    
    // Create a Group node.
    $group_node = $this->createNode([
      'type' => 'group',
      'title' => 'Permission Test Group',
      'status' => 1,
    ]);
    
    // Log in as the limited user.
    $this->drupalLogin($user);
    
    // Check if user has permission to subscribe using OG API.
    $og_access = \Drupal::service('og.access');
    $can_subscribe = $og_access->userAccess($group_node, 'subscribe', $user)->isAllowed();
    
    if (!$can_subscribe) {
      // Visit group page - should not see subscription message.
      $this->drupalGet($group_node->toUrl());
      $this->assertSession()->pageTextNotContains('click here if you would like to subscribe');
      
      // Try to access subscribe URL directly - should see permission error.
      $this->drupalGet("/group/{$group_node->id()}/subscribe");
      $this->assertSession()->pageTextContains('You do not have permission to subscribe to this group.');
    }
    else {
      // If user can subscribe, test should behave like normal subscription test.
      $this->drupalGet($group_node->toUrl());
      $expected_message = "Hi {$user->getDisplayName()}, click here if you would like to subscribe to this group called Permission Test Group";
      $this->assertSession()->pageTextContains($expected_message);
    }
  }
}