<?php

namespace Drupal\Tests\server_group\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests for the Node Group Subscribe functionality.
 */
class NodeGroupSubscribeTest extends ExistingSiteBase {

    /**
     * The node group subscribe page is cache-able.
     */
    public function testNodeGroupSubscribe() {
        $node = $this->createNode([
            'type' => 'group',
            'title' => 'Test Group',
        ]);
        $user = $this->createUser(['name' => 'Alice']);
        $user->addRole('content_editor');
        $user->save();
        $this->drupalLogin($user);

        $this->drupalGet('/group/' . $node->id());
        $this->assertSession()->statusCodeEquals(200);
        $this->assertSession()->responseHeaderContains('X-Drupal-Cache', 'HIT');
    }

    /**
     * Test greeting and subscribe offer for registered user.
     */
    public function testGreetingAndSubscribeOffer() {
        $node = $this->createNode([
            'type' => 'group',
            'title' => 'Test Group',
        ]);
        $user = $this->createUser(['name' => 'Bob']);
        $user->addRole('content_editor');
        $user->save();
        $this->drupalLogin($user);

        $this->drupalGet('/group/' . $node->id());
        $this->assertSession()->pageTextContains('Hi Bob, click here if you would like to subscribe to this group called Test Group.');
    }

    /**
     * Test OG API permission check for subscription.
     */
    public function testOgApiPermissionCheck() {
        $node = $this->createNode([
            'type' => 'group',
            'title' => 'Test Group',
        ]);
        $user = $this->createUser(['name' => 'Charlie']);
        $user->addRole('content_editor');
        $user->save();
        $this->drupalLogin($user);

        // Simulate OG API denying subscription.
        \Drupal::service('og.membership_manager')->setUserCanSubscribe($user, $node, FALSE);

        $this->drupalGet('/group/' . $node->id());
        $this->assertSession()->pageTextNotContains('click here if you would like to subscribe');
    }

    /**
     * Test anonymous user does not get greeting or subscribe offer.
     */
    public function testAnonymousUserNoGreeting() {
        $node = $this->createNode([
            'type' => 'group',
            'title' => 'Test Group',
        ]);
        $this->drupalGet('/group/' . $node->id());
        $this->assertSession()->pageTextNotContains('join this group');
    }

}