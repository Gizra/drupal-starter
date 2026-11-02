<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\Response;

/**
 * A model test case using traits from Drupal Test Traits.
 */
class ServerGeneralExampleTest extends ServerGeneralTestBase {

  /**
   * An example test method; note that Drupal API's and Mink are available.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testNews() {
    // Creates a user. Will be automatically cleaned up at the end of the test.
    $author = $this->createUser();

    // Create a taxonomy term. Will be automatically cleaned up at the end of
    // the test.
    $vocab = Vocabulary::load('tags');
    $term = $this->createTerm($vocab);

    // Create a "Llama" article. Will be automatically cleaned up at end of
    // test.
    $node = $this->createNode([
      'title' => 'Llama',
      'type' => 'news',
      'field_tags' => [
        'target_id' => $term->id(),
      ],
      'uid' => $author->id(),
      'moderation_state' => 'published',
    ]);
    $this->assertEquals($author->id(), $node->getOwnerId());

    // We can browse pages.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // We can login and browse admin pages.
    $this->drupalLogin($author);
    $this->drupalGet($node->toUrl('edit-form'));
  }

}
