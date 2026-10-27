<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\drupal_test_assertions\Assertions\RolesTrait;
use Drupal\Tests\drupal_test_assertions\Assertions\UsersTrait;

/**
 * User and roles tests.
 */
class ServerGeneralRolesTest extends ServerGeneralTestBase {

  use UsersTrait;
  use RolesTrait;

  /**
   * Anonymous users cannot create new accounts and permissions checks.
   */
  public function testSecurity() {
    $this->assertNoCreateAccountsAllowed();
    $this->assertUnprivilegedRolesCannotPerformRiskyActions();
  }

  /**
   * Test some roles exists.
   */
  public function testRoles() {
    $this->assertRoleExists('content_editor');
    $this->assertRoleHasPermissions('content_editor', [
      'access content overview',
      'access navigation',
      'administer nodes',
      'view own unpublished content',
      'view the administration theme',
    ]);
  }

}
