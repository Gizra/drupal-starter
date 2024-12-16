<?php

namespace Drupal\Tests\server_rollbar_test\ExistingSite;

use Drupal\Core\Extension\ModuleInstallerInterface;
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
    // Install the scheduler module.
    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['server_rollbar_test']);

    $this->failOnPhpWatchdogMessages = FALSE;
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => 'AdminOne']);

    /** @var \Drupal\user\Entity\User|false $user */
    $user = reset($users);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/debug/rollbar-error-reporting');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->elementExists('css', '.rollbar-error');
  }

}
