<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Behat\Mink\Driver\DriverInterface;
use Drupal\Tests\server_general\TestConfiguration;
use Drupal\Tests\server_general\Traits\MemoryManagementTrait;
use Mink\WebdriverClassicDriver\WebdriverClassicDriver;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Base test class for writing ExistingSite tests with JS capability.
 *
 * All JS tests should extend this class instead of
 * ExistingSiteSelenium2DriverTestBase.
 */
class ServerGeneralSelenium2TestBase extends ExistingSiteSelenium2DriverTestBase {

  use MemoryManagementTrait;

  /**
   * {@inheritdoc}
   */
  protected function getDriverInstance(): DriverInterface {
    if (!isset($this->driver)) {
      $hostname = getenv('DRUPAL_TEST_WEBDRIVER_HOSTNAME') ?: 'selenium-chrome';
      $port = getenv('DRUPAL_TEST_WEBDRIVER_PORT') ?: '4444';

      $capabilities = [
        'browserName' => 'chrome',
        // Accept self-signed certificates in the local Selenium container.
        // So calling https://drupal-starter.ddev.site:4443/
        // wouldn't result in certificate errors.
        'acceptInsecureCerts' => TRUE,        
        'goog:chromeOptions' => [
          'args' => [
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--headless',
            '--dns-prefetch-disable',
            '--no-sandbox',
          ],
        ],
      ];

      $url = "http://{$hostname}:{$port}";
      $this->driver = new WebdriverClassicDriver('chrome', $capabilities, $url);
    }
    return $this->driver;
  }

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $session = $this->getSession();
    // Make takeScreenshot() more developer friendly, capture
    // as many details as possible.
    $session->resizeWindow(TestConfiguration::BROWSER_WIDTH, TestConfiguration::BROWSER_HEIGHT);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    parent::tearDown();
    $this->performMemoryCleanup();
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
    file_put_contents($dirs[1] . $filename, $screenshot);
  }

}
