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

}
