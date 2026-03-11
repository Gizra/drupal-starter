<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\Entity\MediaCreationTrait;

/**
 * Test for Translator role.
 */
class ServerGeneralTranslatorTest extends ServerGeneralTestBase {

  use MediaCreationTrait;

  /**
   * The translator user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translator;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $user = $this->createUser();
    $user->addRole('translator');
    $user->save();
    $this->drupalLogin($user);
  }

  /**
   * The translator access to content types.
   */
  public function testNode() {
    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'landing_page',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    $this->drupalGet(sprintf("node/%s/translations", $node->id()));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

  /**
   * The translator access to Taxonomy terms.
   */
  public function testTaxonomy() {
    $tags = Vocabulary::load('tags');
    $term = $this->createTerm($tags, [
      'langcode' => 'en',
      'name' => 'Test',
    ]);

    $this->drupalGet(sprintf("taxonomy/term/%s/translations", $term->id()));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->drupalGet('/admin/structure/taxonomy/manage/tags/add');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * The translator access to Media items.
   */
  public function testMedia() {
    $media = $this->createMedia([
      'bundle' => 'image',
    ]);

    $this->drupalGet(sprintf("media/%s/edit/translations", $media->id()));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->drupalGet('admin/content/media');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->drupalGet('media/add/image');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

}
