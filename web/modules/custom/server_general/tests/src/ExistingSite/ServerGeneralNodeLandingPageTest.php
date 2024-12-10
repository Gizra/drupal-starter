<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'landing_page' content type.
 */
class ServerGeneralNodeLandingPageTest extends ServerGeneralNodeTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'landing_page';
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
      'field_is_title_hidden',
      'field_paragraphs',
    ];
  }

  /**
   * Test the permissions and available paragraphs.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGeneral() {
    $paragraph_types = [
      'Hero image',
      'Related content',
      'Search',
      'Text',
      'News teasers',
    ];

    $assert = $this->assertSession();
    // Login as a content editor.
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);
    $this->drupalGet("/node/add/landing_page");
    // Paragraph wrapper exists.
    $assert->elementExists('css', '.field--name-field-paragraphs');
    foreach ($paragraph_types as $type) {
      $assert->buttonExists("Add {$type}");
    }
  }

  /**
   * Test locked Homepage can't be deleted.
   */
  public function testLockedHomepage() {
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadByProperties([
      'title' => 'Homepage',
      'type' => 'landing_page',
    ]);

    /** @var \Drupal\node\NodeInterface $homepage */
    $homepage = reset($nodes);

    try {
      $homepage->delete();
    }
    catch (\Exception $exception) {
      $this->assertEquals("This node is locked and can't be removed", $exception->getMessage());
    }

    $this->drupalGet($homepage->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    $homepage->setUnpublished();
    $homepage->save();

    $this->assertEquals(TRUE, $homepage->isPublished());

    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet($homepage->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->linkByHrefNotExists("/node/{$homepage->id()}/delete");
    $this->assertSession()->elementNotExists('css', 'a#edit-delete');
    $this->assertSession()->elementNotExists('css', 'input#edit-status-value');

    $this->drupalGet("/node/{$homepage->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    $this->drupalGet($homepage->toUrl());
    $this->assertSession()->linkByHrefNotExists("/node/{$homepage->id()}/delete");

    $this->drupalGet('/admin/content');
    $this->assertSession()->linkByHrefNotExists("/node/{$homepage->id()}/delete");

    // Check not locked page for admin.
    $node = $this->createNode([
      'title' => 'Not locked page',
      'uid' => $user->id(),
      'type' => 'landing_page',
      'moderation_state' => 'published',
    ]);

    $node->setPublished()->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->linkByHrefExists("/node/{$node->id()}/delete");

    $this->drupalGet("/node/{$node->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Make page locked.
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\config_pages\ConfigPagesStorage $config_pages_storage */
    $config_pages_storage = $entity_type_manager->getStorage('config_pages');
    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $main_settings */
    $main_settings = $config_pages_storage->load('main_settings');

    $main_settings->get('field_locked_pages')->appendItem(['target_id' => $node->id()]);
    $main_settings->save();

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->linkByHrefNotExists("/node/{$node->id()}/delete");

    $this->drupalGet("/node/{$node->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Test translations.
    $node_es = $node->addTranslation('es', $node->toArray());
    $node_es->setTitle('Not locked page ES');
    $node_es->save();

    $this->drupalGet("/node/{$node->id()}/translations");
    $this->assertSession()->pageTextContains('Translations of');
    $this->assertSession()->elementTextNotContains('css', 'table', 'Delete');

    $this->drupalGet($node_es->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    // "Delete translation" button shouldn't exists if page is locked.
    $this->assertSession()->elementNotExists('css', '#edit-delete-translation');

    // Check locked node for anonymous.
    $this->drupalLogout();

    $this->drupalGet("/node/{$node->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    $main_settings->set('field_locked_pages', ['target_id' => $homepage->id()]);
    $main_settings->save();

    // Check not locked node for anonymous.
    $this->drupalGet("/node/{$node->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

}
