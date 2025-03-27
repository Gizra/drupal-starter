<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Fast 404 test.
 */
class ServerGeneralFast404Test extends ExistingSiteBase {

  const DRUPAL_META_IDENTIFY = '<meta name="Generator" content="Drupal';

  /**
   * Tests Fast 404 functionality.
   */
  public function testFast404() {
    $paths = [
      '/backdoor.php',
      '/fileman',
      '/phpmyadmin',
      '/index.aspx',
      '/adminer/index.php',
    ];

    foreach ($paths as $path) {
      $start_time = microtime(TRUE);

      // Visit the path.
      $this->drupalGet($path);

      $end_time = microtime(TRUE);
      $duration = $end_time - $start_time;

      // Assert that the status code is 404.
      $this->assertSession()->statusCodeEquals(Response::HTTP_NOT_FOUND);

      $this->assertLessThan(0.2, $duration, "The response for $path took too long: $duration seconds.");

      // Get the response content.
      $content = $this->getSession()->getPage()->getContent();

      // Assert that the response does not contain specific Drupal meta tags.
      $this->assertStringNotContainsString(self::DRUPAL_META_IDENTIFY, $content);
    }
    $this->drupalGet('/');
    $content = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString(self::DRUPAL_META_IDENTIFY, $content);
  }

  /**
   * Tests that redirects work correctly for anonymous users.
   */
  public function testRedirect() {
    $redirect = [
      'redirect_source' => 'example',
      'redirect_redirect' => 'internal:/node/1',
      'status_code' => '301',
      'language' => 'en',
    ];

    $redirect_storage = \Drupal::entityTypeManager()->getStorage('redirect');
    $redirect = $redirect_storage->create($redirect);
    $redirect->save();
    $this->markEntityForCleanup($redirect);

    $this->drupalGet('/example');
    $this->assertStringContainsString(self::DRUPAL_META_IDENTIFY, $this->getSession()->getPage()->getContent());
    $this->assertSession()->addressNotEquals('/example');
    $this->assertSession()->addressNotEquals('/en/example');
  }

}
