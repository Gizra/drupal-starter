<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Tests for the Homepage.
 */
class ServerGeneralHomepageTest extends ServerGeneralSelenium2TestBase {

  /**
   * Test the featured content carousel on homepage.
   */
  public function testHomeFeaturedContent() {
    $this->drupalGet('<front>');
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $web_assert */
    $web_assert = $this->assertSession();

    $featured_content = $web_assert->waitForElement('css', '.paragraph--type--related-content');
    $this->assertNotNull($featured_content);
    $web_assert->elementTextContains('css', '.paragraph--type--related-content h2', 'Featured Content');
    // Slick is initialized.
    $carousel = $web_assert->waitForElement('css', '.paragraph--type--related-content .carousel-wrapper.slick-initialized');
    $this->assertNotNull($carousel);

    // Assert the current slide has the expected title.
    $web_assert->elementTextContains('css', '.paragraph--type--related-content .carousel-slide.slick-current', 'Current Digital Marketing Is Sports-Watching, Rather Than Marketing');
    // Click on the 2nd dot navigation.
    $dots_2 = $carousel->find('css', '.slick-dots li:nth-child(2)');
    $this->assertNotNull($dots_2);
    $dots_2->click();
    // Wait half sec for the JS and animation.
    $this->getSession()->wait(500);
    // Assert that the active slide is now different.
    $web_assert->elementTextContains('css', '.paragraph--type--related-content .carousel-slide.slick-current', 'Pandemic Moves Education Online');
  }

  /**
   * Test the permissions and available paragraphs.
   */
  public function testAddParagraph() {
    $assert = $this->assertSession();
    // Login as a content editor.
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);
    $this->drupalGet("/node/add/landing_page");

    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $web_assert */
    $web_assert = $this->assertSession();

    $add_pagraph_button = $assert->elementExists('css', '.paragraph-type-add-modal-button');
    $this->assertNotNull($add_pagraph_button);
    $add_pagraph_button->click();

    $paragraph_types = [
      'Accordion',
      'Call to action',
      'Documents',
      'Form',
      'Hero image',
      'Info cards',
      'News teasers',
      'People teasers',
      'Quick links',
      'Quote',
      'Related content',
      'Search',
      'Text',
    ];
    // Wait half sec for the JS and animation.
    $paragraphs = $web_assert->waitForElement('css', '.paragraphs-ee-add-dialog');
    $paragraphs_items = $paragraphs->findAll('css', '.paragraphs-label');
    foreach ($paragraphs_items as $label) {
      $this->assertContains($label->getText(), $paragraph_types);
    }
  }

}
