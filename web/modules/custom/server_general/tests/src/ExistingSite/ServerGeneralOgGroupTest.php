<?php

declare(strict_types=1);


namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\UserInterface;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * An OG group model test case using traits from Drupal Test Traits.
 */
class ServerGeneralOgGroupTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected static array $modules = [
    'node',
    'og',
    'options',
    'pluggable_entity_view_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected string $defaultTheme = 'server_theme';

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $group;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected string $groupBundle;

  /**
   * A non-author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

  /**
   * An author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $author;


  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();

    // Create OG group node author user.
    $this->author = $this->createUser();

    // Create non-author user.
    $this->user = $this->createUser();

    // Set our custom entity type bundle name.
    $this->groupBundle = 'group';

    // Check if OG group entity type already exists or not.
    try {
      if ($content_type = \Drupal::entityTypeManager()
        ->getStorage('node_type')
        ->load($this->groupBundle)) {
        // Create node of our custom OG group type and set author.
        $this->group = $this->createNode([
          'type' => $this->groupBundle,
          'title' => $this->randomString(),
          'uid' => $this->author->id(),
        ]);

      }
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }

  }

  /**
   * Tests the OG group field formatter changes by user and membership.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testOgFormatter() {
    // Get assert session.
    $assert = $this->assertSession();

    // Get OG group node URL.
    $url = $this->group->toUrl();

    // Test OG group node URL.
    $assert->assert(!empty($url), 'OG group node URL is empty!');

    // Test contents of OG group field for OG group node author user.
    $this->drupalLogin($this->author);
    $this->drupalGet($url);
    $author_membership = Og::getMembership($this->group, $this->author, OgMembershipInterface::ALL_STATES);
    $assert->assert(!is_null($author_membership), 'Group author should be already a group member!');
    // This is default text shown to the group owner.
    // In the case of author user we don't have link.
    $assert->pageTextContains('You are the group manager');

    // Test for overridden OG group field text for logged-in user.
    $this->drupalLogin($this->user);
    // User shouldn't already be a member of the group.
    $user_membership = Og::getMembership($this->group, $this->user, OgMembershipInterface::ALL_STATES);
    $assert->assert(is_null($user_membership), 'Logged in user is already a member of the group!');
    $this->drupalGet($url);
    // Create string based on current user name and OG group name.
    $label = strtr('Hi @name, click here if you would like to subscribe to this group called @label', [
      '@name' => $this->user->getDisplayName(),
      '@label' => $this->group->getTitle(),
    ]);
    $assert->linkExists($label);

    // Anonymous user should still see original text from OG group field formatter.
    $this->drupalLogout();
    $this->drupalGet($url);
    $assert->linkExists('Request group membership');
  }

}
