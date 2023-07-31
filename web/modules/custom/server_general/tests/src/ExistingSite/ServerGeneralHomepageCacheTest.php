<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Tests for the Homepage caching.
 */
class ServerGeneralHomepageCacheTest extends ServerGeneralTestBase {

  /**
   * The homepage is cache-able.
   */
  public function testHomepageCache() {
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderEquals('Cache-Control', 'max-age=1800, public');
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderExists('X-Drupal-Cache', 'HIT');
  }

}
