<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test the locked pages functionality.
 */
class ServerGeneralLockedPagesTest extends ServerGeneralTestBase {

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
      // Fail if exception is not thrown on line above.
      $this->fail('Expected locked pages deletion exception not thrown.');
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
    $user->addRole('content_editor');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet($homepage->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()
      ->linkByHrefNotExists("/node/{$homepage->id()}/delete");
    $this->assertSession()->elementNotExists('css', 'a#edit-delete');
    $this->assertSession()->elementNotExists('css', 'input#edit-status-value');

    $this->drupalGet("/node/{$homepage->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    $this->drupalGet($homepage->toUrl());
    $this->assertSession()
      ->linkByHrefNotExists("/node/{$homepage->id()}/delete");

    $this->drupalGet('/admin/content');
    $this->assertSession()
      ->linkByHrefNotExists("/node/{$homepage->id()}/delete");
  }

  /**
   * Test a general locked landing page.
   */
  public function testLockedLandingPage() {
    $user = $this->createUser();
    $user->addRole('content_editor');
    $user->save();
    $this->drupalLogin($user);

    // Check not locked page for admin.
    $node = $this->createNode([
      'title' => 'Not locked page',
      'type' => 'landing_page',
      'moderation_state' => 'published',
    ]);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->linkByHrefExists("/node/{$node->id()}/delete");

    $this->drupalGet("/node/{$node->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Make page locked.
    $main_settings = $this->loadOrCreateConfigPages('main_settings');

    $old_value = $main_settings->get('field_locked_pages')->getValue();

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

    $user->addRole('translator');
    $user->save();

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

    // Restore old locked pages value so the created node can be deleted.
    $main_settings->set('field_locked_pages', $old_value);
    $main_settings->save();
  }

  /**
   * Create or load a config pages entity.
   *
   * @param string $config_pages_id
   *   The ID of the config pages entity.
   *
   * @return \Drupal\config_pages\ConfigPagesInterface
   *   The config_pages entity.
   */
  protected function loadOrCreateConfigPages(string $config_pages_id) {
    // We try to load config_pages of type "main_settings".
    $config_pages_storage = \Drupal::service('config_pages.loader');
    /** @var \Drupal\config_pages\Entity\ConfigPages|null $config_pages */
    $config_pages = $config_pages_storage->load($config_pages_id);

    if (!empty($config_pages)) {
      return $config_pages;
    }

    // Create a new config page.
    $type = ConfigPagesType::load($config_pages_id);
    $config_pages = ConfigPages::create([
      'type' => $config_pages_id,
      'context' => $type->getContextData(),
    ]);
    $config_pages->save();
    $this->markEntityForCleanup($config_pages);

    return $config_pages;
  }

}
