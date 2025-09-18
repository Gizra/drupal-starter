<?php

namespace Drupal\Tests\server_general\ExistingSite;

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

}
