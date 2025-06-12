<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\Traits;

/**
 * Provides memory management functionality for test classes.
 *
 * This trait contains common memory management methods that help reduce
 * PHPUnit memory usage during test execution.
 */
trait MemoryManagementTrait {

  /**
   * Tear down and unset variables to reduce memory usage.
   *
   * This method should be called from the tearDown() method of test classes
   * to help reduce memory usage by PHPUnit.
   *
   * @see https://stackoverflow.com/questions/13537545/clear-memory-being-used-by-php
   */
  protected function performMemoryCleanup(): void {
    $refl = new \ReflectionObject($this);
    foreach ($refl->getProperties() as $prop) {
      if (!$prop->isStatic()
        && !str_starts_with($prop->getDeclaringClass()->getName(), 'PHPUnit_')
        && $prop->getType()?->allowsNull() !== FALSE
      ) {
        $prop->setAccessible(TRUE);
        $prop->setValue($this, NULL);
      }
    }
  }

}
