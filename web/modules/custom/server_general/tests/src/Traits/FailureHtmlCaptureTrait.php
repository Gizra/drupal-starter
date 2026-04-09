<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\Traits;

/**
 * Automatically captures page HTML when a test fails.
 *
 * Captures page content during tearDown (before the Mink session is stopped),
 * then writes it to DTT_HTML_OUTPUT_DIRECTORY in onNotSuccessfulTest.
 */
trait FailureHtmlCaptureTrait {

  /**
   * Page HTML captured during tearDown, written on failure.
   */
  private ?string $capturedPageHtml = NULL;

  /**
   * {@inheritdoc}
   *
   * Captures page content while the Mink session is still alive.
   */
  protected function tearDown(): void {
    try {
      $this->capturedPageHtml = $this->getSession()->getPage()->getContent();
    }
    catch (\Throwable) {
      // Session may not have been started.
    }

    // Parent tearDown stops the Mink session — must be called after capture.
    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   *
   * Writes the captured HTML to the output directory on failure.
   */
  protected function onNotSuccessfulTest(\Throwable $t): void {
    $directory = getenv('DTT_HTML_OUTPUT_DIRECTORY') ?: ($_ENV['DTT_HTML_OUTPUT_DIRECTORY'] ?? '');

    if ($directory !== '' && $this->capturedPageHtml !== NULL) {
      try {
        if (!is_dir($directory)) {
          mkdir($directory, 0777, TRUE);
        }
        $filename = $directory . DIRECTORY_SEPARATOR . uniqid() . '_failure.html';
        file_put_contents($filename, $this->capturedPageHtml);
      }
      catch (\Throwable) {
        // Silently ignore write errors — always re-throw the original.
      }
    }

    parent::onNotSuccessfulTest($t);
  }

}
