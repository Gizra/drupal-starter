<?php

namespace Drupal\Tests\og_entity_view_builder;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

class OgEvbNodeGroupTest extends ExistingSiteBase {

  /**
   * The group node.
   */
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $node = $this->createNode([
      'title' => 'Test Group',
      'type' => 'group',
      'body' => 'This is the text of the body field.',
    ]);
    $node->save();

    $this->node = $node;
  }

  /**
   * Test page content displayed to anonymous users.
   */
  public function testAnonymousUser(): void {
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->_titleAndBodyTest();

    $link = $this->assertSession()->elementExists('css', 'article a');
    $this->assertEquals('Login to subscribe to this group', $link->getText());
    $url = Url::fromRoute('user.login', [], ['query' => ['destination' => $this->node->toUrl()->getInternalPath()]])->toString();
    $this->assertEquals($url, $link->getAttribute('href'));
  }

  /**
   * Test page content displayed to authenticated users.
   */
  public function testAuthenticatedUser(): void {
    $username = 'auth-user';
    $account = $this->createUser([], $username);
    $this->drupalLogin($account);

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->_titleAndBodyTest();

    $group_title = $this->node->getTitle();
    $link = $this->assertSession()->elementExists('css', 'article a');
    $this->assertEquals("Hi $username, click here if you would like to subscribe to this group called $group_title", $link->getText());
    $url = Url::fromRoute('og.subscribe', [
      'entity_type_id' => $this->node->getEntityTypeId(),
      'group' => $this->node->id()
    ])->toString();
    $this->assertEquals($url, $link->getAttribute('href'));
  }

  /**
   * Test page content displayed to subscribed users.
   */
  public function testSubscribedUser(): void {
    $username = 'subscribed-user';
    $account = $this->createUser([], $username);

    $membership = Og::createMembership($this->node, $account);
    $membership->save();

    $this->drupalLogin($account);

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->_titleAndBodyTest();

    $this->assertSession()->elementNotExists('css', 'article a');
  }

  protected function _titleAndBodyTest(): void {
    $page_title = $this->assertSession()->elementExists('css', '.page-title');
    $this->assertEquals('Test Group', $page_title->getText());

    $body_text = $this->assertSession()->elementExists('css', '.field--name-body');
    $this->assertEquals('This is the text of the body field.', $body_text->getText());
  }

}
