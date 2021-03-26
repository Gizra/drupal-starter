<?php

namespace Drupal\Tests\server_general\ExistingSiteJavascript;

use weitzman\DrupalTestTraits\ExistingSiteWebDriverTestBase;

/**
 * A model test case using traits from Drupal Test Traits.
 */
class ServerGeneralSubscribeTest extends ExistingSiteWebDriverTestBase {

  protected $defaultTheme = 'server_theme';

  protected static $modules = [
    'user',
    'node',
    'og',
  ];

  /**
   * Test OG subscription via front-end link.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSubscribe() {
    $author = $this->createUser();

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'The node title',
      'uid' => $author->id(),
      'body' => [
        ['value' => 'The node body'],
      ],
    ]);
    $node->save();

    $viewer = $this->createUser();

    $this->drupalLogin($viewer);
    $this->assertNotEquals($viewer->id(), $node->getOwnerId());

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $link = $this->assertSession()->elementExists('css', 'a.og-subscribe');

    $link->click();
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = $this->assertSession()->buttonExists('Join');
    $submit_button->click();

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('You are now subscribed to the group.');
  }

}
