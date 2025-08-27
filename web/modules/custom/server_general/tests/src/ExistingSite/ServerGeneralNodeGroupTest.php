<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'group' content type and subscription functionality.
 */
class ServerGeneralNodeGroupTest extends ServerGeneralNodeTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'group';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'body',
    ];
  }

  /**
   * Test subscription messages for different user states.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testGroupSubscriptionMessages() {
    // Create a group owner.
    $group_owner = $this->createUser();

    // Create a group node.
    $group = $this->createNode([
      'title' => 'Test Group',
      'type' => 'group',
      'uid' => $group_owner->id(),
      'moderation_state' => 'published',
    ]);
    $group->body = [['#value' => 'This is a sample body content']];
    $group->save();

    // Ensure the group is actually recognized as an OG group.
    $this->assertTrue(Og::isGroup('node', $group->bundle()));

    // Test 1: Anonymous user should not see subscription message.
    $this->drupalGet($group->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextNotContains('Hi ');
    $this->assertSession()->pageTextNotContains('click here');
    $this->assertSession()->pageTextNotContains('subscribe to this group');
    $this->assertSession()->elementNotExists('css', '.group-subscription-message');

    // Test 2: Registered user (non-member) should see subscription invitation.
    $regular_user = $this->createUser();
    $this->drupalLogin($regular_user);
    $this->drupalGet($group->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $user_display_name = $regular_user->getDisplayName();
    $this->assertSession()->pageTextContains("Hi {$user_display_name}");
    $this->assertSession()->pageTextContains('click here');
    $this->assertSession()->pageTextContains('subscribe to this group called Test Group');
    $this->assertSession()->elementExists('css', '.group-subscription-message');

    // Test 3: User with pending membership should see pending review message.
    $pending_user = $this->createUser();

    // Create a pending membership.
    $membership = OgMembership::create([
      'type' => 'default',
      'uid' => $pending_user->id(),
      'entity_type' => 'node',
      'entity_id' => $group->id(),
      'state' => OgMembershipInterface::STATE_PENDING,
    ]);
    $membership->save();

    $this->drupalLogin($pending_user);
    $this->drupalGet($group->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextContains('Your membership request is pending review.');
    $this->assertSession()->elementExists('css', '.group-subscription-message');

    // Should not see subscription invitation.
    $pending_user_display_name = $pending_user->getDisplayName();
    $this->assertSession()->pageTextNotContains("Hi {$pending_user_display_name}");
    $this->assertSession()->pageTextNotContains('click here');

    // Test 4: Active member should see membership confirmation.
    $member_user = $this->createUser();

    // Create an active membership.
    $active_membership = OgMembership::create([
      'type' => 'default',
      'uid' => $member_user->id(),
      'entity_type' => 'node',
      'entity_id' => $group->id(),
      'state' => OgMembershipInterface::STATE_ACTIVE,
    ]);
    $active_membership->save();

    $this->drupalLogin($member_user);
    $this->drupalGet($group->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextContains('You are a member of this group.');
    $this->assertSession()->elementExists('css', '.group-subscription-message');

    // Should not see subscription invitation.
    $member_user_display_name = $member_user->getDisplayName();
    $this->assertSession()->pageTextNotContains("Hi {$member_user_display_name}");
    $this->assertSession()->pageTextNotContains('click here');
    $this->assertSession()->pageTextNotContains('subscribe to this group');

    // Test 5: Group owner should see membership confirmation (they're auto-members).
    $this->drupalLogin($group_owner);
    $this->drupalGet($group->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->pageTextContains('You are a member of this group.');
    $this->assertSession()->elementExists('css', '.group-subscription-message');
  }
}
