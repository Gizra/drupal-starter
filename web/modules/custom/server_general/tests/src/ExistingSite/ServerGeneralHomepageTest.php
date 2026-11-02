<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\PerformanceTestTrait;

/**
 * Tests for the Homepage.
 */
class ServerGeneralHomepageTest extends ServerGeneralSelenium2TestBase {

  use PerformanceTestTrait;

  /**
   * Performance tests with open telemetry.
   */
  public function testPerformance() {
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'serverGeneralFrontPageColdCache');
    $this->assertSame(5, $performance_data->getStylesheetCount());
    $this->assertSame(1, $performance_data->getScriptCount());
  }

}
