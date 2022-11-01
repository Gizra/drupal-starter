<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'group' content type.
 */
class ServerGeneralNodeGroupPageTest extends ServerGeneralNodeTestBase {

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
    return [];
  }

  /**
   * Test the Group setup.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGroupSetup() {
    $assert = $this->assertSession();
    // Login as a content editor.
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);
    $this->drupalGet("/node/add/group");
    // Title field exists.
    $assert->elementExists('css', '.field--name-title');
    $assert->buttonExists("Save");
  }

  /**
   * Tests The subscribe flow.
   */
  public function testSubcribeFlow() {
    $user = $this->createUser();
    $node = $this->createNode([
      'title' => 'Test Group',
      'type' => 'group',
      'uid' => $user->id(),
    ]);
    $node->setPublished()->save();
    $this->assertEquals($user->id(), $node->getOwnerId());

    $authenticated_user = $this->createUser();
    $this->drupalLogin($authenticated_user);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Validate subscribe link and Text.
    $suscribe_text = 'Hi ' . $authenticated_user->getDisplayName() . ', click here if you would like to subscribe to this group called ' . $node->label();
    $this->assertSession()->linkExists($suscribe_text);
    $this->assertSession()->linkByHrefExists('/group/node/' . $node->id() . '/subscribe');
  }

}
