<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\Traits;

/**
 * Automatically captures page HTML when a test fails.
 *
 * Relies purely on DTT's DrupalTrait::capturePageContent().
 * Requires DTT_HTML_OUTPUT_DIRECTORY to be set (e.g. in phpunit.xml.dist).
 */
trait FailureHtmlCaptureTrait {

  /**
   * {@inheritdoc}
   */
  protected function onNotSuccessfulTest(\Throwable $t): void {
    // capturePageContent() is provided by DTT's DrupalTrait.
    // It writes an HTML file to DTT_HTML_OUTPUT_DIRECTORY.
    if (method_exists($this, 'capturePageContent')) {
      try {
        $this->capturePageContent('failure');
      }
      catch (\Throwable) {
        // Silently ignore capture errors — always re-throw the original.
      }
    }
    parent::onNotSuccessfulTest($t);
  }

}
