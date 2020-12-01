<?php

namespace Drupal\Tests\server_general\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Server general test functionality.
 */
class ServerGeneralTest extends KernelTestBase {

  /**
   * Modules to install for this test.
   *
   * @var array
   */
  public static $modules = ['server_general'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['server_general']);
  }

  /**
   * Dummy test example.
   */
  public function testDummy() {
    $this->assertTrue(TRUE);
  }

}
