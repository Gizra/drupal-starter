<?php

namespace Drupal\Tests\server_general\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Custom base class tailored for the site specifics.
 *
 * All non-js tests should extend this class instead of ExistingSiteBase.
 */
abstract class ServerGeneralTestBase extends ExistingSiteBase {

  /**
   * Tear down and unset variables.
   *
   * This is needed in order to reduce the memory usage by PHPUnit.
   *
   * @see https://stackoverflow.com/questions/13537545/clear-memory-being-used-by-php
   */
  public function tearDown(): void {
    parent::tearDown();
    $refl = new \ReflectionObject($this);
    foreach ($refl->getProperties() as $prop) {
      if (!$prop->isStatic()
        && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')
        && $prop->getType()?->allowsNull() !== FALSE
      ) {
        $prop->setAccessible(TRUE);
        $prop->setValue($this, NULL);
      }
    }
  }

  /**
   * Creates a snapshot of the virtual browser for debugging purposes.
   */
  public function createHtmlSnapshot(): void {
    if (!file_exists('../phpunit_debug')) {
      mkdir('../phpunit_debug');
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
    $filename = '../phpunit_debug/' . $caller . '_' . $timestamp . '.html';
    file_put_contents($filename, $this->getCurrentPage()->getOuterHtml());
    \Drupal::logger('server_general')->notice('HTML snapshot created: ' . str_replace('../', '', $filename));
  }

}
