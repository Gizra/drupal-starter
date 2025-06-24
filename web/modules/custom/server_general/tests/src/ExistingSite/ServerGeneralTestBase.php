<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\TestConfiguration;
use Drupal\Tests\server_general\Traits\MemoryManagementTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Custom base class tailored for the site specifics.
 *
 * All non-js tests should extend this class instead of ExistingSiteBase.
 */
abstract class ServerGeneralTestBase extends ExistingSiteBase {

  use MemoryManagementTrait;

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    parent::tearDown();
    $this->performMemoryCleanup();
  }

  /**
   * Creates a snapshot of the virtual browser for debugging purposes.
   */
  public function createHtmlSnapshot(): void {
    if (!file_exists(TestConfiguration::DEBUG_DIRECTORY)) {
      mkdir(TestConfiguration::DEBUG_DIRECTORY);
    }

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

    // Start with level 1 (the immediate caller).
    $level = 1;
    $caller = $backtrace[$level]['function'] ?? 'unknown';

    // If it's a closure, try to get the next level up.
    if (str_contains($caller, '{closure}') && isset($backtrace[$level + 1])) {
      $level++;
      $caller = $backtrace[$level]['function'];
    }

    if (isset($backtrace[$level]['class'])) {
      $caller = $backtrace[$level]['class'] . '::' . $caller;
    }

    $timestamp = microtime(TRUE);
    $filename = TestConfiguration::DEBUG_DIRECTORY . '/' . $caller . '_' . $timestamp . '.html';
    file_put_contents($filename, $this->getCurrentPage()->getOuterHtml());
    \Drupal::logger('server_general')->notice('HTML snapshot created: ' . str_replace('../', '', $filename));
  }

}
