<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\node\Entity\NodeType;
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
   * Test scheduler fields are hidden on locked pages.
   */
  public function testSchedulerOnLockedPage(): void {
    // Install the scheduler module.
    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['scheduler']);

    // Enable scheduler for Landing Page content type.
    $node_type = NodeType::load('landing_page');
    $node_type
      ->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Enable scheduler fields in the form display.
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'landing_page')
      ->setComponent('publish_on', ['type' => 'datetime_timestamp_no_default'])
      ->setComponent('unpublish_on', ['type' => 'datetime_timestamp_no_default'])
      ->save();

    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);

    // Create a non-locked page.
    $node = $this->createNode([
      'title' => 'Testing scheduler on unlocked page',
      'uid' => $user->id(),
      'type' => 'landing_page',
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Scheduler options should be visible on unlocked pages.
    $this->assertSession()->pageTextContains('Scheduling options');
    $this->assertSession()->elementExists('css', '.scheduler-form');

    // Lock the page.
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

    // Scheduler options should be hidden on locked pages.
    $this->assertSession()->pageTextNotContains('Scheduling options');
    $this->assertSession()->elementNotExists('css', '.scheduler-form');

    // Clean up: remove the locked page entry.
    $main_settings->get('field_locked_pages')->removeItem(1);
    $main_settings->save();
  }

}
