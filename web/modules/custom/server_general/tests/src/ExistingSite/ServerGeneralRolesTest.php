<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\drupal_test_assertions\Assertions\UsersTrait;
use Drupal\Tests\drupal_test_assertions\Assertions\RolesTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * User and roles tests.
 */
class ServerGeneralRolesTest extends ExistingSiteBase {

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
      'access toolbar',
      'administer nodes',
      'view own unpublished content',
      'view the administration theme',
    ]);
  }

}
