<?php

namespace Drupal\Tests\og_custom\ExistingSite;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og_custom\Plugin\Field\FieldFormatter\CustomSubscribeMessageFormatter;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests subscribe and un-subscribe formatter.
 *
 * @group og
 */
class GroupSubscribeFormatterTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og', 'og_custom'];

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected $groupBundle = 'my_group';

  /**
   * A non-author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();


    // Create node author user.
    $user = $this->createUser();

    // Create groups.
    $this->group = Node::create([
      'type' => $this->groupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group->save();

    $this->user1 = $this->createUser();
    $this->user2 = $this->createUser();
  }

  /**
   * Tests the custom formatter changes.
   */
  public function testFormatter() {
    $this->drupalLogin($this->user1);

    // Subscribe to group.
    $this->drupalGet('node/' . $this->group->id());

    // check if the link exists
    // for this we need to reformat the link text from current settings
    $config = \Drupal::service('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.my_group.default')
      ->getRenderer('og_group');

   $settings = $config->getSettings();
    $tokens = [
      '%user' => $this->user1->getDisplayName(),
      '%group' => $this->group->label(),
    ];
    $subscribe_message = CustomSubscribeMessageFormatter::tokenizeMessage($settings['subscribe_message'], $tokens);

    $this->assertSession()->linkExists($subscribe_message);

    // Anonymous users don't see the custom link.
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->group->id());
    $this->assertSession()->linkNotExists($subscribe_message);
  }

}
