<?php

namespace Drupal\Tests\server_general\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Basic Seckit tests.
 */
class SeckitTest extends ExistingSiteBase {

  /**
   * Check HSTS is correctly enabled.
   */
  public function testHsts() {
    $this->drupalGet('/user');
    $this->assertSession()->responseHeaderEquals('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
  }

}
