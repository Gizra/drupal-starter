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

}
