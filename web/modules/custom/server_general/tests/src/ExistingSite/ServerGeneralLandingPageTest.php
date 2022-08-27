<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test Landing page functionality.
 */
class ServerGeneralLandingPageTest extends ExistingSiteBase {

  /**
   * Test the permissions and available paragraphs.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGeneral() {
    $paragraph_types = [
      'Views',
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
   * Tests 'Landing page' CT and Views paragraph type.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testViews() {
    $views = Paragraph::create(['type' => 'views']);
    $views->set('field_views', [
      'target_id' => 'news',
      'display_id' => 'embed',
    ]);
    $views->save();
    $this->markEntityForCleanup($views);

    $cta = Paragraph::create(['type' => 'cta']);
    $cta->set('field_title', 'Lorem ipsum dolor sit amet');
    $cta->set('field_subtitle', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
    $cta->set('field_link', [
      'uri' => 'https://example.com',
      'title' => 'Button text',
    ]);
    $cta->save();
    $this->markEntityForCleanup($cta);

    $user = $this->createUser();
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
      'uid' => $user->id(),
      'field_paragraphs' => [
        [
          'target_id' => $cta->id(),
          'target_revision_id' => $cta->getRevisionId(),
        ],
        [
          'target_id' => $views->id(),
          'target_revision_id' => $views->getRevisionId(),
        ],
      ],
    ]);
    $node->setPublished()->save();
    $this->assertEquals($user->id(), $node->getOwnerId());

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->elementExists('css', '.view-news');

    $this->assertSession()->elementTextContains('css', '.cta', 'Lorem ipsum dolor sit amet');
    $this->assertSession()->elementTextContains('css', '.cta', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
    $this->assertSession()->elementTextContains('css', '.cta', 'Button text');
    $this->assertSession()->linkExists('Button text');
    $this->assertSession()->linkByHrefExists('https://example.com');
  }

}
