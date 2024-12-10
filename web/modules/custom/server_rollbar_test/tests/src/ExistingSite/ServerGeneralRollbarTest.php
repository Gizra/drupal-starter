<?php

namespace Drupal\Tests\server_rollbar_test\ExistingSite;

use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test Rollbar robustness.
 *
 * @group Rollbar
 */
class ServerGeneralRollbarTest extends ExistingSiteBase {

  /**
   * Test admin role.
   */
  public function testAdministratorRole(): void {
    $this->failOnPhpWatchdogMessages = FALSE;
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => 'AdminOne']);
    $user = reset($users);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/debug/rollbar-error-reporting');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->elementExists('css', '.rollbar-error');
  }

}
