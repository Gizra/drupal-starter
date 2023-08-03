<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Base test class for writing ExistingSite tests with JS capability.
 *
 * All JS tests should extend this class instead of
 * ExistingSiteSelenium2DriverTestBase.
 */
class ServerGeneralSelenium2TestBase extends ExistingSiteSelenium2DriverTestBase {

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
   * Take a screenshot and save it in sites/simpletest/screenshots.
   *
   * Note: Do not leave a call to this method in commits. You should only
   * take screenshots locally for debugging purposes.
   *
   * @param string $screenshot_file_base_name
   *   The base name (without extension) to use as the screenshot file name.
   *   Time and extension will be appended to this.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function takeScreenshot(string $screenshot_file_base_name): void {
    if (getenv('CI') === 'true') {
      // Screenshots not allowed CI envs.
      return;
    }
    $working_dir = getcwd();
    // Prepare the directories.
    $dirs = [
      "{$working_dir}/sites/simpletest/",
      "{$working_dir}/sites/simpletest/screenshots/",
    ];
    foreach ($dirs as $dir) {
      if (file_exists($dir)) {
        continue;
      }
      mkdir($dir);
    }

    // Take the screenshot and save it in /sites/simpletest/screenshots.
    $filename = $screenshot_file_base_name . time() . '.png';
    $screenshot = $this->getDriverInstance()->getScreenshot();
    file_put_contents($dir . $filename, $screenshot);
  }

}
