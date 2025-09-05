<?php

namespace Drupal\Tests\server_style_guide\ExistingSite;

use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * A test case to test the Style Guide.
 */
class ServerStyleGuidePageTest extends ExistingSiteBase {

  /**
   * Test Style guide.
   */
  public function testStyleGuide() {
    $this->drupalGet('/style-guide');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}
