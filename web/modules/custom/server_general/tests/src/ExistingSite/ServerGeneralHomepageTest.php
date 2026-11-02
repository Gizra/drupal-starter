<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Performance tests for the Homepage.
 */
class ServerGeneralHomepageTest extends ServerGeneralSelenium2TestBase {

  /**
   * Test homepage performance using the browser's Performance API.
   */
  public function testPerformance() {
    $this->drupalGet('<front>');

    $session = $this->getSession();
    // Wait for the page to fully load including all assets.
    $session->wait(5000, 'window.performance.timing.loadEventEnd > 0');

    // Get timing metrics from the Navigation Timing API.
    $timing = $session->evaluateScript(
      '(function() {' .
      '  var t = window.performance.timing;' .
      '  return {' .
      '    ttfb: t.responseEnd - t.navigationStart,' .
      '    domContentLoaded: t.domContentLoadedEventEnd - t.navigationStart,' .
      '    fullLoad: t.loadEventEnd - t.navigationStart' .
      '  };' .
      '})()'
    );
    $this->assertLessThan(5000, $timing['fullLoad'], "Full page load took {$timing['fullLoad']}ms.");

    // Count CSS and JS resources via the Resource Timing API.
    $resources = $session->evaluateScript(
      '(function() {' .
      '  var entries = window.performance.getEntriesByType("resource");' .
      '  var css = 0, js = 0;' .
      '  for (var i = 0; i < entries.length; i++) {' .
      '    if (entries[i].name.match(/\\.css/)) css++;' .
      '    if (entries[i].name.match(/\\.js/)) js++;' .
      '  }' .
      '  return {css: css, js: js};' .
      '})()'
    );
    $this->assertGreaterThan(0, $resources['css'], 'Page should load CSS assets.');
    $this->assertGreaterThan(0, $resources['js'], 'Page should load JS assets.');
  }

}
