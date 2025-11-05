<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for status messages display.
 */
class ServerGeneralMessagesTest extends ServerGeneralTestBase {

  /**
   * Test that status messages are displayed when saving a node.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testNodeSaveMessage() {
    // Create a landing page node to edit.
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
    ]);

    // Login as admin.
    $user = $this->createUser();
    $user->addRole('administrator');
    $this->drupalLogin($user);

    // Visit the node edit form.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->createHtmlSnapshot();
    $assert = $this->assertSession();
    $assert->statusCodeEquals(Response::HTTP_OK);

    // Change the title.
    $this->getCurrentPage()->fillField('Title', 'Updated Landing Page Title');

    // Save the node.
    $this->getCurrentPage()->pressButton('Save');

    // Assert that the messages container exists.
    $assert->elementExists('css', '[data-drupal-messages]');

    // Assert that the success message appears with proper role and aria-label.
    $assert->elementExists('css', '[role="contentinfo"][aria-label="Status message"]');

    // Assert the messages--status class exists.
    $assert->elementExists('css', '.messages--status');
  }

}
