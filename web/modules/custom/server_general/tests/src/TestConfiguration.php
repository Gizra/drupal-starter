<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general;

/**
 * Centralized test configuration constants and settings.
 *
 * This class provides a single location for all test-related constants,
 * reducing duplication and making configuration management easier.
 */
final class TestConfiguration {

  /**
   * ElasticSearch wait time in seconds.
   */
  public const ES_WAIT_SECONDS = 1;

  /**
   * ElasticSearch wait time in microseconds.
   */
  public const ES_WAIT_MICRO_SECONDS = 200;

  /**
   * ElasticSearch retry limit for operations.
   */
  public const ES_RETRY_LIMIT = 20;

  /**
   * Default test image filename.
   */
  public const DEFAULT_TEST_IMAGE = 'test.png';

  /**
   * Test images directory path relative to test directory.
   */
  public const IMAGES_PATH = '../images/';

  /**
   * Debug directory for HTML snapshots.
   */
  public const DEBUG_DIRECTORY = '../phpunit_debug';

  /**
   * Default test user email.
   */
  public const TEST_USER_EMAIL = 'test@example.com';

  /**
   * Screenshot directory path.
   */
  public const SCREENSHOT_DIRECTORY = 'sites/simpletest/screenshots/';

  /**
   * Default browser window width for Selenium tests.
   */
  public const BROWSER_WIDTH = 1900;

  /**
   * Default browser window height for Selenium tests.
   */
  public const BROWSER_HEIGHT = 1900;

  /**
   * Gets the full path to test images directory.
   *
   * @return string
   *   The full path to the test images directory.
   */
  public static function getImagesPath(): string {
    return __DIR__ . '/' . self::IMAGES_PATH;
  }

  /**
   * Gets the full path to a specific test image.
   *
   * @param string $filename
   *   The image filename.
   *
   * @return string
   *   The full path to the test image.
   */
  public static function getTestImagePath(string $filename = self::DEFAULT_TEST_IMAGE): string {
    return self::getImagesPath() . $filename;
  }

}
