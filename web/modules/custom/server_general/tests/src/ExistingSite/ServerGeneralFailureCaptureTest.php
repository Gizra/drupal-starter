<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\FailureHtmlCaptureTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Demonstrates automatic HTML capture on test failure via FailureHtmlCaptureTrait.
 *
 * On failure, the current page HTML is saved to DTT_HTML_OUTPUT_DIRECTORY
 * and uploaded as a GitHub Actions artifact for inspection.
 */
class ServerGeneralFailureCaptureTest extends ExistingSiteBase {

  use FailureHtmlCaptureTrait;

  /**
   * Verifies the site front page returns a 200 response.
   */
  public function testFrontPageLoads(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Intentionally fails to validate HTML capture + CI artifact upload.
   *
   * Remove this method after confirming the artifact appears in GitHub Actions.
   */
  public function testIntentionalFailureForArtifactValidation(): void {
    $this->drupalGet('<front>');
    // This assertion is intentionally wrong — triggers FailureHtmlCaptureTrait
    // to save the page HTML and upload it as a CI artifact.
    $this->assertSession()->pageTextContains('THIS TEXT DOES NOT EXIST ON THE PAGE');
  }

}
