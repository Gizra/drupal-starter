<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Config based tests.
 */
class ServerGeneralConfigTest extends ExistingSiteBase {

  /**
   * Test no warning is displayed when visiting the config page.
   */
  public function testConfigPage() {
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();

    $this->drupalLogin($user);

    $this->drupalGet('/admin/config');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->elementNotExists('css', '.messages--warning');
  }

}
